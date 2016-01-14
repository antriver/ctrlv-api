<?php

namespace CtrlV\Http\Controllers;

use CtrlV\Http\Requests;
use CtrlV\Libraries\FileManager;
use CtrlV\Libraries\PasswordHasher;
use CtrlV\Libraries\PictureFactory;
use CtrlV\Models\Album;
use CtrlV\Models\Image;
use CtrlV\Models\User;
use Exception;
use Illuminate\Http\RedirectResponse;
use Input;
use Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ImagesController extends Base\ApiController
{
    /**
     * @api            {post} /images Create an Image
     * @apiGroup       Images
     * @apiDescription Store an image and return its metadata including a to view it.
     *     <br/>The image will be made anonymous if a `sessionKey` is given and the user's `defaultAnonymous`
     *     value is `true`.
     *     <br/>The image will be password protected if a `sessionKey` is given and the user's `defaultPassword`
     *     value is set.
     *     <br/>If no `sessionKey` is given in the request, an `imageKey` property will be returned in the response
     *     it is a 64 character string that can be used to manipulate the image in future requests.
     *     If a sessionKey was given, use that or another sessionKey for that user to manipulate it.
     * @apiParam {string} base64 Base64 encoded image to upload.
     * @apiParam {string} [sessionKey] A session key for a user the image should be attributed to.
     * @apiSuccessExample {json} Success Response
     *     {
     *       "success": true,
     *       "image": {
     *         // See Get Image Info
     *       },
     *       "imageKey": "2c9933ce51d082ec61a4a55e21cb9befabdca6a65635930fdd0891dbb78f68f2"
     *     }
     *
     * @param FileManager    $fileManager
     * @param PictureFactory $pictureFactory
     * @param PasswordHasher $passwordHasher
     *
     * @throws Exception
     * @return Response
     */
    public function store(FileManager $fileManager, PictureFactory $pictureFactory, PasswordHasher $passwordHasher)
    {
        $this->validate(
            $this->request,
            [
                'base64' => 'required_without:file',
                'file' => 'required_without:base64',
                'sessionKey' => 'required_with:albumId',
            ]
        );

        $albumId = null;

        // Validating albumId later because we want to ensure the session is setup first
        if ($albumId = $this->request->input('albumId')) {
            $this->validate(
                $this->request,
                [
                    'albumId' => 'exists:albums,albumId,userId,'.$this->user->getId(),
                ]
            );
        }

        if ($this->request->has('base64')) {
            $picture = $pictureFactory->createFromBase64String($this->request->input('base64'));
        } elseif ($this->request->hasFile('file')) {
            $picture = $pictureFactory->createFromUploadedFile($this->request->file('file'));
        }

        if (empty($picture)) {
            throw new HttpException(400, "Unable to create picture from input.");
        }

        $userId = null;
        $anonymous = false;
        $password = null;

        if ($this->user) {
            $userId = $this->user->userId;
            $anonymous = $this->user->defaultAnonymous;
            $password = $this->user->defaultPassword;
        }

        $image = new Image(
            [
                'albumId' => $albumId,
                'ip' => $this->request->getClientIp(),
                'userId' => $userId,
                'anonymous' => $anonymous,
                'password' => $password,
            ]
        );

        if (!$this->user) {
            $key = $image->generateKey($passwordHasher);
        } else {
            $key = null;
        }

        $imageFile = $fileManager->savePicture($picture);
        $image->setImageFile($imageFile);
        $image->save();

        /** @var Image $image */
        $image = $image->fresh();

        $response = [
            'image' => $image,
            'success' => true,
        ];

        if ($key) {
            $response['imageKey'] = $key;
        }

        return $this->response($response);
    }

    /**
     * @api            {get} /images/{imageId} Get Image Info
     * @apiGroup       Images
     * @apiDescription Get the stored metadata for an image.
     *     <br/>If an image is anonymous the `userId` value will set to `null` unless a valid `sessionKey`
     *     for the owner of the image is given.
     * @apiUse         RequiresViewableImage
     * @apiSuccessExample {json} Success Response
     *     {
     *       "image": {
     *         "annotation": null,
     *         "batchId": null,
     *         "createdAt": "2010-05-03T07:22:35+01:00",
     *         "expiresAt": null,
     *         "image": {
     *           "createdAt": "2010-05-03T07:22:35+01:00",
     *           "directory": "img",
     *           "fileId": 1,
     *           "filename": "4bdec00b43a39.jpg",
     *           "height": 1040,
     *           "optimized": null,
     *           "size": 181,
     *           "updatedAt": null,
     *           "url": "http://img.ctrlv.in/img/4bdec00b43a39.jpg",
     *           "width": 1920
     *         },
     *         "imageId": 1,
     *         "isCropped": false,
     *         "privacy": 0,
     *         "thumbnail": {
     *           "createdAt": null,
     *           "directory": "thumb",
     *           "fileId": 663542,
     *           "filename": "4bdec00b43a39.jpg",
     *           "height": null,
     *           "optimized": false,
     *           "size": null,
     *           "updatedAt": null,
     *           "url": "http://img.ctrlv.in/thumb/4bdec00b43a39.jpg",
     *           "width": null
     *         },
     *         "title": "It's improved since this I promise",
     *         "updatedAt": null,
     *         "url": "http://ctrlv.in/1",
     *         "userId": null,
     *         "views": 1238
     *       }
     *     }
     *
     * @param Image $image
     *
     * @return Response
     */
    public function show(Image $image)
    {
        $this->requireViewableModel($image);

        $imageArray = $image->toArray();

        if ($imageArray['albumId']) {
            $imageArray['album'] = Album::find($imageArray['albumId']);
        }

        if ($imageArray['userId']) {
            $imageArray['user'] = User::find($imageArray['userId']);
        }

        return $this->response(['image' => $imageArray]);
    }

    /**
     * @api            {get} /images/{imageId}/image View an Image
     * @apiGroup       Images
     * @apiDescription Returns an HTTP 302 redirect to the image file.
     * @apiUse         RequiresViewableImage
     * @apiParam {boolean=0,1} [display=0] Display the image now at this URL instead of redirecting.
     *     This can be useful for testing but do not use it in production as it is not cached!
     *
     * @param Image       $image
     * @param FileManager $fileManager
     *
     * @return Response
     */
    public function showImage(Image $image, FileManager $fileManager)
    {
        $this->requireViewableModel($image);

        $imageFile = $image->getImageFile();

        if (!$imageFile) {
            // This is a 500 instead of a 404 because we really should have the image...
            throw new HttpException(500, "Unable to load the file for this image.");
        }

        if (Input::has('display')) {
            $picture = $fileManager->getPictureForImageFile($imageFile);

            return $picture->response();
        }

        return new RedirectResponse($imageFile->getUrl());
    }

    /**
     * @api            {get} /images/{imageId}/thumbnail View a Thumbnail
     * @apiGroup       Images
     * @apiDescription Returns an HTTP 302 redirect to the thumbnail image file. The thumbnail is 200x200px
     * @apiUse         RequiresViewableImage
     *
     * @param Image       $image
     * @param FileManager $fileManager
     *
     * @return Response
     */
    public function showThumbnail(Image $image, FileManager $fileManager)
    {
        $this->requireViewableModel($image);

        $thumbnailImageFile = $image->getThumbnailImageFile();

        if (!$thumbnailImageFile) {
            throw new NotFoundHttpException("There is no thumbnail for this image.");
        }

        if (Input::has('display')) {
            $picture = $fileManager->getPictureForImageFile($thumbnailImageFile);

            return $picture->response();
        }

        return new RedirectResponse($thumbnailImageFile->getUrl());
    }

    /**
     * @api            {put} /images/{imageId} Update Image Info
     * @apiGroup       Images
     * @apiDescription Update the stored metadata for an image.
     * @apiParam {string} [title] Title for the image. Give a blank value to clear.
     * @apiParam {boolean=0,1} [anonymous=0] Hide the name of the uploader? (Requires authentication)
     * @apiParam {string=""} [password] Password that will be needed to view the image. Give a blank value to clear.
     *      (Requires authentication)
     * @apiParam {int} [albumId] An album that the image should be moved to. Give a blank value to remove from album.
     *      (Requires authentication)
     * @apiUse         RequiresEditableImage
     * @apiUse         ImageSuccessResponse
     *
     * @param Image          $image
     * @param PasswordHasher $passwordHasher
     *
     * @return Response
     */
    public function update(Image $image, PasswordHasher $passwordHasher)
    {
        $this->requireEditableImage($image);

        $this->validate(
            $this->request,
            [
                'title' => 'max:10',
                'anonymous' => 'boolean',
                'password' => '',
                'sessionKey' => 'required_with:anonymous,password,albumId',
            ]
        );

        if ($this->request->exists('albumId')) {
            if ($albumId = $this->request->input('albumId')) {
                $this->validate(
                    $this->request,
                    [
                        'albumId' => 'exists:albums,albumId,userId,'.$this->user->getId(),
                    ]
                );
                $image->albumId = $albumId;
            } else {
                $image->albumId = null;
            }
        }

        if ($this->request->exists('title')) {
            $image->title = $this->request->input('title');
        }

        if ($this->request->exists('anonymous')) {
            $image->anonymous = (bool)$this->request->input('anonymous');
        }

        if ($this->request->exists('password')) {
            if ($password = $this->request->input('password')) {
                $image->password = $passwordHasher->generateHash($password);
            } else {
                $image->password = null;
            }
        }

        $success = $image->isDirty() ? $image->save() : false;

        return $this->response(['success' => $success, 'image' => $image->fresh()]);
    }

    /**
     * @api            {delete} /images/{imageId} Delete an Image
     * @apiGroup       Images
     * @apiDescription Delete an image.
     * @apiUse         RequiresEditableImage
     * @apiUse         GenericSuccessResponse
     *
     * @param Image $image
     *
     * @return Response
     */
    public function destroy(Image $image)
    {
        $this->requireEditableImage($image);

        return $this->response(['success' => $image->delete()]);
    }

    /**
     * @param Image       $image
     * @param FileManager $fileManager
     *
     * @return Response
     */
    public function updateImage(Image $image, FileManager $fileManager)
    {
        $this->requireEditableImage($image);

        $this->validate(
            $this->request,
            [
                'action' => 'required|string|in:annotate,crop,revert,rotate',
            ]
        );

        switch ($this->request->input('action')) {
            case 'annotate':
                $pictureFactory = app('PictureFactory');

                return $this->annotate($image, $fileManager, $pictureFactory);
                break;
            case 'crop':
                return $this->crop($image, $fileManager);
                break;
            case 'revert':
                return $this->revert($image, $fileManager);
                break;
            case 'rotate':
                return $this->rotate($image, $fileManager);
                break;
        }

        throw new HttpException(400);
    }

    /**
     * @api            {post} /images/{imageId}/image?action=annotate Create an Annotation
     * @apiGroup       Manipulating Images
     * @apiDescription Superimpose the given image (a base64 encoded string) on top of the existing image.
     * @apiParam {string} base64 Base64 encoded image to use as the annotation. This does not have to have the same
     *     dimensions as the image, but it must be the same ratio. It will be resized to the size of the image.
     * @apiUse         RequiresEditableImage
     * @apiUse         ImageSuccessResponse
     *
     * @param Image          $image
     * @param FileManager    $fileManager
     * @param PictureFactory $pictureFactory
     *
     * @throws \Exception
     * @return Response
     */
    protected function annotate(
        Image $image,
        FileManager $fileManager,
        PictureFactory $pictureFactory
    ) {
        $this->requireEditableImage($image);

        $this->validate(
            $this->request,
            [
                'base64' => 'required|string',
            ]
        );

        if ($image->operationInProgress) {
            throw new HttpException(409, "Another operation is currently in progress for this image.");
        }
        $image->operationInProgress = true;
        $image->save();

        $originalImageFile = $image->getImageFile();
        $annotationPicture = $pictureFactory->createFromBase64String($this->request->input('base64'));

        // Make sure the annotation can be resized to the image's size nicely
        $annotationRatio = round($annotationPicture->width() / $annotationPicture->height(), 2);
        $imageRatio = round($originalImageFile->width / $originalImageFile->height, 2);
        if ($annotationRatio !== $imageRatio) {
            throw new HttpException(
                422,
                "The annotation's ratio ({$annotationRatio}) is not the same as the image's ratio ({$imageRatio})."
            );
        }

        $originalPicture = $fileManager->getPictureForImageFile($originalImageFile);
        $image->moveOriginalFile($fileManager);

        // Resize annotation to the size of the image
        $annotationPicture->resize($originalImageFile->width, $originalImageFile->height);

        // Add the annotation on top of the original
        $originalPicture->insert($annotationPicture);

        $newFile = $fileManager->savePicture(
            $originalPicture,
            FileManager::IMAGE_DIR,
            null,
            $image->getUncroppedImageFile()
        );

        $image->setImageFile($newFile);
        $image->operationInProgress = false;
        $success = $image->save();

        return $this->response(['success' => $success, 'image' => $image->fresh()]);
    }

    /**
     * @api            {put} /images/{imageId}/image?action=crop Crop an Image
     * @apiGroup       Manipulating Images
     * @apiDescription Crops out a portion of the image.
     *     See [Uncrop An Image](#api-Manipulating_Images-DeleteImagesIdCrop) to undo.
     *     If the image is already cropped an error will be returned.
     * @apiParam {string=crop} action Action to perform.
     * @apiParam {int} width Width of the rectangular cutout.
     * @apiParam {int} height Height of the rectangular cutout.
     * @apiParam {int} [x=0] X-Coordinate of the top-left corner of the rectangular cutout.
     * @apiParam {int} [y=0] Y-Coordinate of the top-left corner of the rectangular cutout.
     * @apiUse         RequiresEditableImage
     * @apiUse         ImageSuccessResponse
     *
     * @param Image       $image
     * @param FileManager $fileManager
     *
     * @throws Exception
     * @return Response
     */
    protected function crop(Image $image, FileManager $fileManager)
    {
        $this->requireEditableImage($image);

        /*if ($image->hasOriginal()) {
            throw new HttpException(400, "Image is already cropped.");
        }*/

        $originalImageFile = $image->getImageFile();

        // TODO: width needs to be less than original width - x.
        $this->validate(
            $this->request,
            [
                'width' => 'required|integer|between:1,'.$originalImageFile->width,
                'height' => 'required|integer|between:1,'.$originalImageFile->height,
                'x' => 'integer|between:0,'.($originalImageFile->width - 1),
                'y' => 'integer|between:0,'.($originalImageFile->height - 1),
            ]
        );

        if ($image->operationInProgress) {
            throw new HttpException(409, "Another operation is currently in progress for this image.");
        }
        $image->operationInProgress = true;
        $image->save();

        $picture = $fileManager->getPictureForImageFile($originalImageFile);
        $image->moveOriginalFile($fileManager);

        $width = $this->request->input('width');
        $height = $this->request->input('height');

        $xPos = $this->request->has('x') ? (int)$this->request->input('x') : 0;
        $yPos = $this->request->has('y') ? (int)$this->request->input('y') : 0;

        $picture->crop($width, $height, $xPos, $yPos);

        $newFile = $fileManager->savePicture($picture, FileManager::IMAGE_DIR, null, $image->getUncroppedImageFile());

        $image->setImageFile($newFile);
        $image->operationInProgress = false;
        $success = $image->save();

        return $this->response(['success' => $success, 'image' => $image->fresh()]);
    }

    /**
     * @api            {put} /images/{imageId}/image?action=revert Revert an Image
     * @apiGroup       Manipulating Images
     * @apiDescription Revert the changes made by a prior crop/annotate operation.
     *     Returns an error if the image is not altered.
     *     This does not undo rotation - you can rotate it back to the original orientation.
     * @apiParam {string=revert} action Action to perform.
     * @apiUse         RequiresEditableImage
     * @apiUse         ImageSuccessResponse
     *
     * @param Image       $image
     * @param FileManager $fileManager
     *
     * @return Response
     */
    protected function revert(Image $image, FileManager $fileManager)
    {
        $this->requireEditableImage($image);

        if ($image->operationInProgress) {
            throw new HttpException(409, "Another operation is currently in progress for this image.");
        }
        $image->operationInProgress = true;
        $image->save();

        if (!$image->hasOriginal()) {
            throw new HttpException(400, "Image is not altered.");
        }

        $originalImageFile = $image->getUncroppedImageFile();
        if (!$originalImageFile) {
            throw new HttpException(500, "Unable to find original image.");
        }

        // Copy back uncropped image
        $fileManager->moveFile($originalImageFile, FileManager::IMAGE_DIR);

        $image->setImageFile($originalImageFile);
        $image->setUncroppedImageFile(null);
        $image->operationInProgress = false;
        $success = $image->save();

        return $this->response(['success' => $success, 'image' => $image->fresh()]);
    }

    /**
     * @api            {put} /images/{imageId}/image?action=rotate Rotate an Image
     * @apiGroup       Manipulating Images
     * @apiDescription Rotate an image clockwise or counter-clockwise.
     * @apiParam {string=rotate} action Action to perform.
     * @apiParam {int=90,180,270} degrees Degrees to rotate by.
     * @apiParam {string=cw,ccw} [direction=cw] Direction to rotate in (clockwise or counter-clockwise respectively).
     * @apiUse         RequiresEditableImage
     * @apiUse         ImageSuccessResponse
     *
     * @param Image       $image
     * @param FileManager $fileManager
     *
     * @throws Exception
     * @return Response
     */
    protected function rotate(Image $image, FileManager $fileManager)
    {
        $success = false;
        $this->validate(
            $this->request,
            [
                'degrees' => 'required|integer|in:90,180,270',
                'direction' => 'in:cw,ccw',
            ]
        );

        if ($image->operationInProgress) {
            throw new HttpException(409, "Another operation is currently in progress for this image.");
        }
        $image->operationInProgress = true;
        $image->save();

        $degrees = (int)$this->request->input('degrees');
        $direction = $this->request->has('direction') ? $this->request->input('direction') : 'cw';

        if ($direction === 'cw') {
            $degrees = -$degrees;
        }

        $picture = $fileManager->getPictureForImageFile($image->imageFile);

        $picture->rotate($degrees);

        if ($newImageFile = $fileManager->savePicture($picture)) {
            $image->setImageFile($newImageFile);
            $image->operationInProgress = false;
            $success = $image->save();
        }

        return $this->response(['success' => $success, 'image' => $image->fresh()]);
    }
}
