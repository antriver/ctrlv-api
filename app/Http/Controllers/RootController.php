<?php

namespace CtrlV\Http\Controllers;

class RootController extends Base\ApiController
{
    public function getIndex()
    {
        return $this->response(['hello' => 'world']);
    }
}
