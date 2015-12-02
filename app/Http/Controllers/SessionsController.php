<?php

namespace CtrlV\Http\Controllers;

use CtrlV\Http\Requests;
use CtrlV\Libraries\PasswordHasher;
use CtrlV\Models\User;
use CtrlV\Models\UserSession;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SessionsController extends Base\ApiController
{
    /**
     * @api {get} /sessions/{sessionKey} Get Session Info
     * @apiGroup User Sessions
     * @apiDescription Returns information about a user's session.
     *
     * @param UserSession $session
     *
     * @return \Illuminate\Http\Response
     */
    public function show(UserSession $session)
    {
        $session->with('user');

        return $this->response(
            [
                'session' => $session,
                'success' => true,
            ]
        );
    }

    /**
     * @api {post} /sessions Start a Session (Login)
     * @apiGroup User Sessions
     * @apiDescription Validates login credentials and returns a new session if valid.
     * @apiParam {string} username Username to login as.
     * @apiParam {string} password The user's password.
     *
     * @param PasswordHasher $passwordHasher
     *
     * @throws HttpException
     * @return \Response
     */
    public function store(PasswordHasher $passwordHasher)
    {
        $this->validate(
            $this->request,
            [
                'username' => 'required',
                'password' => 'required'
            ]
        );

        $username = $this->request->input('username');
        $password = $this->request->input('password');

        /** @var User $user */
        $user = User::whereUsername($username)->first();
        if (!$user) {
            throw new NotFoundHttpException("Couldn't find a user with that username.");
        }

        if ($passwordHasher->verify($password, $user, 'password')) {
            $this->auth->setUser($user);

            // Start a new session
            $session = new UserSession(
                [
                    'userId' => $user->userId,
                    'ip' => $this->request->getClientIp()
                ]
            );

            $sessionKey = $session->generateKey($passwordHasher);

            $session->save();

            return $this->response(
                [
                    'session' => $session,
                    'sessionKey' => $sessionKey,
                    'success' => true
                ]
            );
        } else {
            throw new HttpException(401, 'Incorrect password.');
        }
    }

    /**
     * @api {delete} /sessions/{sessionKey} End A Session (Logout)
     * @apiGroup User Sessions
     * @apiDescription Deletes a user's session.
     *
     * @param UserSession $session
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(UserSession $session)
    {
        $success = $session->delete();

        return $this->response(
            [
                'success' => $success
            ]
        );
    }
}
