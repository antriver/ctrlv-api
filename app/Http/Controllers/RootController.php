<?php

namespace CtrlV\Http\Controllers;

use Response;
use Base\BaseController;

class RootController extends BaseController
{
    public function getIndex()
    {
        return Response::json(['hello' => 'world']);
    }
}
