<?php

namespace CtrlV\Http\Controllers;

use App;
use Config;
use Illuminate\Auth\Guard;
use Input;
use Response;
use CtrlV\Http\Requests;
use CtrlV\Models\Image;
use CtrlV\Libraries\FileManager;
use CtrlV\Libraries\PictureManager;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @apiDefine ImageSuccessResponse
 * @apiSuccessExample {json} Success Response
 *               {
 *                   "success": true,
 *                   "image": [...] // See: Get An Image
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
class ImageController extends Base\ApiController
{
    /**
     * Ensure that the given Image is viewable by the current visitor
     *
     * @param Image $image
     *
     * @throws HttpException
     * @return boolean
     */
    private function requireViewableImageModel(Image $image)
    {
        if (!$image->isViewable(Input::get('password'))) {
            throw new HttpException(403, "You don't have permission to view that image.");
        }
        return true;
    }

    /**
     * Ensure that the given Image is editable by the current visitor
     *
     * @param Image $image
     *
     * @throws HttpException
     * @return boolean
     */
    private function requireEditableImageModel(Image $image)
    {
        if (!$image->isEditable(Input::get('imageKey'))) {
            throw new HttpException(403, "You don't have permission to modify that image.");
        }
        return true;
    }

    /**
     * @api            {post} /image Create an Image
     * @apiGroup       Images
     * @apiDescription Store an image and return its metadata and a URL to view it. The returned `image` object
     *     contains an additional property named `key` that can be used to manipulate the image in future requests.
     * @apiParam {string} base64 Base64 encoded image to upload.
     * @apiUse         ImageSuccessResponse
     *
     * @param Request $request
     * @param PictureManager $pictureManager
     * @param Guard $auth
     *
     * @throws \Exception
     * @return Response
     */
    public function store(Request $request, PictureManager $pictureManager, Guard $auth)
    {
        if ($request->has('base64')) {
            $picture = $pictureManager->createFromBase64String($request->input('base64'));
        } elseif ($request->hasFile('file')) {
            $picture = $pictureManager->createFromUploadedFile($request->file('file'));
        } else {
            throw new HttpException(400, 'Please provide a base64 image or uploaded file');
        }

        if (empty($picture)) {
            throw new HttpException(500);
        }

        $userId = null;
        if ($auth->check()) {
            /** @var \CtrlV\Models\User $user */
            $user = $auth->user();
            $userId = $user->id;
        }

        $image = new Image(
            [
                'IP' => $request->ip(),
                'userID' => $userId,
            ]
        );

        $image->saveWithNewPicture($picture);

        /** @var Image $image */
        $image = $image->fresh();

        $imageArray = $image->toArray();
        $imageArray['key'] = $image->key;

        return Response::json(
            [
                'image' => $imageArray,
                'success' => true,
            ]
        );
    }

    /**
     * @api            {get} /image/{id} Get an Image
     * @apiGroup       Images
     * @apiDescription Get the stored metadata for an image.
     * @apiUse         RequiresViewableImage
     * @apiSuccessExample {json} Success Response
     * {
     *     "image": {
     *       "batchID": "",
     *       "caption": "",
     *       "date": "2015-08-15 22:40:58",
     *       "expires": null,
     *       "filesize": 394938,
     *       "height": 666,
     *       "imageID": 4,
     *       "ocrtext": "",
     *       "privacy": 0,
     *       "urls": {
     *         "view": "http://ctrlv.in/4",
     *         "image": "http://img.ctrlv.in/img/15/08/15/55cfcad49dd1b.png",
     *         "thumbnail": "http://img.ctrlv.in/thumb/15/08/15/55cfcad49dd1b.png",
     *         "annotation": null
     *       },
     *       "userID": null,
     *       "views": 5,
     *       "width": 493
     *     }
     *   }
     *
     * @param Image $image
     *
     * @return Response
     */
    public function show(Image $image)
    {
        $this->requireViewableImageModel($image);
        return Response::json(['image' => $image]);
    }

    /**
     * @api            {get} /image/{id}/image View an Image
     * @apiGroup       Images
     * @apiDescription Returns an HTTP 302 redirect to the image file.
     * @apiUse         RequiresViewableImage
     *
     * @param Image $image
     *
     * @return Response
     */
    public function view(Image $image)
    {
        $this->requireViewableImageModel($image);

        // Display the image right now instead of redirecting to the correct URL
        // This is good for testing but bad for production
        if (Input::has('display')) {
            return $image->getPicture()->response();
        }

        return redirect(Config::get('app.data_url') . 'img/' . $image->filename);
    }

    /**
     * @api            {get} /image/{id}/thumbnail View a Thumbnail
     * @apiGroup       Images
     * @apiDescription Returns an HTTP 302 redirect to the thumbnail image file. The thumbnail is 200x200px
     * @apiUse         RequiresViewableImage
     *
     * @param Image $image
     *
     * @return Response
     */
    public function viewThumbnail(Image $image)
    {
        $this->requireViewableImageModel($image);

        if (!$image->thumb) {
            return $this->error('There is no thumbnail for this image.', 404);
        }

        return redirect(Config::get('app.data_url') . 'thumb/' . $image->filename);

    }

    /**
     * @api            {get} /image/{id}/annotation View an Annotation
     * @apiGroup       Image Annotations
     * @apiDescription Returns an HTTP 302 redirect to the annotation image file.
     * @apiUse         RequiresViewableImage
     *
     * @param Image $image
     *
     * @return Response
     */
    public function viewAnnotation(Image $image)
    {
        $this->requireViewableImageModel($image);

        if (!$image->annotation) {
            return $this->error('There is no annotation for this image.', 404);
        }

        return redirect(Config::get('app.data_url') . 'annotation/' . $image->annotation);

    }

    /**
     * @api            {put} /image/{id} Update an Image
     * @apiGroup       Images
     * @apiDescription Update the stored metadata for an image.
     * @apiParam {string} [caption] Caption for the image. Send an empty string to remove the caption.
     * @apiParam {int=0,1,2} [privacy] Privacy setting.
     * @apiParam {string} [password] Password that will be needed to view the image.
     *     **Required if `privacy` is given and is `2`.**
     * @apiUse         RequiresEditableImage
     * @apiUse         ImageSuccessResponse
     *
     * @param Request $request
     * @param Image $image
     *
     * @return Response
     */
    public function update(Request $request, Image $image)
    {
        $this->requireEditableImageModel($image);

        $this->validate(
            $request,
            [
                'caption' => 'max:10',
                'privacy' => 'integer|between:0,2',
                'password' => 'required_if:privacy,2'
            ]
        );

        if ($request->exists('caption')) {
            $image->caption = $request->input('caption');
        }

        if ($request->exists('privacy')) {
            $image->privacy = $request->input('privacy');
        }

        if ($request->exists('password')) {
            $image->password = md5($request->input('password'));
        }

        $success = $image->isDirty() ? $image->save() : false;

        return Response::json(['success' => $success, 'image' => $image->fresh()]);
    }

    /**
     * @api            {post} /image/{id}/rotate Rotate an Image
     * @apiGroup       Manipulating Images
     * @apiDescription Rotate an image clockwise or counter-clockwise.
     * @apiParam {int=90,180,270} degrees Degrees to rotate by.
     * @apiParam {string=cw,ccw} [direction=cw] Direction to rotate in (clockwise or counter-clockwise respectively).
     * @apiUse         RequiresEditableImage
     * @apiUse         ImageSuccessResponse
     *
     * @param Request $request
     * @param Image $image
     *
     * @return Response
     */
    public function rotate(Request $request, Image $image)
    {
        $this->requireEditableImageModel($image);

        $this->validate(
            $request,
            [
                'degrees' => 'required|integer|in:90,180,270',
                'direction' => 'in:cw,ccw'
            ]
        );

        $degrees = (int)$request->input('degrees');
        $direction = $request->has('direction') ? $request->input('direction') : 'cw';

        if ($direction === 'cw') {
            $degrees = -$degrees;
        }

        $picture = $image->getPicture();

        $picture->rotate($degrees);

        $success = $image->saveWithNewPicture($picture);

        return Response::json(['success' => $success, 'image' => $image->fresh()]);
    }

    /**
     * @api            {post} /image/{id}/crop Crop an Image
     * @apiGroup       Manipulating Images
     * @apiDescription Crops out a portion of the image. After cropping, you can use the `uncrop` endpoint to undo.
     *     If the image is already cropped an error will be returned.
     * @apiParam {int} width Width of the rectangular cutout.
     * @apiParam {int} height Height of the rectangular cutout.
     * @apiParam {int} [x=0] X-Coordinate of the top-left corner of the rectangular cutout.
     * @apiParam {int} [y=0] Y-Coordinate of the top-left corner of the rectangular cutout.
     * @apiUse         RequiresEditableImage
     * @apiUse         ImageSuccessResponse
     *
     * @param Request $request
     * @param Image $image
     * @param FileManager $fileManager
     *
     * @return Response
     */
    public function crop(Request $request, Image $image, FileManager $fileManager)
    {
        $this->requireEditableImageModel($image);

        if ($image->uncroppedfilename) {
            return $this->error('Image is already cropped.');
        }

        $this->validate(
            $request,
            [
                'width' => 'required|integer|between:1,' . $image->w,
                'height' => 'required|integer|between:1,' . $image->h,
                'x' => 'integer|between:0,' . $image->w,
                'y' => 'integer|between:0,' . $image->h,
            ]
        );

        // Backup uncropped image
        $fileManager->renameFile('img/' . $image->filename, 'uncropped/' . $image->filename);
        $image->uncroppedfilename = $image->filename;

        $picture = $image->getPicture();

        $width = $request->input('width');
        $height = $request->input('height');

        $xPos = $request->has('x') ? (int)$request->input('x') : 0;
        $yPos = $request->has('y') ? (int)$request->input('y') : 0;

        $picture->crop($width, $height, $xPos, $yPos);

        $success = $image->saveWithNewPicture($picture);

        return Response::json(['success' => $success, 'image' => $image->fresh()]);
    }

    /**
     * @api            {post} /image/{id}/uncrop Uncrop an Image
     * @apiGroup       Manipulating Images
     * @apiDescription Revert the changes made by a prior crop operation. Returns an error if the image is not cropped.
     * @apiUse         RequiresEditableImage
     * @apiUse         ImageSuccessResponse
     *
     * @param Image $image
     * @param FileManager $fileManager
     *
     * @return Response
     */
    public function uncrop(Image $image, FileManager $fileManager)
    {
        $this->requireEditableImageModel($image);

        if (!$image->uncroppedfilename) {
            return $this->error('Image is not cropped.');
        }

        $uncroppedPicture = $fileManager->getPicture('uncropped/' . $image->uncroppedfilename);

        // Copy back uncropped image
        $fileManager->renameFile(
            'uncropped/' . $image->uncroppedfilename,
            'img/' . $image->uncroppedfilename
        );

        $image->setMetadataFromPicture($uncroppedPicture);
        $image->filename = $image->uncroppedfilename;
        $image->uncroppedfilename = null;

        $success = $image->save();
        // Image will take care of deleting the old image. Should it though?

        return Response::json(['success' => $success, 'image' => $image->fresh()]);
    }

    /**
     * @api            {post} /image/{id}/annotation Add an Annotation
     * @apiGroup       Image Annotations
     * @apiDescription Add an annotation to an image. This will replace an existing annotation if it exists.
     * @apiParam {string} base64 Base64 encoded image to use as the annotation. This does not have to have the same
     *     dimensions as the image, but it must be the same ratio. It will be resized to the size of the image.
     * @apiUse         RequiresEditableImage
     * @apiUse         ImageSuccessResponse
     *
     * @param Request $request
     * @param Image $image
     * @param PictureManager $pictureManager
     * @param FileManager $fileManager
     *
     * @return Response
     */
    public function storeAnnotation(
        Request $request,
        Image $image,
        PictureManager $pictureManager,
        FileManager $fileManager
    ) {
        $this->requireEditableImageModel($image);

        $this->validate(
            $request,
            [
                'base64' => 'required|string'
            ]
        );

        $annotationPicture = $pictureManager->createFromBase64String($request->input('base64'));

        // Make sure the annotation can be resized to the image's size nicely
        $annotationRatio = round($annotationPicture->width() / $annotationPicture->height(), 2);
        $imageRatio = round($image->w / $image->h, 2);
        if ($annotationRatio !== $imageRatio) {
            App::abort(
                422,
                "The annotation's ratio ({$annotationRatio}) is not the same as the image's ratio ({$imageRatio})."
            );
        }

        // Resize annotation to the size of the image
        $annotationPicture->resize($image->w, $image->h);

        $annotationFilename = $fileManager->savePicture($annotationPicture, 'annotation');

        $image->annotation = $annotationFilename;

        $success = $image->save();

        return Response::json(['success' => $success, 'image' => $image->fresh()]);
    }

    /**
     * @api            {delete} /image/{id}/annotation Delete an Annotation
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
    public function destroyAnnotation(Image $image, FileManager $fileManager)
    {
        $this->requireEditableImageModel($image);

        if (!$image->annotation) {
            App::abort(400, "This image does not have an annotation.");
        }

        $fileManager->deleteFile('annotation/' . $image->annotation);

        $image->annotation = null;

        $success = $image->save();

        return Response::json(['success' => $success, 'image' => $image->fresh()]);
    }

    /**
     * @api            {delete} /image/{id} Delete an Image
     * @apiGroup       Images
     * @apiDescription Delete an image.
     * @apiUse         RequiresEditableImage
     * @apiSuccessExample {json} Success Response
     *               {
     *                   "success": true
     *               }
     *
     * @param Image $image
     *
     * @return Response
     */
    public function destroy(Image $image)
    {
        $this->requireEditableImageModel($image);
        return Response::json(['success' => $image->delete()]);
    }
}
