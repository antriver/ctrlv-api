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

class SessionsController extends Base\ApiController
{
    /**
     * @api            {get} /sessions/{id} Get Session Info
     * @apiGroup       User Sessions
     * @apiDescription Returns information about a user's session.
     * @apiParam {string} sessionKey A user session key.
     *
     * @param UserSession $session
     *
     * @return \Illuminate\Http\Response
     */
    public function show(UserSession $session)
    {
        $user = $session->user;
        return $this->successResponse(
            [
                'session' => $session,
                'success' => true,
                //'user' => $user->toArray(),
            ]
        );
    }

    /**
     * @api            {post} /sessions Start A Session (Login)
     * @apiGroup       User Sessions
     * @apiDescription Validates login credentials and returns a new session if valid.
     * @apiParam {string} username Username to login as.
     * @apiParam {string} password The user's password.
     *
     * @param Request $request
     * @param Guard $auth
     * @param PasswordHasher $passwordHasher
     */
    public function login(Request $request, Guard $auth, PasswordHasher $passwordHasher)
    {
        $this->validate(
            $request,
            [
                'username' => 'required',
                'password' => 'required'
            ]
        );

        $username = $request->get('username');
        $password = $request->get('password');

        /** @var User $user */
        $user = User::whereUsername($username)->first();
        if (!$user) {
            throw new NotFoundHttpException("We can't find a user with that username.");
        }

        if ($passwordHasher->verify($password, $user, 'password')) {
            $auth->setUser($user);

            // Start a new session
            $session = (new UserSession(
                [
                    'userId' => $user->id,
                    'ip' => $request->getClientIp()
                ]
            ));
            $session->save();

            return $this->successResponse(
                [
                    'session' => $session,
                    'success' => true,
                    'user' => $user->toArray(true),
                ]
            );
        } else {
            throw new HttpException(401, 'Incorrect password.');
        }
    }
}
