<?php

namespace CtrlV\Factories;

use Exception;
use InterventionFacade;
use Intervention\Image\Image;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class ImageFactory
 * Generates Intervention Image objects from various sources.
 */
class ImageFactory
{
    /**
     * Create an image from a base64 encoded string.
     *
     * @param string $base64String
     *
     * @throws Exception
     * @return Image
     */
    public function createFromBase64String($base64String)
    {
        if (empty($base64String)) {
            throw new Exception('Empty base64 string given');
        }

        $image = InterventionFacade::make($base64String);
        $image->orientate();
        return $image;
    }

    /**
     * Create an image from an uploaded file.
     *
     * @param UploadedFile $file
     *
     * @throws Exception
     * @return Image
     */
    public function createFromUploadedFile(UploadedFile $file)
    {
        $path = $file->getRealPath();
        if (empty($file) || !is_uploaded_file($path)) {
            throw new Exception('No file uploaded');
        }

        $image = InterventionFacade::make($path);
        $image->orientate();
        return $image;
    }

    /**
     * Create an image from a URL.
     *
     * @param string $url
     *
     * @throws Exception
     * @return Image
     */
    public function createFromUrl($url)
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new Exception('Invalid URL was given');
        }

        $image = InterventionFacade::make($url);
        $image->orientate();
        return $image;
    }
}
