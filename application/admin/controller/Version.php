<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\common\model\Version as VersionModel;

/**
 * 版本管理
 */
class Version extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new VersionModel();
    }
}
