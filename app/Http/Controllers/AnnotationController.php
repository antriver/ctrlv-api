<?php

namespace CtrlV\Http\Controllers;

use Config;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\RedirectResponse;
use Response;
use CtrlV\Http\Requests;
use CtrlV\Models\Image;
use CtrlV\Libraries\FileManager;
use CtrlV\Libraries\PictureFactory;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @apiDefine ImageSuccessResponse
 * @apiSuccessExample {json} Success Response
 *               {
 *                   "success": true,
 *                   "image": [...] // See: Get Image Info
 *               }
 */

/**
 * @apiDefine RequiresViewableImage
 * @apiParam {string} [sessionKey] Session key for the user that owns the image.
 *     **Either `sessionKey` or `password` is required if the image's privacy is `2`.**
 * @apiParam {string} [password] Password to view the image.
 *     **Either `sessionKey` or `password` is required if the image's privacy is `2`.**
 */

/**
 * @apiDefine RequiresEditableImage
 * @apiParam {string} sessionKey Session key for the user that owns the image.
 *     **Either `sessionKey` or `imageKey` is required.**
 * @apiParam {string} imageKey Editing key for the image (obtained when the image is created).
 *     **Either `sessionKey` or `imageKey` is required.**
 */
class AnnotationController extends Base\ApiController
{
    /**
     * @api            {get} /images/{id}/annotation View an Annotation
     * @apiGroup       Image Annotations
     * @apiDescription Returns an HTTP 302 redirect to the annotation image file.
     * @apiUse         RequiresViewableImage
     *
     * @param ConfigRepository $config
     * @param Image $image
     *
     * @return Response
     */
    public function show(ConfigRepository $config, Image $image)
    {
        $this->requireViewableImage($image);

        if (!$image->annotation) {
            return $this->error('There is no annotation for this image.', 404);
        }

        return new RedirectResponse($config->get('app.data_url') . 'annotation/' . $image->annotation);
    }

    /**
     * @api            {post} /images/{id}/annotation Create an Annotation
     * @apiGroup       Image Annotations
     * @apiDescription Add an annotation to an image. This will replace an existing annotation if it exists.
     * @apiParam {string} base64 Base64 encoded image to use as the annotation. This does not have to have the same
     *     dimensions as the image, but it must be the same ratio. It will be resized to the size of the image.
     * @apiUse         RequiresEditableImage
     * @apiUse         ImageSuccessResponse
     *
     * @param Request $request
     * @param Image $image
     * @param PictureFactory $pictureFactory
     * @param FileManager $fileManager
     *
     * @return Response
     */
    public function store(
        Request $request,
        Image $image,
        PictureFactory $pictureFactory,
        FileManager $fileManager
    ) {
        $this->requireEditableImage($image);

        $this->validate(
            $request,
            [
                'base64' => 'required|string'
            ]
        );

        $annotationPicture = $pictureFactory->createFromBase64String($request->input('base64'));

        // Make sure the annotation can be resized to the image's size nicely
        $annotationRatio = round($annotationPicture->width() / $annotationPicture->height(), 2);
        $imageRatio = round($image->w / $image->h, 2);
        if ($annotationRatio !== $imageRatio) {
            throw new HttpException(
                422,
                "The annotation's ratio ({$annotationRatio}) is not the same as the image's ratio ({$imageRatio})."
            );
        }

        // Resize annotation to the size of the image
        $annotationPicture->resize($image->w, $image->h);

        $annotationFilename = $fileManager->savePicture($annotationPicture, 'annotation');

        $image->annotation = $annotationFilename;

        $success = $image->save();

        return $this->successResponse(['success' => $success, 'image' => $image->fresh()]);
    }

    /**
     * @api            {delete} /images/{id}/annotation Delete an Annotation
     * @apiGroup       Image Annotations
     * @apiDescription Delete an image's annotation.
     * @apiUse         RequiresEditableImage
     * @apiUse         ImageSuccessResponse
     *
     * @param Image $image
     * @param FileManager $fileManager
     *
     * @return Response
     */
    public function destroy(Image $image, FileManager $fileManager)
    {
        $this->requireEditableImage($image);

        if (!$image->annotation) {
            throw new HttpException(400, "This image does not have an annotation.");
        }

        $fileManager->deleteFile('annotation/' . $image->annotation);

        $image->annotation = null;

        $success = $image->save();

        return $this->successResponse(['success' => $success, 'image' => $image->fresh()]);
    }
}
