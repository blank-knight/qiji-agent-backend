<?php

namespace app\admin\controller\agent;

use app\common\controller\Backend;

/**
 * 邀请码管理（总后台）
 */
class Agentinvite extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\AgentInvite();
        // add 视图按超管/非超管渲染不同表单
        $this->view->assign('isSuperAdmin', $this->isSuperAdmin());
    }

    /**
     * 查看
     */
    public function index()
    {
        $this->relationSearch = false;

        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            // 数据隔离：超管看全部；贴牌/代理只看自己子树范围内的邀请码
            $scope = $this->getAgentScope();
            $scopeWhere = [];
            if ($scope !== null) {
                $scopeWhere = ['agent_id' => ['in', $scope]];
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
                $row['agent_name'] = db('agent')->where('id', $row['agent_id'])->value('name');
            }

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

            // 归属强制：非超管（贴牌/代理）只能给自己（或子树）创建邀请码
            if (!$this->isSuperAdmin()) {
                $currentAgent = $this->getCurrentAgent();
                if (!$currentAgent) {
                    $this->error('当前账号未关联代理，无法创建邀请码');
                }
                $target = isset($params['agent_id']) ? (int)$params['agent_id'] : 0;
                if ($target === 0) {
                    // 非超管表单不展示所属代理字段，默认归属自己
                    $params['agent_id'] = (int)$currentAgent['id'];
                } else {
                    $scope = $this->getAgentScope();
                    if (!$scope || !in_array($target, $scope)) {
                        $this->error('只能为自己范围内的代理创建邀请码');
                    }
                }
            }

            // 生成唯一邀请码
            $params['invite_code'] = $this->generateUniqueCode();
            $params['used_count']  = 0;
            $params['status']      = 'normal';
            // 整型字段归一化: 表单留空提交的是空串, MySQL严格模式会报1366
            foreach (['expiretime', 'max_count'] as $intField) {
                if (isset($params[$intField]) && $params[$intField] === '') {
                    $params[$intField] = 0;
                }
            }

            $result = $this->model->allowField(true)->save($params);
            if ($result !== false) {
                $this->success();
            } else {
                $this->error($this->model->getError());
            }
        }

        return $this->view->fetch();
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
        } while (\think\Db::name('agent_invite')->where('invite_code', $code)->find());

        return $code;
    }

    /**
     * 删除（覆盖基类：加数据隔离，防止删到范围外的邀请码）
     */
    public function del($ids = '')
    {
        if (!$ids) {
            $this->error(__('Parameter %s can not be empty', 'ids'));
        }
        $scopeWhere = [];
        $scope = $this->getAgentScope();
        if ($scope !== null) {
            $scopeWhere = ['agent_id' => ['in', $scope]];
        }
        $count = $this->model->where('id', 'in', $ids)->where($scopeWhere)->delete();
        if ($count) {
            $this->success();
        }
        $this->error(__('No rows were deleted'));
    }

    /**
     * 批量操作（覆盖基类：status等字段修改同样受数据隔离约束）
     */
    public function multi($ids = '')
    {
        $ids = $ids ?: $this->request->param('ids');
        if (!$ids) {
            $this->error(__('Parameter %s can not be empty', 'ids'));
        }
        $scope = $this->getAgentScope();
        if ($scope !== null) {
            // 只对范围内记录生效：范围外的id直接过滤掉
            $allowed = $this->model->where('id', 'in', $ids)
                ->where('agent_id', 'in', $scope)
                ->column('id');
            if (!$allowed) {
                $this->error('没有权限操作这些记录');
            }
            $ids = implode(',', $allowed);
        }
        parent::multi($ids);
    }
}
