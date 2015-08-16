<?php

namespace CtrlV\Http\Controllers;

use Request;
use Response;
use View;
use CtrlV\Http\Controllers\Base\BaseController;

use Browser\Browser;
use Browser\Os;

class UploaderController extends BaseController
{
    public function getIndex(Request $request)
    {
        $browser = new Browser;
        $os = new Os;

        $agent = Request::header('User-Agent');

        $isMac = $os->getName() === 'OS X';

        // We going to be optimistic and assume that browsers support pasting unless we know they don't
        $browserName = $browser->getName();
        $crapBrowsers = ['Safari'];
        $canPaste = !in_array($browserName, $crapBrowsers);


        return View::make('uploader.index', [
            'isMac' => $isMac,
            'canPaste' => $canPaste
        ]);
    }

    public function getXdframe(Request $request)
    {
        return View::make('uploader.xdframe');
    }

    public function getBlank(Request $request)
    {
        return '';
    }
}
