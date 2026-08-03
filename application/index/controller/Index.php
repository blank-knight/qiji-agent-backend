<?php

namespace app\index\controller;

use think\Controller;

class Index extends Controller
{
    public function index()
    {
        return json(['name' => 'QIJI Agent API', 'version' => '1.0.0', 'status' => 'running']);
    }
}
