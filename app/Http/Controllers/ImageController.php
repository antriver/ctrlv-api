<?php

namespace CtrlV\Http\Controllers;

use App;
use Auth;
use Config;
use Exception;
use Input;
use Response;
use Validator;
use CtrlV\Http\Requests;
use CtrlV\Http\Controllers\Base\ApiController;
use CtrlV\Models\ImageModel;
use CtrlV\Repositories\FileRepository;
use CtrlV\Factories\ImageFactory;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @apiDefine ImageSuccessResponse
 * @apiSuccessExample {json} Success Response
 *               {
 *                   "success": true,
 *                   "image": [...]
 *               }
 */

class ImageController extends ApiController
{
    /**
     * Ensure that the given ImageModel is viewable by the current visitor
     * @param  ImageModel $imageModel
     * @throws HttpException
     * @return boolean
     */
    private function requireViewableImageModel(ImageModel $imageModel)
    {
        if (!$imageModel->isViewable(Input::get('password'))) {
            App::abort(403, "You don't have permission to view that image.");
            return false;
        }
        return true;
    }

    /**
     * Ensure that the given ImageModel is editable by the current visitor
     * @param  ImageModel $imageModel
     * @throws HttpException
     * @return boolean
     */
    private function requireEditableImageModel(ImageModel $imageModel)
    {
        if (!$imageModel->isEditable(Input::get('key'))) {
            App::abort(403, "You don't have permission to modify that image.");
            return false;
        }
        return true;
    }

    /**
     * Create a new image
     *
     * @api {post} /image Create an Image
     * @apiGroup Images
     * @apiParam {string} base64 Base64 encoded image to upload.
     * @apiUse ImageSuccessResponse
     *
     * @param  Request  $request
     * @param  ImageFactory $imageFactory
     * @return Response
     */
    public function store(Request $request, ImageFactory $imageFactory)
    {
        if ($request->has('base64')) {
            $image = $imageFactory->createFromBase64String($request->input('base64'));

        } elseif ($request->hasFile('file')) {
            $image = $imageFactory->createFromUploadedFile($request->file('file'));

        } else {
            throw new HttpException(400, 'Please provide a base64 image or uploaded file');

        }

        if (empty($image)) {
            App::abort(500);
        }

        $userID = Auth::check() ? Auth::user()->userID : null;

        $imageModel = new ImageModel([
            'IP' => $request->ip(),
            'userID' => $userID,
        ]);

        $imageModel->saveWithNewImage($image, false);

        $imageModel = $imageModel->fresh();

        return Response::json([
            'image' => $imageModel,
            'key' => $imageModel->key,
            'success' => true,
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @api {get} /image/{id} Get an Image
     * @apiGroup Images
     * @apiDescription Get the stored metadata for an image.
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
     * @param  ImageModel  $imageModel
     * @return Response
     */
    public function show(ImageModel $imageModel)
    {
        $this->requireViewableImageModel($imageModel);
        return Response::json(['image' => $imageModel]);
    }

    /**
     * @api {get} /image/{id}/image View an Image
     * @apiGroup Images
     * @apiDescription Returns an HTTP 302 redirect to the image file.
     *
     * @param  ImageModel  $imageModel
     * @return Response
     */
    public function view(ImageModel $imageModel)
    {
        $this->requireViewableImageModel($imageModel);

        // Display the image right now instead of redirecting to the correct URL
        // This is good for testing but bad for production
        if (Input::has('display')) {
            return $imageModel->getImage()->response();
        }

        return redirect(Config::get('app.data_url') . 'img/' . $imageModel->filename);
    }

    /**
     * @api {get} /image/{id}/thumbnail View a Thumbnail
     * @apiGroup Images
     * @apiDescription Returns an HTTP 302 redirect to the thumbnail image file.
     *
     * @param  ImageModel  $imageModel
     * @return Response
     */
    public function viewThumbnail(ImageModel $imageModel)
    {
        $this->requireViewableImageModel($imageModel);

        if (!$imageModel->thumb) {
            return $this->error('There is no thumbnail for this image.', 404);
        }

        return redirect(Config::get('app.data_url') . 'thumb/' . $imageModel->filename);

    }

    /**
     * Display the specified resource.
     *
     * @api {get} /image/{id}/annotation View an Annotation
     * @apiGroup Image Annotations
     * @apiDescription Returns an HTTP 302 redirect to the annotation image file.
     *
     * @param  ImageModel  $imageModel
     * @return Response
     */
    public function viewAnnotation(ImageModel $imageModel)
    {
        $this->requireViewableImageModel($imageModel);

        if (!$imageModel->annotation) {
            return $this->error('There is no annotation for this image.', 404);
        }

        return redirect(Config::get('app.data_url') . 'annotation/' . $imageModel->annotation);

    }

    /**
     * Update the specified resource in storage.
     *
     * @api {put} /image/{id} Update an Image
     * @apiGroup Images
     * @apiDescription Update the stored metadata for an image.
     * @apiParam {string} [caption] Caption for the image. Send an empty string to remove the caption.
     * @apiParam {int=0,1,2} [privacy] Privacy setting.
     * @apiParam {string} [password] (Required if privacy is `2`) Password required to view the image.
     * @apiUse ImageSuccessResponse
     *
     * @param  Request  $request
     * @param  ImageModel  $imageModel
     * @return Response
     */
    public function update(Request $request, ImageModel $imageModel)
    {
        $this->requireEditableImageModel($imageModel);

        $this->validate($request, [
            'caption' => 'max:10',
            'privacy' => 'integer|between:0,2',
            'password' => 'required_if:privacy,2'
        ]);

        if ($request->exists('caption')) {
            $imageModel->caption = $request->input('caption');
        }

        if ($request->exists('privacy')) {
            $imageModel->privacy = $request->input('privacy');
        }

        if ($request->exists('password')) {
            $imageModel->password = md5($request->input('password'));
        }

        $success = $imageModel->isDirty() ? $imageModel->save() : false;

        return Response::json(['success' => $success, 'image' => $imageModel->fresh()]);
    }

    /**
     * POST /image/123/rotate
     * Rotate the image.
     *
     * @api {post} /image/{id}/rotate Rotate an Image
     * @apiGroup Manipulating Images
     * @apiDescription Rotate an image clockwise or counter-clockwise.
     * @apiParam {int=90,180,270} degrees Degrees to rotate by.
     * @apiParam {string=cw,ccw} [direction=cw] Direction to rotate in (clockwise or counter-clockwise respectively).
     * @apiUse ImageSuccessResponse
     *
     * @param  Request  $request
     * @param  ImageModel  $imageModel
     * @return Response
     */
    public function rotate(Request $request, ImageModel $imageModel)
    {
        $this->requireEditableImageModel($imageModel);

        $this->validate($request, [
            'degrees' => 'required|integer|in:90,180,270',
            'direction' => 'in:cw,ccw'
        ]);

        $degrees = (int)$request->input('degrees');
        $direction = $request->has('direction') ? $request->input('direction') : 'cw';

        if ($direction === 'cw') {
            $degrees = -$degrees;
        }

        $image = $imageModel->getImage();

        $image->rotate($degrees);

        $success = $imageModel->saveWithNewImage($image);

        return Response::json(['success' => $success, 'image' => $imageModel->fresh()]);
    }

    /**
     * POST /image/123/crop
     * Crop the image.
     *
     * @api {post} /image/{id}/crop Crop an Image
     * @apiGroup Manipulating Images
     * @apiDescription Crops out a portion of the image. After cropping, you can use the `uncrop` endpoint to undo.
     *     If the image is already cropped an error will be returned.
     * @apiParam {int} width Width of the rectangular cutout.
     * @apiParam {int} height Height of the rectangular cutout.
     * @apiParam {int} [x=0] X-Coordinate of the top-left corner of the rectangular cutout.
     * @apiParam {int} [y=0] Y-Coordinate of the top-left corner of the rectangular cutout.
     * @apiUse ImageSuccessResponse
     *
     * @param  Request  $request
     * @param  ImageModel  $imageModel
     * @param  FileRepository $fileRepository
     * @return Response
     */
    public function crop(Request $request, ImageModel $imageModel, FileRepository $fileRepository)
    {
        $this->requireEditableImageModel($imageModel);

        if ($imageModel->uncroppedfilename) {
            return $this->error('Image is already cropped.');
        }

        $this->validate($request, [
            'width' => 'required|integer|between:1,' . $imageModel->w,
            'height' => 'required|integer|between:1,' . $imageModel->h,
            'x' => 'integer|between:0,' . $imageModel->w,
            'y' => 'integer|between:0,' . $imageModel->h,
        ]);

        // Backup uncropped image
        $fileRepository->renameFile('img/' . $imageModel->filename, 'uncropped/' . $imageModel->filename);
        $imageModel->uncroppedfilename = $imageModel->filename;

        $image = $imageModel->getImage();

        $width = $request->input('width');
        $height = $request->input('height');

        $xPos = $request->has('x') ? (int)$request->input('x') : 0;
        $yPos = $request->has('y') ? (int)$request->input('y') : 0;

        $image->crop($width, $height, $xPos, $yPos);

        $success = $imageModel->saveWithNewImage($image);

        return Response::json(['success' => $success, 'image' => $imageModel->fresh()]);
    }

    /**
     * POST /image/123/uncrop
     * Uncrop the image.
     *
     * @api {post} /image/{id}/uncrop Uncrop an Image
     * @apiGroup Manipulating Images
     * @apiDescription Revert the changes made by a prior crop operation. Returns an error if the image is not cropped.
     * @apiUse ImageSuccessResponse
     *
     * @param  ImageModel  $imageModel
     * @param  FileRepository $fileRepository
     * @return Response
     */
    public function uncrop(ImageModel $imageModel, FileRepository $fileRepository)
    {
        $this->requireEditableImageModel($imageModel);

        if (!$imageModel->uncroppedfilename) {
            return $this->error('Image is not cropped.');
        }

        $uncroppedImage = $fileRepository->getImage('uncropped/' . $imageModel->uncroppedfilename);

        // Copy back uncropped image
        $fileRepository->renameFile('uncropped/' . $imageModel->uncroppedfilename, 'img/' . $imageModel->uncroppedfilename);

        $imageModel->setImageMetadata($uncroppedImage);
        $imageModel->filename = $imageModel->uncroppedfilename;
        $imageModel->uncroppedfilename = null;

        $success = $imageModel->save();
        // ImageModel will take care of deleting the old image

        return Response::json(['success' => $success, 'image' => $imageModel->fresh()]);
    }

    /**
     * POST /image/123/annotation
     * Add an annotation to the image.
     *
     * @api {post} /image/{id}/annotation Add an Annotation
     * @apiGroup Image Annotations
     * @apiDescription Add an annotation to an image. This will replace an existing annotation if it exists.
     * @apiParam {string} base64 Base64 encoded image to use as the annotation. This does not have to have the same
     *     dimensions as the image, but it must be the same ratio. It will be resized to the size of the image.
     * @apiUse ImageSuccessResponse
     *
     * @param  Request  $request
     * @param  ImageModel  $imageModel
     * @param  ImageFactory $imageFactory
     * @param  FileRepository $fileRepository
     * @return Response
     */
    public function storeAnnotation(Request $request, ImageModel $imageModel, ImageFactory $imageFactory, FileRepository $fileRepository)
    {
        $this->requireEditableImageModel($imageModel);

        $this->validate($request, [
            'base64' => 'required|string'
        ]);

        $annotationImage = $imageFactory->createFromBase64String($request->input('base64'));

        // Make sure the annotation can be resized to the image's size nicely
        $annotationRatio = round($annotationImage->width() / $annotationImage->height(), 2);
        $imageRatio = round($imageModel->w / $imageModel->h, 2);
        if ($annotationRatio !== $imageRatio) {
            App::abort(422, "The annotation's ratio ({$annotationRatio}) is not the same as the image's ratio ({$imageRatio}).");
        }

        // Resize annotation to the size of the image
        $annotationImage->resize($imageModel->w, $imageModel->h);

        $annotationFilename = $fileRepository->saveImage($annotationImage, 'annotation');

        $imageModel->annotation = $annotationFilename;

        $success = $imageModel->save();

        return Response::json(['success' => $success, 'image' => $imageModel->fresh()]);
    }

    /**
     * Delete an image's annotation.
     *
     * @api {delete} /image/{id}/annotation Delete an Annotation
     * @apiGroup Image Annotations
     * @apiDescription Delete an image's annotation.
     * @apiUse ImageSuccessResponse
     *
     * @param  ImageModel  $imageModel
     * @param  FileRepository $fileRepository
     * @return Response
     */
    public function destroyAnnotation(ImageModel $imageModel, FileRepository $fileRepository)
    {
        $this->requireEditableImageModel($imageModel);

        if (!$imageModel->annotation) {
            App::abort(400, "This image does not have an annotation.");
        }

        $fileRepository->deleteFile('annotation/' . $imageModel->annotation);

        $imageModel->annotation = null;

        $success = $imageModel->save();

        return Response::json(['success' => $success, 'image' => $imageModel->fresh()]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @api {delete} /image/{id} Delete an Image
     * @apiGroup Images
     * @apiDescription Delete an image.
     * @apiSuccessExample {json} Success Response
     *               {
     *                   "success": true
     *               }
     *
     * @param  ImageModel  $imageModel
     * @return Response
     */
    public function destroy(ImageModel $imageModel)
    {
        $this->requireEditableImageModel($imageModel);
        return Response::json(['success' => $imageModel->delete()]);
    }
}
