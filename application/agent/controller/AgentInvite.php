<?php

namespace app\agent\controller;

use app\common\controller\Backend;
use think\Db;
use app\common\model\AgentInvite;

/**
 * 邀请码管理（代理后台）
 */
class AgentInvite extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new AgentInvite();
    }

    /**
     * 查看
     */
    public function index()
    {
        $agentId = $this->getAgentId();

        $this->relationSearch = false;

        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->model
                ->where('agent_id', $agentId)
                ->where($where)
                ->count();

            $list = $this->model
                ->where('agent_id', $agentId)
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $result = ['total' => $total, 'rows' => $list];
            return json($result);
        }

        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            if (!$params) {
                $this->error(__('Parameter %s can not be empty', ''));
            }

            $agentId = $this->getAgentId();

            // 生成唯一邀请码
            $params['invite_code'] = $this->generateUniqueCode();
            $params['agent_id']    = $agentId;
            $params['used_count']  = 0;
            $params['status']      = 'normal';

            $result = $this->model->validate()->save($params);
            if ($result !== false) {
                $this->success();
            } else {
                $this->error($this->model->getError());
            }
        }

        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }

        // 只能操作自己的邀请码
        $agentId = $this->getAgentId();
        if ($row['agent_id'] != $agentId) {
            $this->error('无权操作');
        }

        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            $params['invite_code'] = $row['invite_code'];
            $params['agent_id']    = $row['agent_id'];

            $result = $row->save($params);
            if ($result !== false) {
                $this->success();
            } else {
                $this->error($row->getError());
            }
        }

        $this->view->assign('row', $row);
        return $this->view->fetch();
    }

    /**
     * 删除
     */
    public function del($ids = '')
    {
        if ($ids) {
            $agentId = $this->getAgentId();
            $count   = $this->model->where('id', 'in', $ids)
                ->where('agent_id', $agentId)
                ->delete();
            if ($count) {
                $this->success();
            } else {
                $this->error('无权删除');
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }

    /**
     * 生成唯一邀请码
     */
    private function generateUniqueCode($length = 8)
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $code = '';
            for ($i = 0; $i < $length; $i++) {
                $code .= $chars[mt_rand(0, strlen($chars) - 1)];
            }
        } while (Db::name('agent_invite')->where('invite_code', $code)->find());

        return $code;
    }

    /**
     * 获取当前登录代理的 agent_id
     */
    private function getAgentId()
    {
        // 代理后台的 admin 关联到 agent 表
        // 通过 session 中的 admin id 查找关联的 agent
        $adminId = $this->auth->id;

        // 检查 admin 表是否有 agent_id 字段
        $columns = $this->getTableColumns('admin');
        if (in_array('agent_id', $columns)) {
            $agentId = Db::name('admin')->where('id', $adminId)->value('agent_id');
            if ($agentId) {
                return $agentId;
            }
        }

        // 备用方案：agent 表的 id 等于当前 admin 的 id（简化的 1:1 关联）
        $agent = Db::name('agent')->where('admin_id', $adminId)->find();
        if ($agent) {
            return $agent['id'];
        }

        // 超级管理员：返回第一个代理 ID
        if ($this->auth->isSuper()) {
            $firstAgent = Db::name('agent')->order('id', 'asc')->find();
            return $firstAgent ? $firstAgent['id'] : 1;
        }

        return 0;
    }

    private function getTableColumns($table)
    {
        $database = config('database.database');
        $prefix   = config('database.prefix');
        $rows     = Db::query("SHOW COLUMNS FROM `{$prefix}{$table}`");
        return array_column($rows, 'Field');
    }
}
