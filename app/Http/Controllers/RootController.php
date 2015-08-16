<?php

namespace CtrlV\Http\Controllers;

use Config;
use Request;
use Response;
use CtrlV\Http\Controllers\Base\BaseController;
use CtrlV\Jobs\OptimizeImageJob;

class RootController extends BaseController
{
    public function getIndex(Request $request)
    {
        return Response::json(['hello' => 'world']);
    }
}
