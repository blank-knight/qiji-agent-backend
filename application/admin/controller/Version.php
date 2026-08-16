<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\common\model\Version as VersionModel;
use think\Validate;

/**
 * 客户端版本管理
 *
 * 发布新版本供客户端 /api/client/v1/update/check 检测更新
 *
 * @icon fa fa-upload
 */
class Version extends Backend
{

    /**
     * @var VersionModel
     */
    protected $model = null;

    // multi 批量修改白名单：状态与强制更新开关
    protected $multiFields = 'status,enforce';

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new VersionModel();
        $this->view->assign("statusList", ['normal' => '正常', 'hidden' => '隐藏']);
        $this->view->assign("enforceList", ['0' => '否', '1' => '是']);
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = array_filter($params, function ($v) {
                    return $v !== '' && $v !== null;
                });
                $this->validateVersion($params);
                $result = $this->model->allowField(true)->save($params);
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error($this->model->getError());
                }
            }
            $this->error(__('Parameter %s can not be empty', 'row'));
        }
        return $this->view->fetch();
    }

    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = array_filter($params, function ($v) {
                    return $v !== '' && $v !== null;
                });
                $this->validateVersion($params);
                $result = $row->allowField(true)->save($params);
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error($row->getError());
                }
            }
            $this->error(__('Parameter %s can not be empty', 'row'));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 版本记录字段校验
     */
    private function validateVersion(&$params)
    {
        if (empty($params['newversion'])) {
            $this->error('请填写新版本号');
        }
        if (!preg_match('/^\d+(\.\d+)*$/', $params['newversion'])) {
            $this->error('版本号格式错误，应为如 1.2.0 的数字点分格式');
        }
        if (empty($params['downloadurl'])) {
            $this->error('请填写下载地址');
        }
        if (!Validate::is($params['downloadurl'], 'url')) {
            $this->error('下载地址格式错误，应以 http(s):// 开头');
        }
        if (!isset($params['enforce'])) {
            $params['enforce'] = 0;
        }
        if (!isset($params['status'])) {
            $params['status'] = 'normal';
        }
    }
}
