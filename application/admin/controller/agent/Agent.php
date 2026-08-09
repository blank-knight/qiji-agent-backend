<?php

namespace app\admin\controller\agent;

use app\common\controller\Backend;
use think\Db;

/**
 * 代理管理（总后台）
 */
class Agent extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Agent();
    }

    /**
     * 查看
     */
    public function index()
    {
        $this->relationSearch = false;

        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            // 层级数据隔离：贴牌/代理只能看自己的子树
            $scope = $this->getAgentScope();
            $currentAgent = $this->getCurrentAgent();

            // 构建scope条件数组
            $scopeWhere = [];
            if ($scope !== null) {
                $excludeIds = $currentAgent ? [$currentAgent['id']] : [];
                $visibleIds = array_values(array_diff($scope, $excludeIds));
                if ($visibleIds) {
                    $scopeWhere = ['id' => ['in', $visibleIds]];
                } else {
                    $scopeWhere = ['id' => 0]; // 无可见数据
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
                $row['user_count'] = Db::name('user')->where('agent_id', $row['id'])->count();
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

            // 权限校验：贴牌只能开代理，代理不能开代理
            if (!$this->isSuperAdmin()) {
                $currentAgent = $this->getCurrentAgent();
                if ($currentAgent) {
                    // 贴牌只能创建代理
                    $params['type'] = 'agent';
                    // 上级必须是当前贴牌自己
                    $params['agent_id'] = $currentAgent['id'];
                }
            }

            // type=agent 时必须有上级
            if (isset($params['type']) && $params['type'] === 'agent' && empty($params['agent_id'])) {
                $this->error('代理必须指定上级贴牌商');
            }
            // type=tiepai 时上级为0
            if (isset($params['type']) && $params['type'] === 'tiepai') {
                $params['agent_id'] = 0;
            }

            // 密码加密
            if (isset($params['password']) && $params['password']) {
                $salt = \fast\Random::alnum(6);
                $params['password'] = md5(md5($params['password']) . $salt);
                $params['salt'] = $salt;
            }

            $params['createtime'] = time();
            $params['updatetime'] = time();

            Db::startTrans();
            try {
                // 1. 先保存agent（不含path）
                $params['path'] = '/'; // 临时值，后面更新
                $result = $this->model->allowField(true)->save($params);
                if ($result === false) {
                    throw new \Exception($this->model->getError());
                }

                $newId = $this->model->id;

                // 2. 计算并更新path
                $path = \app\admin\model\Agent::computePath($newId, $params['agent_id']);
                $this->model->save(['path' => $path], ['id' => $newId]);

                // 3. 创建后台管理员账号（贴牌和代理都能登录后台）
                $this->createAdminForAgent($newId, $params);

                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
            $this->success();
        }

        // 准备上级选项
        $this->assignParentOptions();
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

        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');

            // type 不可更改（防止层级混乱）
            unset($params['type']);
            // 上级不可更改
            unset($params['agent_id']);

            // 密码为空则不改
            if (isset($params['password']) && !$params['password']) {
                unset($params['password']);
            } elseif (isset($params['password']) && $params['password']) {
                $salt = \fast\Random::alnum(6);
                $params['password'] = md5(md5($params['password']) . $salt);
                $params['salt'] = $salt;
            }

            $params['updatetime'] = time();

            $result = $row->save($params);
            if ($result !== false) {
                $this->success();
            } else {
                $this->error($row->getError());
            }
        }

        $this->view->assign('row', $row);
        $this->assignParentOptions();
        return $this->view->fetch();
    }

    /**
     * 为上级选项赋值给模板
     */
    private function assignParentOptions()
    {
        $parentOptions = [];
        if ($this->isSuperAdmin()) {
            // 超管可以选择任何贴牌作为上级，或 agent_id=0（直接设为贴牌）
            $parents = \app\admin\model\Agent::where('type', 'tiepai')->where('status', 'normal')->column('name', 'id');
            $parentOptions[0] = '总后台直挂（贴牌商）';
            foreach ($parents as $id => $name) {
                $parentOptions[$id] = $name;
            }
        }
        $this->view->assign('parentOptions', $parentOptions);
        $this->view->assign('isSuperAdmin', $this->isSuperAdmin());
    }

    /**
     * 为agent创建后台管理员账号
     */
    private function createAdminForAgent($agentId, $params)
    {
        // 创建后台管理员账号
        $adminData = [
            'username' => $params['username'],
            'nickname' => isset($params['name']) ? $params['name'] : $params['username'],
            'email'    => isset($params['email']) ? $params['email'] : '',
            'mobile'   => isset($params['mobile']) ? $params['mobile'] : '',
            'password' => $params['password'],
            'salt'     => $params['salt'],
            'status'   => 'normal',
            'group_id' => $this->getAgentGroupId($params['type']),
            'createtime' => time(),
            'updatetime' => time(),
        ];

        $adminId = Db::name('admin')->insertGetId($adminData);

        // 关联 agent.admin_id
        Db::name('agent')->where('id', $agentId)->update(['admin_id' => $adminId]);

        // 分配角色组
        $groupId = $this->getAgentGroupId($params['type']);
        if ($groupId) {
            Db::name('auth_group_access')->insert([
                'uid' => $adminId,
                'group_id' => $groupId
            ]);
        }
    }

    /**
     * 获取贴牌/代理对应的角色组ID
     */
    private function getAgentGroupId($type)
    {
        $groupName = ($type === 'tiepai') ? '贴牌商' : '代理';
        $group = Db::name('auth_group')->where('name', $groupName)->where('status', 'normal')->find();
        if (!$group) {
            // 自动创建角色组（rules留空，isSuperAdmin()检查rules含*，留空不会被误判为超管）
            $groupId = Db::name('auth_group')->insertGetId([
                'name' => $groupName,
                'pid' => 1, // 默认上级
                'status' => 'normal',
                'rules' => '', // 不用*，否则会被isSuperAdmin()误判
                'createtime' => time(),
                'updatetime' => time(),
            ]);
            return $groupId;
        }
        return $group['id'];
    }
}
