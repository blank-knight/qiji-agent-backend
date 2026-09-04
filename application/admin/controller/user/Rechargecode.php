<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;
use think\Db;
use fast\Random;

/**
 * 充值码管理（总后台/代理）
 * 超管：可发任意面值、通用码或指定代理
 * 贴牌/代理：只能发自己子树范围、用户兑换后积分进入用户账户
 */
class Rechargecode extends Backend
{

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Rechargecode();
    }

    /**
     * 列表
     */
    public function index()
    {
        $this->relationSearch = false;

        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            // 数据隔离：非超管只能看自己子树的码（含自己发的通用归属码）
            $scopeWhere = [];
            if (!$this->isSuperAdmin()) {
                $currentAgent = $this->getCurrentAgent();
                if (!$currentAgent) {
                    $scopeWhere = ['agent_id' => -1]; // 无代理身份，什么都看不到
                } else {
                    $scope = \app\admin\model\Agent::getDescendantIds($currentAgent['id']);
                    $scopeWhere = ['agent_id' => ['in', $scope]];
                }
            }

            $total = $this->model
                ->where($where)
                ->where($scopeWhere)
                ->count();

            $list = $this->model
                ->where($where)
                ->where($scopeWhere)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            foreach ($list as &$row) {
                $row['used_by_username'] = $row['used_by'] ? (Db::name('user')->where('id', $row['used_by'])->value('username') ?: ('#' . $row['used_by'])) : '';
                $row['agent_name'] = $row['agent_id'] ? (Db::name('agent')->where('id', $row['agent_id'])->value('name') ?: ('#' . $row['agent_id'])) : '平台通用';
            }

            $result = ['total' => $total, 'rows' => $list];
            return json($result);
        }

        return $this->view->fetch();
    }

    /**
     * 批量生成充值码
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            if (!$params) {
                $this->error(__('Parameter %s can not be empty', ''));
            }

            $score = (int)($params['score'] ?? 0);
            $count = (int)($params['count'] ?? 1);
            if ($score <= 0) {
                $this->error('面值必须大于0');
            }
            if ($count < 1 || $count > 500) {
                $this->error('单次生成数量 1-500');
            }

            // 归属：超管可选代理或通用；贴牌/代理只能选自己子树
            $agentId = (int)($params['agent_id'] ?? 0);
            if (!$this->isSuperAdmin()) {
                $currentAgent = $this->getCurrentAgent();
                if (!$currentAgent) {
                    $this->error('无权限生成充值码');
                }
                if ($agentId) {
                    // 校验目标代理在自己子树内
                    $target = Db::name('agent')->where('id', $agentId)->find();
                    if (!$target || strpos($target['path'], $currentAgent['path']) !== 0) {
                        $this->error('只能为下级代理生成充值码');
                    }
                } else {
                    $agentId = $currentAgent['id'];
                }
            }

            $now = time();
            $rows = [];
            for ($i = 0; $i < $count; $i++) {
                $rows[] = [
                    'code'       => 'QJ' . strtoupper(Random::alnum(12)),
                    'score'      => $score,
                    'agent_id'   => $agentId,
                    'status'     => 'unused',
                    'used_by'    => 0,
                    'used_at'    => 0,
                    'createtime' => $now,
                    'updatetime' => $now,
                ];
            }
            Db::name('recharge_code')->insertAll($rows);

            $this->success("成功生成 {$count} 个充值码");
        }

        $this->assignAgentOptions();
        return $this->view->fetch();
    }

    /**
     * 删除（仅未使用的可删）
     */
    public function del($ids = null)
    {
        if ($this->request->isPost() || $this->request->isAjax()) {
            $ids = $ids ? explode(',', $ids) : [];
            if (!$ids) {
                $this->error('参数错误');
            }
            // 非超管校验归属
            if (!$this->isSuperAdmin()) {
                $currentAgent = $this->getCurrentAgent();
                if (!$currentAgent) {
                    $this->error('无权限');
                }
                $scope = \app\admin\model\Agent::getDescendantIds($currentAgent['id']);
                $owned = Db::name('recharge_code')->where('id', 'in', $ids)->where('agent_id', 'in', $scope)->column('id');
                $ids = array_intersect($ids, $owned);
                if (!$ids) {
                    $this->error('无权操作这些充值码');
                }
            }
            // 已使用的不允许删（保留审计）
            $used = Db::name('recharge_code')->where('id', 'in', $ids)->where('status', '<>', 'unused')->count();
            if ($used) {
                $this->error('已使用/已停用的充值码不能删除');
            }
            Db::name('recharge_code')->where('id', 'in', $ids)->where('status', 'unused')->delete();
            $this->success();
        }
        $this->error('无效请求');
    }

    /**
     * 停用/启用（multi 快捷开关）
     */
    public function multi($ids = '')
    {
        $ids = $ids ? $ids : $this->request->param('ids');
        $ids = $ids ? explode(',', $ids) : [];
        $params = $this->request->post('params', '');
        parse_str($params, $values);
        $action = isset($values['status']) ? $values['status'] : '';
        if (!$ids || !in_array($action, ['disabled', 'unused'])) {
            $this->error('无效操作');
        }
        if (!$this->isSuperAdmin()) {
            $currentAgent = $this->getCurrentAgent();
            if (!$currentAgent) {
                $this->error('无权限');
            }
            $scope = \app\admin\model\Agent::getDescendantIds($currentAgent['id']);
            $owned = Db::name('recharge_code')->where('id', 'in', $ids)->where('agent_id', 'in', $scope)->column('id');
            $ids = array_intersect($ids, $owned);
            if (!$ids) {
                $this->error('无权操作');
            }
        }
        // 停用只作用于未使用；启用只作用于已停用（已使用不可逆）
        if ($action === 'unused') {
            Db::name('recharge_code')->where('id', 'in', $ids)->where('status', 'disabled')->update(['status' => 'unused', 'updatetime' => time()]);
        } else {
            Db::name('recharge_code')->where('id', 'in', $ids)->where('status', 'unused')->update(['status' => 'disabled', 'updatetime' => time()]);
        }
        $this->success();
    }

    /**
     * 为下拉框赋值代理选项（贴牌看子树，代理看自己，超管看全部+通用）
     */
    private function assignAgentOptions()
    {
        $agents = [];
        if ($this->isSuperAdmin()) {
            $agents[] = ['id' => 0, 'name' => '平台通用（所有用户可兑）'];
            $rows = Db::name('agent')->field('id,name,type')->order('id', 'asc')->select();
            foreach ($rows as $r) {
                $typeText = $r['type'] === 'tiepai' ? '贴牌商' : '代理';
                $agents[] = ['id' => $r['id'], 'name' => $r['name'] . '（' . $typeText . '）'];
            }
        } else {
            $currentAgent = $this->getCurrentAgent();
            if ($currentAgent) {
                $scope = \app\admin\model\Agent::getDescendantIds($currentAgent['id']);
                $agents[] = ['id' => $currentAgent['id'], 'name' => $currentAgent['name'] . '（自己）'];
                if (count($scope) > 1) {
                    $rows = Db::name('agent')->where('id', 'in', array_diff($scope, [$currentAgent['id']]))->field('id,name,type')->select();
                    foreach ($rows as $r) {
                        $typeText = $r['type'] === 'tiepai' ? '贴牌商' : '代理';
                        $agents[] = ['id' => $r['id'], 'name' => $r['name'] . '（' . $typeText . '）'];
                    }
                }
            }
        }
        $this->view->assign('agentOptions', $agents);
    }
}

