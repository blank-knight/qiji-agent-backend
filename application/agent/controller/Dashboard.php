<?php

namespace app\agent\controller;

use app\common\controller\Backend;
use think\Db;

/**
 * 代理仪表盘
 */
class Dashboard extends Backend
{
    public function index()
    {
        $agentId = $this->getAgentId();

        $userCount = Db::name('user')->where('agent_id', $agentId)->count();
        $totalScore = Db::name('user')->where('agent_id', $agentId)->sum('score');
        $agentScore = Db::name('agent')->where('id', $agentId)->value('score');
        $inviteCount = Db::name('agent_invite')->where('agent_id', $agentId)->count();

        $this->view->assign([
            'user_count'   => $userCount,
            'total_score'  => $totalScore,
            'agent_score'  => $agentScore,
            'invite_count' => $inviteCount,
        ]);

        return $this->view->fetch();
    }

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
