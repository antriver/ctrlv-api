<?php

namespace CtrlV\Http\Controllers;

use App;
use Config;
use Illuminate\Auth\Guard;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Http\RedirectResponse;
use Input;
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
 *                   "image": [...] // See Get Image Info
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
class ImagesController extends Base\ApiController
{
    /**
     * @api            {post} /images Create an Image
     * @apiGroup       Images
     * @apiDescription Store an image and return its metadata and a URL to view it. The returned `image` object
     *     contains an additional property named `key` that can be used to manipulate the image in future requests.
     *                 <br/>The default privacy setting for new images is 0
     *                 <b>or</b> the user's `defaultPrivacy` value if `sessionKey` is given.
     *                 (See "Get an Image" for privacy options).
     * @apiParam {string} base64 Base64 encoded image to upload.
     * @apiParam {string} [sessionKey] A session key for a user the image should be attributed to.
     * @apiUse         ImageSuccessResponse
     *
     * @param Request $request
     * @param PictureFactory $pictureFactory
     * @param Guard $auth
     *
     * @throws \Exception
     * @return Response
     */
    public function store(Request $request, PictureFactory $pictureFactory, Guard $auth)
    {
        if ($request->has('base64')) {
            $picture = $pictureFactory->createFromBase64String($request->input('base64'));
        } elseif ($request->hasFile('file')) {
            $picture = $pictureFactory->createFromUploadedFile($request->file('file'));
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

        return $this->successResponse(
            [
                'image' => $imageArray,
                'success' => true,
            ]
        );
    }

    /**
     * @api            {get} /images/{id} Get Image Info
     * @apiGroup       Images
     * @apiDescription Get the stored metadata for an image.
     *                 <br/><br/><strong>Privacy Settings</strong>
     *                 <br/>`0` = publicly visible and the name of the user is displayed.
     *                 <br/>`1` = publicly visible but the name of the user is <b>not</b> displayed.
     *                 <br/>`2` = password required to view and the name of the user is <b>not</b> displayed.
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
        $this->requireViewableImage($image);
        return $this->successResponse(['image' => $image]);
    }

    /**
     * @api            {get} /images/{id}/image View an Image
     * @apiGroup       Images
     * @apiDescription Returns an HTTP 302 redirect to the image file.
     * @apiUse         RequiresViewableImage
     *
     * @param ConfigRepository $config
     * @param Image $image
     *
     * @return Response
     */
    public function view(ConfigRepository $config, Image $image)
    {
        $this->requireViewableImage($image);

        // Display the image right now instead of redirecting to the correct URL
        // This is good for testing but bad for production
        if (Input::has('display')) {
            return $image->getPicture()->response();
        }

        return new RedirectResponse($config->get('app.data_url') . 'img/' . $image->filename);
    }

    /**
     * @api            {get} /images/{id}/thumbnail View a Thumbnail
     * @apiGroup       Images
     * @apiDescription Returns an HTTP 302 redirect to the thumbnail image file. The thumbnail is 200x200px
     * @apiUse         RequiresViewableImage
     *
     * @param ConfigRepository $config
     * @param Image $image
     *
     * @return Response
     */
    public function viewThumbnail(ConfigRepository $config, Image $image)
    {
        $this->requireViewableImage($image);

        if (!$image->thumb) {
            return $this->error('There is no thumbnail for this image.', 404);
        }

        return new RedirectResponse($config->get('app.data_url') . 'thumb/' . $image->filename);
    }

    /**
     * @api            {put} /images/{id} Update Image Info
     * @apiGroup       Images
     * @apiDescription Update the stored metadata for an image.
     * @apiParam {string} [caption] Caption for the image. Send an empty string to remove the caption.
     * @apiParam {int=0,1,2} [privacy] New privacy setting. See "Create an Image".
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
        $this->requireEditableImage($image);

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

        return $this->successResponse(['success' => $success, 'image' => $image->fresh()]);
    }

    /**
     * @param Request $request
     * @param Image $image
     *
     * @return Response
     */
    public function updateImage(Request $request, Image $image)
    {
        $this->requireEditableImage($image);

        $this->validate(
            $request,
            [
                'action' => 'required|string|in:rotate'
            ]
        );

        switch ($request->input('action')) {
            case 'rotate':
                return $this->rotate($request, $image);
                break;
        }

        throw new HttpException(400);
    }

    /**
     * @api            {put} /images/{id}/image Rotate an Image
     * @apiGroup       Manipulating Images
     * @apiDescription Rotate an image clockwise or counter-clockwise.
     * @apiParam {string=rotate} action Action to perform.
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

        return $this->successResponse(['success' => $success, 'image' => $image->fresh()]);
    }

    /**
     * @api            {post} /images/{id}/crop Crop an Image
     * @apiGroup       Manipulating Images
     * @apiDescription Crops out a portion of the image.
     *      See [Uncrop An Image](#api-Manipulating_Images-DeleteImagesIdCrop) to undo.
     *      If the image is already cropped an error will be returned.
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
        $this->requireEditableImage($image);

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

        return $this->successResponse(['success' => $success, 'image' => $image->fresh()]);
    }

    /**
     * @api            {delete} /images/{id}/crop Uncrop an Image
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
        $this->requireEditableImage($image);

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

        return $this->successResponse(['success' => $success, 'image' => $image->fresh()]);
    }

    /**
     * @api            {delete} /images/{id} Delete an Image
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
        $this->requireEditableImage($image);
        return $this->successResponse(['success' => $image->delete()]);
    }
}
