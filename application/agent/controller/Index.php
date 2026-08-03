<?php

namespace app\agent\controller;

use app\common\controller\Backend;

class Index extends Backend
{
    public function index()
    {
        return $this->view->fetch();
    }
}
