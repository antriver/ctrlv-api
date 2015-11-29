<?php

namespace CtrlV\Http\Controllers;

use Sinergi\BrowserDetector\Browser;
use Sinergi\BrowserDetector\Os;
use View;

class UploaderController extends Base\BaseController
{
    public function getIndex()
    {
        $browser = new Browser;
        $os = new Os;

        $isMac = $os->getName() === Os::OSX;

        // We going to be optimistic and assume that browsers support pasting unless we know they don't
        $browserName = $browser->getName();
        $crapBrowsers = [
            //Browser::SAFARI
        ];
        $canPaste = !$os->isMobile() && !in_array($browserName, $crapBrowsers);

        return View::make(
            'uploader.index',
            [
                'isMac' => $isMac,
                'canPaste' => $canPaste
            ]
        );
    }

    public function getXdframe()
    {
        return View::make('uploader.xdframe');
    }

    public function getBlank()
    {
        return '';
    }
}
