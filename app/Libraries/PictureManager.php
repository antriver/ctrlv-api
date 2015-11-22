<?php

namespace CtrlV\Libraries;

use Exception;
use InterventionFacade;
use Intervention\Image\Image as Picture;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class PictureManager
 * Generates Picture (Intervention\Image\Image) objects from various sources.
 */
class PictureManager
{
    /**
     * Create an image from a base64 encoded string.
     *
     * @param string $base64String
     *
     * @throws Exception
     * @return Picture
     */
    public function createFromBase64String($base64String)
    {
        if (empty($base64String)) {
            throw new Exception('Empty base64 string given');
        }

        $picture = InterventionFacade::make($base64String);
        $picture->orientate();
        return $picture;
    }

    /**
     * Create an image from an uploaded file.
     *
     * @param UploadedFile $file
     *
     * @throws Exception
     * @return Picture
     */
    public function createFromUploadedFile(UploadedFile $file)
    {
        $path = $file->getRealPath();
        if (empty($file) || !is_uploaded_file($path)) {
            throw new Exception('No file uploaded');
        }

        $picture = InterventionFacade::make($path);
        $picture->orientate();
        return $picture;
    }

    /**
     * Create an image from a URL.
     *
     * @param string $url
     *
     * @throws Exception
     * @return Picture
     */
    public function createFromUrl($url)
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new Exception('Invalid URL was given');
        }

        $picture = InterventionFacade::make($url);
        $picture->orientate();
        return $picture;
    }
}
