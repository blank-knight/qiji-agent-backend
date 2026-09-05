<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;
use think\Db;

/**
 * 套餐订单列表
 * 超管：看全部订单，含每笔实际收款方（感知贴牌/代理自收款）
 * 贴牌/代理：看自己子树内用户(归属自己套餐链)的订单
 */
class Scoreorder extends Backend
{

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {
        $this->relationSearch = false;
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            // 层级隔离：超管=全部；贴牌/代理=自己+子树代理相关的订单
            $scopeWhere = [];
            $currentAgent = $this->getCurrentAgent();
            if (!$this->isSuperAdmin()) {
                if (!$currentAgent) {
                    $this->error('无权查看');
                }
                $scopeIds = Db::name('agent')->where('path', 'like', $currentAgent['path'] . '%')->column('id');
                $scopeIds[] = $currentAgent['id'];
                // 订单可见：套餐归属方 或 收款方 在子树内
                $scopeWhere = function ($q) use ($scopeIds) {
                    $q->where('agent_id', 'in', $scopeIds)->whereOr('payee_agent_id', 'in', $scopeIds);
                };
            }

            $total = Db::name('score_order')->where($where)->where($scopeWhere)->count();
            $list = Db::name('score_order')
                ->where($where)
                ->where($scopeWhere)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $agentNames = [];
            foreach ($list as &$row) {
                $row['user_name'] = Db::name('user')->where('id', $row['user_id'])->value('username') ?: ('#' . $row['user_id']);
                // 套餐归属
                $aid = (int)$row['agent_id'];
                if (!isset($agentNames[$aid])) {
                    $agentNames[$aid] = $aid ? (Db::name('agent')->where('id', $aid)->value('name') ?: ('#' . $aid)) : '平台';
                }
                $row['agent_name'] = $agentNames[$aid];
                // 实际收款方
                $pid = (int)$row['payee_agent_id'];
                if (!isset($agentNames[$pid])) {
                    $agentNames[$pid] = $pid ? (Db::name('agent')->where('id', $pid)->value('name') ?: ('#' . $pid)) : '平台';
                }
                $row['payee_name'] = $agentNames[$pid];
            }

            return json(['total' => $total, 'rows' => collection($list)->toArray()]);
        }
        return $this->view->fetch();
    }
}
