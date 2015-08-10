<?php

namespace CtrlV\Http\Controllers\Base;

use Auth;
use DB;
use Input;
use CtrlV\Http\Controllers\Base\BaseController;
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
                    Auth::setUser($user);
                    // Note: setUser() not login() so the user is only logged in for this request
                }
            }
        }
    }
}
