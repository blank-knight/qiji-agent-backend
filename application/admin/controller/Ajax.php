<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Config;
use think\Cache;
use think\Validate;
use think\Db;

/**
 * Ajax通用接口
 */
class Ajax extends Backend
{
    protected $noNeedLogin = ['lang'];
    protected $noNeedRight = ['*'];
    protected $layout = '';

    /**
     * 语言文件加载
     */
    public function lang()
    {
        $this->request->filter(['trim']);
        $controllername = $this->request->get('controllername', '');
        $this->loadlang($controllername);
        $langs = \think\Lang::get();
        // RequireJS 通过 callback=define 以 JSONP 方式加载，需要 AMD 格式
        $callback = $this->request->get('callback', '');
        if ($callback === 'define') {
            return response($callback . '(' . json_encode($langs) . ');')->header(['Content-Type' => 'application/javascript']);
        }
        return json($langs);
    }

    /**
     * 清空系统缓存
     */
    public function wipecache()
    {
        $type = $this->request->request('type');
        switch ($type) {
            case 'content':
                \think\Cache::clear();
                break;
            case 'template':
                // 清除模板缓存
                $tempFiles = glob(\think\Config::get('template.compiled_path') . '*');
                foreach ($tempFiles as $file) {
                    if (is_file($file)) {
                        @unlink($file);
                    }
                }
                break;
            case 'addons':
                // 清除插件缓存
                break;
            case 'all':
            default:
                \think\Cache::clear();
                $tempFiles = glob(\think\Config::get('template.compiled_path') . '*');
                foreach ($tempFiles as $file) {
                    if (is_file($file)) {
                        @unlink($file);
                    }
                }
                break;
        }

        \think\Hook::listen("wipecache_after");
        $this->success();
    }

    /**
     * 通用排序
     */
    public function weigh()
    {
        // 排序的字段名
        $ids = $this->request->post('ids');
        $changeids = $this->request->post('changeids');

        if (!$ids) {
            $this->error(__('Parameter %s can not be empty', 'ids'));
        }

        $pk = $this->model->getPk();
        $table = $this->model->getQuery()->getTable();

        $idsArray = explode(',', $ids);

        foreach ($idsArray as $n => $id) {
            Db::name($table)->where($pk, $id)->update(['weigh' => $n + 1]);
        }

        $this->success();
    }

    /**
     * 通用状态修改
     */
    public function state()
    {
        $id = $this->request->post('id/d');
        $field = $this->request->post('field', 'status');
        $value = $this->request->post('value');

        if (!$id) {
            $this->error(__('Parameter %s can not be empty', 'id'));
        }

        $pk = $this->model->getPk();
        $result = $this->model->where($pk, $id)->update([$field => $value]);

        if ($result !== false) {
            $this->success();
        } else {
            $this->error($this->model->getError());
        }
    }
}
