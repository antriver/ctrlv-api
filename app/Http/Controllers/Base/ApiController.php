<?php

namespace CtrlV\Http\Controllers\Base;

use Auth;
use Input;
use Response;
use CtrlV\Models\User;
use CtrlV\Models\UserSession;

abstract class ApiController extends BaseController
{
    public function __construct()
    {
        // TODO: Add API keys and check here

        if ($sessionKey = Input::get('sessionKey')) {
            if ($session = UserSession::findOrFail($sessionKey)) {
                if ($user = User::findOrFail($session->userID)) {
                    /** @var \Illuminate\Contracts\Auth\Authenticatable $user */
                    Auth::setUser($user);
                    // Note: setUser() not login() so the user is only logged in for this request
                }
            }
        }
    }

    /**
     * Create a JSON error response
     *
     * @param string $message
     * @param int $status
     *
     * @return Response
     */
    protected function error($message, $status = 400)
    {
        return Response::json(
            [
                'error' => true,
                'message' => $message,
                'status' => $status
            ],
            $status
        );
    }
}
