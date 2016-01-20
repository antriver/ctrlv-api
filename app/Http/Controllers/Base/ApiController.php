<?php

namespace CtrlV\Http\Controllers\Base;

use App;
use CtrlV\Models\Album;
use CtrlV\Models\Image;
use CtrlV\Models\User;
use CtrlV\Models\UserSession;
use Illuminate\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @apiDefine GenericSuccessResponse
 * @apiSuccessExample {json} Success Response
 *     {
 *       "success": true
 *     }
 */

/**
 * @apiDefine ImageSuccessResponse
 * @apiSuccessExample {json} Success Response
 *     {
 *       "success": true,
 *       "image": {
 *         // See Get Image Info
 *       }
 *     }
 */

/**
 * @apiDefine         PaginatedAlbumResponse
 * @apiSuccessExample Success Response
 *     {
 *       "total": 2,
 *       "perPage": 20,
 *       "currentPage": 1,
 *       "lastPage": 1,
 *       "nextPageUrl": null,
 *       "prevPageUrl": null,
 *       "from": 1,
 *       "to": 2,
 *       "albums": [
 *         {
 *           "albumId": 1,
 *           "createdAt": "2015-11-28T19:49:12+00:00",
 *           "privacy": 0,
 *           "title": "My Pics",
 *           "updatedAt": "2015-11-28T19:49:12+00:00",
 *           "url": "http://ctrlv.in/album/1",
 *           "userId": 1
 *         },
 *         {
 *           "albumId": 2,
 *           "createdAt": "2015-11-28T19:49:17+00:00",
 *           "privacy": 0,
 *           "title": "Screenshots",
 *           "updatedAt": "2015-11-28T19:49:17+00:00",
 *           "url": "http://ctrlv.in/album/2",
 *           "userId": 1
 *         }
 *       ]
 *     }
 */

/**
 * @apiDefine         PaginatedImageResponse
 * @apiSuccessExample Success Response
 *     {
 *       "total": 60,
 *       "perPage": 20,
 *       "currentPage": 1,
 *       "lastPage": 3,
 *       "nextPageUrl": "http://api.ctrlv.in/users/anthony/images/?page=2",
 *       "prevPageUrl": null,
 *       "from": 1,
 *       "to": 20,
 *       "images": [
 *         {
 *           // See Get Image Info
 *         },
 *         {
 *           // See Get Image Info
 *         },
 *         // ...
 *       ]
 *     }
 */

/**
 * @apiDefine RequiresAuthentication
 * @apiParam {string} sessionKey This endpoint requires authentication so a session key must be provided.
 */

/**
 * @apiDefine RequiresViewableImage
 * @apiParam {string} [sessionKey] Session key for the user that owns the image.
 *     **Either `sessionKey` or `password` is required if the image is password protected.**
 * @apiParam {string} [password] Password to view the image.
 *     **Either `sessionKey` or `password` is required if the image is password protected.**
 */

/**
 * @apiDefine RequiresEditableImage
 * @apiParam {string} sessionKey Session key for the user that owns the image.
 *     **Either `sessionKey` or `imageKey` is required.**
 * @apiParam {string} imageKey Editing key for the image (obtained when the image is created).
 *     **Either `sessionKey` or `imageKey` is required.**
 */
abstract class ApiController extends BaseController
{
    protected $resultsPerPage = 15;

    public function __construct(Request $request, Guard $auth)
    {
        // TODO: Add API keys and check here

        if ($sessionKey = $request->get('sessionKey')) {
            /** @var UserSession $session */
            $session = UserSession::find($sessionKey);

            if (!$session) {
                throw new NotFoundHttpException("The given sessionKey is invalid.");
            }

            if (!$session->user) {
                throw new NotFoundHttpException("The user for that session could not be found.");
            }

            // Login the user just for this request.
            $auth->setUser($session->user);
        }

        parent::__construct($request, $auth);
    }

    /**
     * @param mixed $data
     *
     * @return Response
     */
    protected function response($data)
    {
        return Response::json($data);
    }

    /**
     * Does the current logged in user have the given userId?
     *
     * @param int $userId
     *
     * @return bool
     */
    protected function isCurrentUser($userId)
    {
        if (empty($userId)) {
            return false;
        }

        if (!$this->user) {
            return false;
        }

        return $this->user->userId == $userId;
    }

    /**
     * Ensure that the given Image is viewable by the current visitor.
     * If $user is given then the user must be authenticated as that user.
     *
     * @param int|null $userId
     *
     * @return User
     */
    protected function requireAuthentication($userId = null)
    {
        $this->validate(
            $this->request,
            [
                'sessionKey' => 'required|string',
            ]
        );

        if (!$this->user) {
            throw new HttpException(401, "You need to provide a valid sessionKey to use this endpoint.");
        }

        if ($userId) {
            if ($this->user->userId != $userId) {
                throw new HttpException(403, "You're not authorized to do that.");
            }
        }

        return $this->user;
    }

    /**
     * Ensure that the given Image/Album is viewable by the current visitor.
     *
     * @param Album|Image $model
     *
     * @return bool
     */
    protected function requireViewableModel($model)
    {
        if (!$model->password) {
            return true;
        }

        if ($this->user && $model->userId) {
            if ($this->user->userId == $model->userId) {
                return true;
            }
        }

        if ($this->request->has('password')) {
            $passwordHasher = \App::make('PasswordHasher');
            if ($passwordHasher->verify($this->request->input('password'), $model, 'password')) {
                return true;
            }
        }

        throw new HttpException(403, "You don't have permission to view that.");
    }

    /**
     * Ensure that the given Image is editable by the current visitor
     *
     * @param Image $image
     * @param bool  $checkImageKey
     *
     * @return bool
     */
    protected function requireEditableImage(Image $image, $checkImageKey = true)
    {
        if ($this->user && $image->userId) {
            if ($this->user->userId == $image->userId) {
                return true;
            }
        }

        if ($checkImageKey && $this->request->has('imageKey')) {
            $passwordHasher = \App::make('PasswordHasher');
            if ($passwordHasher->verify($this->request->input('imageKey'), $image, 'key', 'plain')) {
                return true;
            }
        }

        throw new HttpException(403, "You don't have permission to modify that image ({$image->imageId}).");
    }

    protected function paginatorToArray(LengthAwarePaginator $paginator, $dataKey = 'items')
    {
        return [
            'total' => $paginator->total(),
            'perPage' => $paginator->perPage(),
            'currentPage' => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
            'nextPageUrl' => $paginator->nextPageUrl(),
            'prevPageUrl' => $paginator->previousPageUrl(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            $dataKey => $paginator->items(),
        ];
    }

    protected function getResultsPerPage()
    {
        if ($perPage = intval($this->request->input('limit'))) {
            if ($perPage > 0 && $perPage <= 100) {
                return $perPage;
            }
        }

        return 15;
    }

    /**
     * Returns the Image objects for the imageIds list in the request.
     *
     * @return Image[]
     */
    protected function getMultipleImageInput()
    {
        $this->validate(
            $this->request,
            [
                'imageIds' => 'required|string',
            ]
        );

        $imageIds = explode(',', $this->request->input('imageIds'));
        $images = [];
        foreach ($imageIds as $imageId) {
            if ($image = Image::find($imageId)) {
                /** @var Image $image */
                $this->requireEditableImage($image);
            } else {
                throw new NotFoundHttpException("Image {$imageId} does not exist.");
            }
        }
        return $images;
    }
}
