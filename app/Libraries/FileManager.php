<?php

namespace CtrlV\Libraries;

use AWS;
use Config;
use CtrlV\Jobs\MoveRemoteFileJob;
use CtrlV\Jobs\OptimizeFileJob;
use CtrlV\Models\ImageFile;
use Exception;
use Illuminate\Foundation\Bus\DispatchesJobs;
use InterventionFacade;
use Intervention\Image\Image as Picture;

/**
 * FileManager saves/loads Pictures to/from the file system.
 */
class FileManager
{
    use DispatchesJobs;

    // Directories in the image dir
    const ANNOTATION_DIR = 'annotation';
    const IMAGE_DIR = 'img';
    const THUMBNAIL_DIR = 'thumb';
    const UNCROPPED_DIR = 'uncropped';

    private $localDataDirectory;
    private $s3BucketName;

    /**
     * @var \Aws\S3\S3Client
     */
    private $s3Client = null;
    private $s3Access = 'public-read';
    private $s3ExpiresHeader = 'Fri, 1 Jan 2100 00:00:00 GMT';

    public function __construct()
    {
        $this->localDataDirectory = Config::get('app.data_dir');
        $this->s3BucketName = Config::get('aws.s3.image_bucket');
    }

    /**
     * Return a Picture object with the contents of the given ImageFile.
     *
     * @param ImageFile $imageFile
     *
     * @return Picture|null
     */
    public function getPictureForImageFile(ImageFile $imageFile)
    {
        $path = $imageFile->getPath();

        if (!$this->localFileExists($path)) {
            // Copy from S3 to server if it's not already there
            if (!$this->copyFromRemote($path)) {
                return null;
            }
        }

        if ($this->localFileExists($path)) {
            return InterventionFacade::make($this->localDataDirectory.$path);
        }

        return null;
    }

    /**
     * Save an Picture object to the file system.
     * Runs OptimizeFileJob on the file after it is saved.
     *
     * @param Picture        $picture
     * @param string         $directory One of the *_DIR constants.
     * @param string         $filename
     * @param ImageFile|null $originalImageFile
     *
     * @throws Exception
     * @return ImageFile
     */
    public function savePicture(
        Picture $picture,
        $directory = self::IMAGE_DIR,
        $filename = null,
        ImageFile $originalImageFile = null
    ) {
        if (!$filename) {
            $mime = $picture->mime();
            if (empty($mime)) {
                $mime = 'image/jpeg';
            }
            $extension = $this->getExtensionForMime($mime);

            $filename = $this->generateFilename($extension);
        }

        $relativePath = $directory.'/'.$filename;

        // Save locally
        if (!$this->savePictureLocally($picture, $relativePath)) {
            throw new Exception('Unable to save file locally.');
        }

        $imageFile = new ImageFile(
            [
                'originalImageFileId' => $originalImageFile ? $originalImageFile->getId() : null,
                'directory' => $directory,
                'filename' => $filename,
                'width' => $picture->getWidth(),
                'height' => $picture->getHeight(),
                'size' => $picture->filesize() // File size in bytes
            ]
        );
        $imageFile->save();

        // Optimize and copy to remote storage
        $this->dispatch(new OptimizeFileJob($imageFile));

        return $imageFile;
    }

    /**
     * Save a file in the local filesystem.
     *
     * @param string $contents
     * @param string $path Relative to localDir
     *
     * @return boolean
     */
    private function saveFileLocally($contents, $path)
    {
        $this->createLocalDirectory(dirname($path));

        $result = file_put_contents($this->localDataDirectory.$path, $contents);
        clearstatcache();

        return $result;
    }

    /**
     * Save a picture in the local filesystem.
     *
     * @param Picture $picture
     * @param string  $relativePath Relative to localDir
     *
     * @return boolean
     */
    private function savePictureLocally(Picture $picture, $relativePath)
    {
        $this->createLocalDirectory(dirname($relativePath));

        $result = $picture->save($this->localDataDirectory.$relativePath);
        clearstatcache();

        return $result;
    }

    /**
     * Create a directory in the local filesystem if it does not already exist.
     *
     * @param string $path Relative to the localDir
     *
     * @return boolean
     */
    private function createLocalDirectory($path)
    {
        clearstatcache();
        if (is_dir($this->localDataDirectory.$path)) {
            return true;
        }

        return mkdir($this->localDataDirectory.$path, 0777, true); // true = make intermediate directories
    }

    /**
     * Generate a directory and filename to save an image as.
     *
     * @param string $extension
     * @param string $suffix
     *
     * @return string 9152/1501/d8ca/8232{$suffix}{.$extension}
     */
    private function generateFilename($extension = 'jpg', $suffix = '')
    {
        $filename = bin2hex(openssl_random_pseudo_bytes(12));
        $filename = str_split($filename, 3);
        $filename = implode('/', array_slice($filename, 0, 4)).implode('', array_slice($filename, 4));

        if ($suffix) {
            $filename .= $suffix;
        }

        if ($extension) {
            $filename .= '.'.$extension;
        }

        return $filename;
    }

    /**
     * Copy local file to remote storage.
     * Public so it can be run from a Job.
     *
     * @param ImageFile $imageFile
     *
     * @return \Aws\Result
     */
    public function copyToRemote(ImageFile $imageFile)
    {
        $s3Client = $this->getS3Client();

        $path = $imageFile->getPath();

        $storageClass = $imageFile->directory === FileManager::THUMBNAIL_DIR ? 'REDUCED_REDUNDANCY' : 'STANDARD';

        $result = $s3Client->putObject(
            [
                'ACL' => $this->s3Access,
                'Bucket' => $this->s3BucketName,
                'Expires' => $this->s3ExpiresHeader,
                'Key' => $path,
                'SourceFile' => $this->localDataDirectory.$path,
                'StorageClass' => $storageClass,
            ]
        );

        $imageFile->copied = true;
        $imageFile->save();

        return $result;
    }

    /**
     * Copy from remote storage to local file.
     * Returns the contents of the file
     *
     * @param string  $relativePath
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
     * Delete a file from local storage.
     *
     * @param string $path
     */
    public function deleteFile($path)
    {
        if ($this->localFileExists($path)) {
            $result['local'] = unlink($this->localDataDirectory.$path);
            clearstatcache();
        }

        $cacheManager = new CacheManager();
        $cacheManager->purge($path);
    }

    /**
     * Delete a file from remote storage.
     *
     * @param string $path
     */
    public function deleteRemoteFile($path)
    {
        $s3Client = $this->getS3Client();
        try {
            $s3Client->deleteMatchingObjects($this->s3BucketName, $path);
        } catch (Exception $e) {
        }

        $cacheManager = new CacheManager();
        $cacheManager->purge($path);
    }

    public function moveFile(ImageFile $file, $newDirectory)
    {
        $oldPath = $file->getPath();
        $newPath = $newDirectory.'/'.$file->getFilename();

        // Move the local file
        if ($this->localFileExists($oldPath)) {
            $this->createLocalDirectory(dirname($newPath));
            rename($this->localDataDirectory.$oldPath, $this->localDataDirectory.$newPath);
            clearstatcache();
        }

        // Move the remote file
        $this->dispatch(new MoveRemoteFileJob($oldPath, $newPath));

        $file->directory = $newDirectory;
        $file->save();

        $cacheManager = new CacheManager();
        $cacheManager->purge($oldPath);
        $cacheManager->purge($newPath);
    }

    /**
     * Called by MoveRemoteFileJob
     *
     * @param string $oldPath
     * @param string $newPath
     */
    public function moveRemoteFile($oldPath, $newPath)
    {
        $s3Client = $this->getS3Client();

        if ($s3Client->copyObject(
            [
                'ACL' => $this->s3Access,
                'Bucket' => $this->s3BucketName,
                'CopySource' => "{$this->s3BucketName}/{$oldPath}",
                'Expires' => $this->s3ExpiresHeader,
                'Key' => $newPath,
            ]
        )
        ) {
            $result['remote'] = true;
            $s3Client->deleteMatchingObjects($this->s3BucketName, $oldPath);
        }

        $cacheManager = new CacheManager();
        $cacheManager->purge($oldPath);
        $cacheManager->purge($newPath);
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
        return is_file($this->localDataDirectory.$relativePath);
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
