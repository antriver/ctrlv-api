<?php

namespace CtrlV\Repositories;

use Config;
use Exception;
use InterventionFacade;
use CtrlV\Models\Image as ImageRow;
use Intervention\Image\Image as InterventionImage;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImageRepository
{
    const TYPE_ANNOTATION = 'annotation';
    const TYPE_IMAGE = 'img';
    const TYPE_THUMB = 'thumb';

    private $localDataDirectory;

    public function __construct()
    {
        $this->localDataDirectory = Config::get('app.data_dir');
    }



    /**
     * @param  string $base64String
     * @return Intervention\Image\Image
     */
    public function createFromBase64String($base64String)
    {
        if (empty($base64String)) {
            throw new Exception("Empty base64 string given");
        }

        return InterventionFacade::make($base64String);
    }

    public function createFromUploadedFile(UploadedFile $file)
    {
        $path = $file->getRealPath();
        if (empty($file) || !is_uploaded_file($path)) {
            throw new Exception("No file uploaded");
        }

        return InterventionFacade::make($path);
    }



    public function generateFilename($extension = 'jpg', $prefix = '')
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

        return [$directory, $filename];
    }

    /**
     * Create a directory in the data directory if it doesn't already exist
     * @param  string $directory
     * @return boolean
     */
    private function makeLocalDirectory($directory)
    {
        if (is_dir($this->localDataDirectory . $directory)) {
            return true;
        }

        return mkdir($this->localDataDirectory . $directory, 0777, true); // true = make intermediate directories
    }

    public function save(InterventionImage $image, $type = self::TYPE_IMAGE)
    {
        list($directory, $filename) = $this->generateFilename();

        // Save locally
        $this->makeLocalDirectory($type . '/' . $directory);
        $image->save($this->localDataDirectory . $type . '/' . $filename);

        // Save remotely
        // TODO: Add to the queue to be optimized and sent off to remote storage

        return $filename;
    }



    public function getImageForImageRow(ImageRow $imageRow)
    {

    }

    private function getFileForImageRow(ImageRow $imageRow)
    {

    }
}
