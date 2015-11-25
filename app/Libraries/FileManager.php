<?php

namespace CtrlV\Libraries;

use AWS;
use Config;
use Exception;
use InterventionFacade;
use CtrlV\Jobs\DeleteFileJob;
use CtrlV\Jobs\RenameFileJob;
use CtrlV\Jobs\OptimizeFileJob;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Intervention\Image\Image as Picture;

/**
 * FileManager saves/loads Pictures to/from the file system.
 */
class FileManager
{
    use DispatchesJobs;

    // Directories in the image dir
    const ANNOTATION = 'annotation';
    const IMAGE = 'img';
    const THUMB = 'thumb';
    const UNCROPPED = 'uncropped';

    private $localDir;
    private $s3BucketName;
    private $s3Client = null;
    private $s3Access = 'public-read';
    private $s3Expires = 'Fri, 1 Jan 2100 00:00:00 GMT';

    public function __construct()
    {
        $this->localDir = Config::get('app.data_dir');
        $this->s3BucketName = Config::get('aws.s3.image_bucket');
    }

    /**
     * Return the contents of a file.
     *
     * @param string $relativePath
     *
     * @return string
     */
    public function getFile($relativePath)
    {
        if ($this->localFileExists($relativePath)) {
            return file_get_contents($this->localDir . $relativePath);
        }

        return $this->copyFromRemote($relativePath, true);
    }

    /**
     * Return a Picture object from the given relativePath.
     *
     * @param string $relativePath
     *
     * @return Picture|null
     */
    public function getPicture($relativePath)
    {
        if ($this->localFileExists($relativePath)) {
            return InterventionFacade::make($this->localDir . $relativePath);
        }

        if ($this->copyFromRemote($relativePath)) {
            return InterventionFacade::make($this->localDir . $relativePath);
        }

        return null;
    }

    /**
     * Save an Picture object to the file system.
     * Runs OptimizeFileJob on the file after it is saved.
     *
     * @param Picture $picture
     * @param string $type IMAGE|ANNOTATION|THUMB
     * @param string $filename
     *
     * @throws Exception
     * @return string Path to the saved file relative to the localDir
     */
    public function savePicture(Picture $picture, $type = 'img', $filename = null)
    {
        if (!$filename) {
            $mime = $picture->mime();
            if (empty($mime)) {
                $mime = 'image/jpeg';
            }
            $extension = $this->getExtensionForMime($mime);

            $filename = $this->generateFilename($extension);
        }

        $relativePath = $type . '/' . $filename;

        // Save locally
        if (!$this->savePictureLocally($picture, $relativePath)) {
            throw new Exception('Unable to save file locally.');
        }

        // Optimize and copy to remote storage
        $this->dispatch(new OptimizeFileJob($relativePath));

        return $filename;
    }

    /**
     * Save a file in the local filesystem.
     *
     * @param string $contents
     * @param string $relativePath Relative to localDir
     *
     * @return boolean
     */
    private function saveFileLocally($contents, $relativePath)
    {
        $this->createLocalDirectory(dirname($relativePath));

        $result = file_put_contents($this->localDir . $relativePath, $contents);
        clearstatcache();
        return $result;
    }

    /**
     * Save a picture in the local filesystem.
     *
     * @param Picture $picture
     * @param string $relativePath Relative to localDir
     *
     * @return boolean
     */
    private function savePictureLocally(Picture $picture, $relativePath)
    {
        $this->createLocalDirectory(dirname($relativePath));

        $result = $picture->save($this->localDir . $relativePath);
        clearstatcache();
        return $result;
    }

    /**
     * Create a directory in the localDir if it does not already exist.
     *
     * @param string $relativePath Relative to the localDir
     *
     * @return boolean
     */
    private function createLocalDirectory($relativePath)
    {
        clearstatcache();
        if (is_dir($this->localDir . $relativePath)) {
            return true;
        }

        return mkdir($this->localDir . $relativePath, 0777, true); // true = make intermediate directories
    }

    /**
     * Generate a directory and filename to save an image as.
     *
     * @param string $extension
     * @param string $prefix
     *
     * @return string yy/mm/dd/{$prefix}-filename.{$extension}
     */
    private function generateFilename($extension = 'jpg', $prefix = '')
    {
        $directory = date('y/m/d') . '/';

        $filename = $directory;
        if ($prefix) {
            $filename .= $prefix . '-';
        }
        $filename .= uniqid();
        if ($extension) {
            $filename .= '.' . $extension;
        }

        return $filename;
    }

    /**
     * Copy local file to remote storage.
     * Public so it can be run from a Job.
     *
     * @param string $relativePath
     *
     * @return \Aws\Result
     */
    public function copyToRemote($relativePath)
    {
        $s3Client = $this->getS3Client();
        return $s3Client->putObject(
            [
                'ACL' => $this->s3Access,
                'Bucket' => $this->s3BucketName,
                'Expires' => $this->s3Expires,
                'Key' => $relativePath,
                'SourceFile' => $this->localDir . $relativePath
            ]
        );
    }

    /**
     * Copy from remote storage to local file.
     * Returns the contents of the file
     *
     * @param string $relativePath
     * @param boolean $returnContents If true returns the contents of the file otherwise returns a boolean
     *
     * @return string|bool
     */
    private function copyFromRemote($relativePath, $returnContents = false)
    {
        $s3Client = $this->getS3Client();

        if ($url = $s3Client->getObjectUrl($this->s3BucketName, $relativePath)) {
            if ($contents = file_get_contents($url)) {
                $this->saveFileLocally($contents, $relativePath);
                return $returnContents ? $contents : true;
            }
        }

        return false;
    }

    /**
     * Deletes a file both locally and from remote.
     *
     * @param string $relativePath
     * @param boolean $synchronously Delete from remote
     *
     * @return array
     */
    public function deleteFile($relativePath, $synchronously = false)
    {
        $result = [];

        // Local always happens synchronously
        if ($this->localFileExists($relativePath)) {
            $result['local'] = unlink($this->localDir . $relativePath);
            clearstatcache();
        }

        // Remote deletion can happen in a queue
        if (!$synchronously) {
            $result['queued'] = $this->dispatch(new DeleteFileJob($relativePath));
            return $result;
        }

        $result['remote'] = $this->deleteFromRemote($relativePath);

        $cacheManager = new CacheManager();
        $result['purge'] = $cacheManager->purge($relativePath);

        return $result;
    }

    /**
     * Delete a file from remote only.
     *
     * @param string $relativePath
     *
     * @return bool
     */
    public function deleteFromRemote($relativePath)
    {
        $s3Client = $this->getS3Client();
        try {
            $s3Client->deleteMatchingObjects($this->s3BucketName, $relativePath);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Rename a file both locally and from remote.
     *
     * @param string $oldRelativePath
     * @param string $newRelativePath
     * @param bool $synchronously
     *
     * @return array
     */
    public function renameFile($oldRelativePath, $newRelativePath, $synchronously = false)
    {
        $result = [];

        // Local always happens synchronously
        if ($this->localFileExists($oldRelativePath)) {
            $this->createLocalDirectory(dirname($newRelativePath));
            $result['local'] = rename($this->localDir . $oldRelativePath, $this->localDir . $newRelativePath);
            clearstatcache();
        }

        // Remote renaming can happen in a queue
        if (!$synchronously) {
            return $this->dispatch(new RenameFileJob($oldRelativePath, $newRelativePath));
        }

        $s3Client = $this->getS3Client();

        if ($s3Client->copyObject(
            [
                'ACL' => $this->s3Access,
                'Bucket' => $this->s3BucketName,
                'CopySource' => "{$this->s3BucketName}/{$oldRelativePath}",
                'Expires' => $this->s3Expires,
                'Key' => $newRelativePath,
            ]
        )
        ) {
            $result['remote'] = true;
            $s3Client->deleteMatchingObjects($this->s3BucketName, $oldRelativePath);
        }

        $cacheManager = new CacheManager();
        $cacheManager->purge($oldRelativePath);
        $cacheManager->purge($newRelativePath); // Just in case

        return $result;
    }

    /**
     * Check if the given file exists locally.
     *
     * @param string $relativePath
     *
     * @return boolean
     */
    private function localFileExists($relativePath)
    {
        return is_file($this->localDir . $relativePath);
    }

    /**
     * Return the file extension for the given mime type.
     * (Only those supported by Intervention are defined)
     *
     * @param string $mime
     *
     * @return string
     * @throws \Intervention\Image\Exception\NotSupportedException
     */
    private function getExtensionForMime($mime)
    {
        switch (strtolower($mime)) {

            case 'gif':
            case 'image/gif':
                return 'gif';
                break;

            case 'png':
            case 'image/png':
            case 'image/x-png':
                return 'png';
                break;

            case 'jpg':
            case 'jpeg':
            case 'image/jpg':
            case 'image/jpeg':
            case 'image/pjpeg':
                return 'jpg';
                break;

            case 'tif':
            case 'tiff':
            case 'image/tiff':
            case 'image/tif':
            case 'image/x-tif':
            case 'image/x-tiff':
                return 'tif';
                break;

            case 'bmp':
            case 'image/bmp':
            case 'image/ms-bmp':
            case 'image/x-bitmap':
            case 'image/x-bmp':
            case 'image/x-ms-bmp':
            case 'image/x-win-bitmap':
            case 'image/x-windows-bmp':
            case 'image/x-xbitmap':
                return 'bmp';
                break;

            case 'ico':
            case 'image/x-icon':
            case 'image/vnd.microsoft.icon':
                return 'ico';
                break;

            case 'psd':
            case 'image/vnd.adobe.photoshop':
                return 'psd';
                break;

            default:
                throw new \Intervention\Image\Exception\NotSupportedException("Unsupported image format ({$mime}).");
        }
    }

    /**
     * @return \Aws\S3\S3Client
     */
    private function getS3Client()
    {
        if (!is_null($this->s3Client)) {
            return $this->s3Client;
        }

        $this->s3Client = AWS::createClient('s3');
        return $this->s3Client;
    }
}
