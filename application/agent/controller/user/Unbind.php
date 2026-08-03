<?php

namespace app\agent\controller\user;

use app\common\controller\Backend;
use think\Db;

/**
 * 用户管理（代理后台，解绑功能）
 */
class Unbind extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\User();
    }

    /**
     * 用户列表（仅当前代理的用户）
     */
    public function index()
    {
        $agentId = $this->getAgentId();

        if ($this->request->isAjax()) {
            $this->relationSearch = false;

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = Db::name('user')
                ->where('agent_id', $agentId)
                ->where($where)
                ->count();

            $list = Db::name('user')
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
     * 解绑用户（取消和代理的关联）
     */
    public function unbind($ids = '')
    {
        if (!$ids) {
            $this->error('请选择要解绑的用户');
        }

        $agentId = $this->getAgentId();

        $count = Db::name('user')
            ->where('id', 'in', $ids)
            ->where('agent_id', $agentId)
            ->update(['agent_id' => 0]);

        if ($count > 0) {
            $this->success("成功解绑 {$count} 个用户");
        } else {
            $this->error('未找到符合条件的用户');
        }
    }

    /**
     * 获取当前登录代理的 agent_id
     */
    private function getAgentId()
    {
        $adminId = $this->auth->id;

        $agent = Db::name('agent')->where('admin_id', $adminId)->find();
        if ($agent) {
            return $agent['id'];
        }

        if ($this->auth->isSuper()) {
            $firstAgent = Db::name('agent')->order('id', 'asc')->find();
            return $firstAgent ? $firstAgent['id'] : 1;
        }

        return 0;
    }
}
