<?php

namespace CtrlV\Http\Controllers;

use CtrlV\Libraries\PasswordHasher;
use Response;
use CtrlV\Models\User;
use CtrlV\Models\UserSession;
use Illuminate\Auth\Guard;
use Illuminate\Http\Request;
use CtrlV\Http\Requests;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @apiDefine UserSuccessResponse
 * @apiSuccessExample {json} Success Response
 *               {
 *                   "success": true,
 *                   "user": [...] // See: Get User Info
 *               }
 */

class UsersController extends Base\ApiController
{
    /**
     * @param Guard $auth
     * @param User $user
     *
     * @return bool
     */
    private function isAuthenticated(Guard $auth, User $user)
    {
        return $auth->check() && $auth->user()->id === $user->id;
    }

    /**
     * @api            {post} /users Create a User
     * @apiGroup       Users
     * @apiDescription Register a new user account.
     * @apiParam {string} username A username for the user.
     * @apiParam {string} email A email address for the user.
     * @apiParam {string} password A password for the user. Minimum 3 characters.
     * @apiUse UserSuccessResponse
     *
     * @param  \Illuminate\Http\Request $request
     * @param PasswordHasher $passwordHasher
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, PasswordHasher $passwordHasher)
    {
        $this->validate(
            $request,
            [
                'username' => 'required|unique:users,username',
                'email' => 'required|unique:users,email',
                'password' => 'required|min:3'
            ]
        );

        $user = new User(
            [
                'username' => $request->get('username'),
                'email' => $request->get('email'),
                'password' => $passwordHasher->generateHash($request->get('password'))
            ]
        );

        $success = $user->save();

        return Response::json(
            [
                'success' => $success,
                'user' => $user->fresh()->toArray(true)
            ]
        );
    }

    /**
     * @api             {get} /users/{username} Get User Info
     * @apiGroup        Users
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
     *         "email": "anthonykuske@gmail.com",
     *         "id": 1,
     *         "moderator": true,
     *         "updatedAt": "2015-11-23 00:48:02",
     *         "username": "Anthony"
     *     }
     * }
     *
     * @param Request $request
     * @param Guard $auth
     * @param User $user
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Guard $auth, User $user)
    {
        $userArray = $user->toArray($this->isAuthenticated($auth, $user));

        return $this->successResponse(
            [
                'user' => $userArray,
            ]
        );
    }

    /**
     * @api            {put} /users/{username} Update User Info
     * @apiGroup       Users
     * @apiDescription Update a user's account information.
     * @apiParam {string} sessionKey A session key belonging to this user.
     * @apiParam {string} [username] A new username for the user.
     * @apiParam {string} [email] A new email address for the user.
     * @apiParam {string} [password] A new password for the user. Minimum 3 characters.
     * @apiParam {int=0,1,2} [defaultPrivacy=0] The privacy setting for new images uploaded by this user.
     *      See "Get Image Info" for options.
     * @apiParam {string} [defaultPassword] **Required if `defaultPrivacy` is being changed to `2`.**
     *      Password required to view newly uploaded images
     *      (can be changed per image after uploading, see "Update Image Info").
     * @apiUse UserSuccessResponse
     *
     * @param  \Illuminate\Http\Request $request
     * @param PasswordHasher $passwordHasher
     * @param Guard $auth
     * @param User $user
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, PasswordHasher $passwordHasher, Guard $auth, User $user)
    {
        if (!$this->isAuthenticated($auth, $user)) {
            throw new HttpException(401);
        }

        $validationRules = [
            'username' => 'unique:users,username,' . $user->id,
            'email' => 'unique:users,email,' . $user->id,
            'password' => 'min:3',
            'defaultPrivacy' => 'integer|between:0,2',
            'defaultPassword' => 'string'
        ];

        if ($user->defaultPrivacy != 2) {
            /**
             * Conditionally add this validator because if the defaultPrivacy is already set to 2
             * it may be resupplied as 2 without making any difference. But we don't want to password
             * to have to be resupplied in that case. So only require it if defaultPrivacy is being changed
             * to 2 and it is not already 2.
             */
            $validationRules ['defaultPassword'] = 'string|required_if:defaultPrivacy,2';
        }

        $this->validate(
            $request,
            $validationRules
        );

        if ($request->has('username')) {
            $user->username = $request->input('username');
        }

        if ($request->has('email')) {
            $user->email = $request->input('email');
        }

        if ($request->has('password')) {
            $user->password = $passwordHasher->generateHash($request->input('password'));
        }

        if ($request->has('defaultPrivacy')) {
            $user->defaultPrivacy = $request->input('defaultPrivacy');
        }

        if ($request->has('defaultPassword')) {
            $user->defaultPassword = $passwordHasher->generateHash($request->input('defaultPassword'));
        }

        $success = $user->isDirty() ? $user->save() : false;

        return $this->successResponse(
            [
                'success' => $success,
                'user' => $user->fresh()->toArray(true)
            ]
        );
    }

    public function showImages(Request $request, User $user)
    {
        $images = $user->images()->limit(10)->get();
        return $this->successResponse([
            'images' => $images
        ]);
    }
}
