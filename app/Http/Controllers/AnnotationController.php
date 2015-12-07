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

class AnnotationController extends Base\ApiController
{
    /**
     * @api            {get} /images/{imageId}/annotation View an Annotation
     * @apiGroup       Image Annotations
     * @apiDescription Returns an HTTP 302 redirect to the annotation image file.
     * @apiUse         RequiresViewableImage
     *
     * @param Image $image
     *
     * @return Response
     */
    public function show(Image $image)
    {
        $this->requireViewableModel($image);

        $annotationImageFile = $image->getAnnotationImageFile();

        if (!$annotationImageFile) {
            throw new ModelNotFoundException(404, 'There is no annotation for this image.');
        }

        return new RedirectResponse($annotationImageFile->getUrl());
    }

    /**
     * @api {post} /images/{imageId}/annotation Create an Annotation
     * @apiGroup Image Annotations
     * @apiDescription Add an annotation to an image. This will replace an existing annotation if it exists.
     * @apiParam {string} base64 Base64 encoded image to use as the annotation. This does not have to have the same
     *     dimensions as the image, but it must be the same ratio. It will be resized to the size of the image.
     * @apiUse RequiresEditableImage
     * @apiUse ImageSuccessResponse
     *
     * @param Image $image
     * @param FileManager $fileManager
     * @param PictureFactory $pictureFactory
     *
     * @throws \Exception
     * @return Response
     */
    public function store(
        Image $image,
        FileManager $fileManager,
        PictureFactory $pictureFactory
    ) {
        $this->requireEditableImage($image);

        $this->validate(
            $this->request,
            [
                'base64' => 'required|string'
            ]
        );

        if ($image->operationInProgress) {
            throw new HttpException(409, "Another operation is currently in progress for this image.");
        }
        $image->operationInProgress = true;
        $image->save();

        $annotationPicture = $pictureFactory->createFromBase64String($this->request->input('base64'));

        $imageFile = $image->getImageFile();

        // Make sure the annotation can be resized to the image's size nicely
        $annotationRatio = round($annotationPicture->width() / $annotationPicture->height(), 2);
        $imageRatio = round($imageFile->width / $imageFile->height, 2);
        if ($annotationRatio !== $imageRatio) {
            throw new HttpException(
                422,
                "The annotation's ratio ({$annotationRatio}) is not the same as the image's ratio ({$imageRatio})."
            );
        }

        // Resize annotation to the size of the image
        $annotationPicture->resize($imageFile->width, $imageFile->height);

        $annotationImageFile = $fileManager->savePicture($annotationPicture, 'annotation');

        $image->setAnnotationImageFile($annotationImageFile);
        $image->operationInProgress = false;
        $success = $image->save();

        return $this->response(['success' => $success, 'image' => $image->fresh()]);
    }

    /**
     * @api {delete} /images/{imageId}/annotation Delete an Annotation
     * @apiGroup Image Annotations
     * @apiDescription Delete an image's annotation.
     * @apiUse RequiresEditableImage
     * @apiUse GenericSuccessResponse
     *
     * @param Image $image
     *
     * @return Response
     */
    public function destroy(Image $image)
    {
        $this->requireEditableImage($image);

        $annotationImageFile = $image->getAnnotationImageFile();

        if (!$annotationImageFile) {
            throw new HttpException(400, "This image does not have an annotation.");
        }

        if ($image->operationInProgress) {
            throw new HttpException(409, "Another operation is currently in progress for this image.");
        }
        $image->operationInProgress = true;
        $image->save();

        $image->setAnnotationImageFile(null);
        $annotationImageFile->delete();
        $image->operationInProgress = false;
        $success = $image->save();

        return $this->response(['success' => $success, 'image' => $image->fresh()]);
    }
}
