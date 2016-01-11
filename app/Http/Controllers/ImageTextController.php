<?php

namespace CtrlV\Http\Controllers;

use CtrlV\Http\Requests;
use CtrlV\Libraries\FileManager;
use CtrlV\Libraries\PictureFactory;
use CtrlV\Models\Image;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ImageTextController extends Base\ApiController
{
    /**
     * @api            {get} /images/{imageId}/text Get Image Text
     * @apiGroup       Image Text
     * @apiDescription Returns the text found in the image.
     * @apiUse         RequiresViewableImage
     *
     * @param Image $image
     *
     * @return Response
     */
    public function show(Image $image)
    {
        $this->requireViewableModel($image);

        $text = $image->getImageFile()->text;

        return $this->response(['text' => $text]);
    }
}
