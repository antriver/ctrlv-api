<?php

namespace CtrlV\Http\Controllers;

use CtrlV\Http\Requests;
use CtrlV\Libraries\PasswordHasher;
use CtrlV\Models\User;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @apiDefine UserSuccessResponse
 * @apiSuccessExample {json} Success Response
 *     {
 *       "success": true,
 *       "user": {
 *         // See Get User Info
 *       }
 *     }
 */
class UsersController extends Base\ApiController
{
    /**
     * @api {post} /users Create a User
     * @apiGroup Users
     * @apiDescription Register a new user account.
     * @apiParam {string} username A username for the user.
     * @apiParam {string} email A email address for the user.
     * @apiParam {string} password A password for the user. Minimum 3 characters.
     * @apiUse UserSuccessResponse
     *
     * @param PasswordHasher $passwordHasher
     *
     * @return Response
     */
    public function store(PasswordHasher $passwordHasher)
    {
        $this->validate(
            $this->request,
            [
                'username' => 'required|unique:users,username',
                'email' => 'required|unique:users,email',
                'password' => 'required|min:3'
            ]
        );

        $user = new User(
            [
                'username' => $this->request->input('username'),
                'email' => $this->request->input('email'),
                'password' => $passwordHasher->generateHash($this->request->input('password'))
            ]
        );

        if ($user->save()) {
            return $this->response(
                [
                    'user' => $user->fresh()
                ]
            );
        }

        throw new HttpException(500, "Unable to save user.");
    }

    /**
     * @api {get} /users/{username} Get User Info
     * @apiGroup Users
     * @apiDescription Get a user's account information.
     * @apiParam {string} [sessionKey] If a valid sessionKey for this user is given additional information is returned.
     * @apiSuccessExample {json} Success Response
     * {
     *     "user": {
     *         "createdAt": "2010-05-14 12:39:00",
     *         "id": 1,
     *         "username": "Anthony"
     *     }
     * }
     * @apiSuccessExample {json} Success Response With sessionKey
     * {
     *     "user": {
     *         "createdAt": "2010-05-14 12:39:00",
     *         "defaultPrivacy": 0,
     *         "email": "anthony@example.com",
     *         "id": 1,
     *         "moderator": true,
     *         "updatedAt": "2015-11-23 00:48:02",
     *         "url": "http://ctrlv.in/user/anthony",
     *         "username": "Anthony"
     *     }
     * }
     *
     * @param User $user
     *
     * @return Response
     */
    public function show(User $user)
    {
        return $this->response(
            [
                'user' => $user,
            ]
        );
    }

    /**
     * @api {put} /users/{username} Update User Info
     * @apiGroup Users
     * @apiDescription Update a user's account information.
     * @apiParam {string} sessionKey A session key belonging to this user.
     * @apiParam {string} [username] A new username for the user.
     * @apiParam {string} [email] A new email address for the user.
     * @apiParam {string} [password] A new password for the user. Minimum 3 characters.
     * @apiParam {boolean=0,1} [defaultAnonymous=0] Display the username on images uploaded by this user?
     * @apiParam {string} [defaultPassword] A password that will be required to view newly uploaded images.
     *     (Can be changed per image after uploading, see "Update Image Info").
     * @apiUse UserSuccessResponse
     *
     * @param User $user
     * @param PasswordHasher $passwordHasher
     *
     * @return Response
     */
    public function update(User $user, PasswordHasher $passwordHasher)
    {
        $this->requireAuthentication($user->userId);

        $validationRules = [
            'username' => 'unique:users,username,'.$user->userId.',userId',
            'email' => 'unique:users,email,'.$user->userId.',userId',
            'password' => 'min:3',
            'defaultAnonymous' => 'boolean',
            'defaultPassword' => 'string'
        ];

        $this->validate(
            $this->request,
            $validationRules
        );

        if ($this->request->has('username')) {
            $user->username = $this->request->input('username');
        }

        if ($this->request->has('email')) {
            $user->email = $this->request->input('email');
        }

        if ($this->request->has('password')) {
            $user->password = $passwordHasher->generateHash($this->request->input('password'));
        }

        if ($this->request->has('defaultAnonymous')) {
            $user->defaultAnonymous = (bool)$this->request->input('defaultAnonymous');
        }

        if ($this->request->exists('defaultPassword')) {
            if ($password = $this->request->input('defaultPassword')) {
                $user->defaultPassword = $passwordHasher->generateHash($password);
            } else {
                $user->defaultPassword = null;
            }
        }

        $success = $user->isDirty() ? $user->save() : false;

        return $this->response(
            [
                'success' => $success,
                'user' => $user->fresh()
            ]
        );
    }

    /**
     * @api {get} /users/{username}/albums Get User's Images
     * @apiGroup Users
     * @apiDescription Gets albums created by a user. The results are paginated with 15 results per page.
     * @apiParam {string} [sessionKey] A session key belonging to this user. Unless this is given anonymous
     *     and passworded albums will be omitted.
     * @apiParam {int} [page=1] Results page number.
     * @apiUse PaginatedAlbumResponse
     *
     * @param User $user
     *
     * @return Response
     */
    public function indexAlbums(User $user)
    {
        $this->validate(
            $this->request,
            [
                'page' => 'int|min:1'
            ]
        );

        $results = $user->albums();

        // TODO
        /*if (!$this->isCurrentUser($user)) {
            $results->where('anonymous', 0)->whereNull('password');
        }*/

        $results->orderBy('title', 'ASC');

        $paginator = $results->paginate($this->getResultsPerPage());

        return $this->response(
            $this->paginatorToArray($paginator, 'albums')
        );
    }

    /**
     * @api {get} /users/{username}/images Get User's Images
     * @apiGroup Users
     * @apiDescription Gets images uploaded by a user. The results are paginated with 15 results per page.
     * @apiParam {string} [sessionKey] A session key belonging to this user. Unless this is given anonymous
     *     and passworded images will be omitted.
     * @apiParam {int} [page=1] Results page number.
     * @apiUse PaginatedImageResponse
     *
     * @param User $user
     *
     * @return Response
     */
    public function indexImages(User $user)
    {
        $this->validate(
            $this->request,
            [
                'page' => 'int|min:1'
            ]
        );

        $results = $user->images()->with('imageFile')->with('thumbnailImageFile');

        if (!$this->isCurrentUser($user->getId())) {
            $results->where('anonymous', 0)->whereNull('password');
        }

        $results->orderBy('imageId', 'DESC');

        $paginator = $results->paginate($this->getResultsPerPage());

        return $this->response(
            $this->paginatorToArray($paginator, 'images')
        );
    }
}
