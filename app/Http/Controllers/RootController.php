<?php

namespace CtrlV\Http\Controllers;

use Response;

class RootController extends Base\BaseController
{
    public function getIndex()
    {
        return Response::json(['hello' => 'world']);
    }
}
