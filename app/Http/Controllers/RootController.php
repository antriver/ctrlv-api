<?php

namespace CtrlV\Http\Controllers;

use Request;
use Response;
use CtrlV\Http\Controllers\Base\BaseController;

class RootController extends BaseController
{
    public function getIndex(Request $request)
    {
        return Response::json(['hello' => 'world']);
    }
}
