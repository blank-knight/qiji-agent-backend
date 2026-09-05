<?php

namespace app\admin\controller\agent;

use app\common\controller\Backend;
use think\Db;
use think\Session;

/**
 * 代理管理（总后台）
 */
class Agent extends Backend
{
    protected $model = null;
    protected $noNeedRight = ['loginas'];

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
                // 上级名称（贴牌显示"总后台"，代理显示上级贴牌名）
                if ($row['type'] === 'tiepai' || !$row['agent_id']) {
                    $row['parent_name'] = '总后台';
                } else {
                    $row['parent_name'] = Db::name('agent')->where('id', $row['agent_id'])->value('name') ?: ('#' . $row['agent_id']);
                }
                // 当前登录者能否越权进入该代理后台（超管或其祖先）
                $row['can_impersonate'] = false;
                if ($row['admin_id'] && $this->isSuperAdmin()) {
                    $row['can_impersonate'] = true;
                } elseif ($row['admin_id'] && $currentAgent) {
                    $row['can_impersonate'] = strpos($row['path'], $currentAgent['path']) === 0;
                }
            }

            $result = ['total' => $total, 'rows' => $list];
            return json($result);
        }

        return $this->view->fetch();
    }

    /**
     * 越权进入下级后台（超管进贴牌/代理，贴牌进自己的代理）
     */
    public function loginas($ids = null)
    {
        $target = $this->model->get($ids);
        if (!$target || !$target['admin_id']) {
            $this->error('该记录不存在或未关联后台账号');
        }

        // 权限校验：目标必须是自己或自己的下级（祖先链校验，杜绝横向越权）
        $currentAgent = $this->getCurrentAgent();
        $currentAgentId = $currentAgent ? (int)$currentAgent['id'] : 0;
        $targetPath = $target['path']; // 形如 /3/4/
        if (!$this->isSuperAdmin()) {
            if (!$currentAgent) {
                $this->error('无权限');
            }
            // 自己的path必须是目标path的前缀（自己是目标的祖先）
            $myPath = $currentAgent['path'];
            if (strpos($targetPath, $myPath) !== 0) {
                $this->error('只能进入自己或下级的后台');
            }
        }

        if ($this->request->isPost()) {
            // 一次性票据：写数据库（新标签走 imp.php 独立会话，session 不可跨会话读）
            // 单发短时效：60 秒内未领取即作废；领取即删。
            $ticket = md5(uniqid(mt_rand(), true));
            Db::name('impersonate_ticket')->insert([
                'ticket'    => $ticket,
                'from_admin' => (int)$this->auth->id,
                'to_admin'  => (int)$target['admin_id'],
                'agent_id'  => (int)$target['id'],
                'createtime' => time(),
                'expiretime' => time() + 60,
            ]);
            $this->success('', null, ['ticket' => $ticket, 'entry' => 'imp.php']);
        }

        $this->error('无效请求');
    }

    /**
     * 添加
     */

    /**
     * API配置三选一（编辑/新增共用）：
     *   inherit=使用上级(默认) custom=由我指定 self=允许其自行配置
     * 映射：is_custom_key = (mode==custom) ；allow_model_config = (mode==self)
     * self 时保留已填的 custom 字段作兜底（不覆盖不清空）
     * api_key 统一 base64 入库（与 Modelconfig 页一致，读取端统一 base64_decode）
     * models 归一化：中英逗号切分/trim/滤非法/去重/上限50，英文逗号join
     */
    private function applyApiMode(array &$params, $existing = null)
    {
        $mode = isset($params['api_mode']) ? $params['api_mode'] : 'inherit';
        unset($params['api_mode']);

        // 允许自行配置的授权校验（沿用原 allow_model_config 规则：仅超管或直系上级）
        if ($mode === 'self') {
            if (!$this->isSuperAdmin()) {
                $currentAgent = $this->getCurrentAgent();
                $targetPath = $existing ? $existing['path'] : '';
                if (!($currentAgent && $targetPath && strpos($targetPath, $currentAgent['path']) === 0)) {
                    $mode = 'inherit'; // 无权授权则回落默认
                }
            }
        }

        if ($mode === 'self') {
            $params['allow_model_config'] = 1;
            $params['is_custom_key'] = $existing ? ($existing['is_custom_key'] ? 1 : 0) : 0;
            // 历史代填字段保留（若上级曾通过自助页代配），其未自行配置前仍按此下发
            unset($params['base_url'], $params['api_key'], $params['models']);
        } else {
            // inherit：回到默认——清自定义与授权
            $params['is_custom_key'] = 0;
            $params['allow_model_config'] = 0;
            $params['base_url'] = '';
            $params['api_key'] = '';
            $params['models'] = '';
        }
    }

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

            // API配置三选一映射
            $this->applyApiMode($params);

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
        // 新增页授权开关：超管恒可；贴牌建自己下级可（POST 已强校验归属）
        $canGrantModelConfig = false;
        if ($this->isSuperAdmin()) {
            $canGrantModelConfig = true;
        } else {
            $currentAgent = $this->getCurrentAgent();
            if ($currentAgent) {
                $canGrantModelConfig = true;
            }
        }
        $this->view->assign('canGrantModelConfig', $canGrantModelConfig);
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

            // API配置三选一映射（含 allow_model_config 授权校验）
            $this->applyApiMode($params, $row);

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

        // 补充 username 和 type_text（存在 admin 表，agent 表无此字段）
        $row['username'] = Db::name('admin')->where('id', $row['admin_id'])->value('username');
        $row['type_text'] = $row['type'] === 'tiepai' ? '贴牌商' : '代理';

        // 大模型配置授权开关：超管对所有人可设；贴牌只对自己的下级可设；代理无权设置
        $canGrantModelConfig = false;
        if ($this->isSuperAdmin()) {
            $canGrantModelConfig = true;
        } else {
            $currentAgent = $this->getCurrentAgent();
            if ($currentAgent && $currentAgent['id'] != $ids && strpos($row['path'], $currentAgent['path']) === 0) {
                $canGrantModelConfig = true;
            }
        }
        $this->view->assign('canGrantModelConfig', $canGrantModelConfig);

        // API配置三选一回显：self 优先于 custom（都开时显示 self，custom 字段保留兜底）
        $apiMode = 'inherit';
        if (!empty($row['allow_model_config'])) {
            $apiMode = 'self';
        }
        $this->view->assign('api_mode', $apiMode);
        // api_key 库里是 base64，表单回显明文
        $row['api_key_plain'] = $row['api_key'] ? base64_decode($row['api_key']) : '';

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
