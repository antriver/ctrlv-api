<?php

namespace CtrlV\Http\Controllers;

use Request;
use Response;
use View;
use CtrlV\Http\Controllers\Base\BaseController;

class UploadController extends BaseController
{
    public function getIndex(Request $request)
    {
        $agent = Request::header('User-Agent');

        $mac = strpos($agent, 'Macintosh') !== false;


        return View::make('upload.index', [
            'mac' => $mac
        ]);
    }
}
