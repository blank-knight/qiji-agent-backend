<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;
use app\admin\model\User as UserModel;
use think\Db;
use think\Validate;

/**
 * 会员管理
 */
class User extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new UserModel();
    }

    /**
     * 查看
     */
    public function index()
    {
        $this->relationSearch = false;

        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            // 去除排序字段的表名前缀（如 user.id => id）
            $sort = preg_replace('/^\w+\./', '', $sort);

            // 层级数据隔离：贴牌看旗下代理用户，代理只看自己用户
            $scope = $this->getUserScope();
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
                $row['agent_name'] = $row['agent_id'] ? Db::name('agent')->where('id', $row['agent_id'])->value('name') : '';
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

            // 密码加密
            if (isset($params['password']) && $params['password']) {
                $salt = \fast\Random::alnum(6);
                $params['password'] = md5(md5($params['password']) . $salt);
                $params['salt'] = $salt;
            }

            $params['jointime'] = time();
            $params['createtime'] = time();
            $params['updatetime'] = time();

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

            // 密码为空则不修改
            if (isset($params['password']) && !$params['password']) {
                unset($params['password']);
            } elseif (isset($params['password']) && $params['password']) {
                $salt = \fast\Random::alnum(6);
                $params['password'] = md5(md5($params['password']) . $salt);
                $params['salt'] = $salt;
            }

            // 处理自定义 API Key
            $apiKeyPlain = '';
            if (isset($params['api_key_plain'])) {
                $apiKeyPlain = $params['api_key_plain'];
                unset($params['api_key_plain']);
            }
            if (isset($params['is_custom_key']) && $params['is_custom_key'] == 1 && $apiKeyPlain) {
                $params['api_key_encrypted'] = base64_encode($apiKeyPlain);
            }

            $params['updatetime'] = time();

            // 触发配额继承钩子
            $result = $this->model->save($params, ['id' => $ids]);
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
     * 充值（修改积分）
     */
    public function score($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }

        if ($this->request->isPost()) {
            $score = (int)$this->request->post('score', 0);
            $type = $this->request->post('type', 'inc'); // inc or dec
            $memo = $this->request->post('memo', '');

            $oldScore = (int)$row['score'];
            if ($type == 'inc') {
                $newScore = $oldScore + $score;
            } else {
                $newScore = $oldScore - $score;
            }

            if ($newScore < 0) {
                $this->error('扣除后积分不能为负数');
            }

            // 更新用户积分（会触发配额继承钩子）
            $this->model->save(['score' => $newScore], ['id' => $ids]);

            // 记录日志
            \app\common\model\ScoreLog::create([
                'user_id' => $ids,
                'score'   => $type == 'inc' ? $score : -$score,
                'before'  => $oldScore,
                'after'   => $newScore,
                'memo'    => $memo ?: '后台手动调整',
            ]);

            $this->success('操作成功');
        }

        $this->view->assign('row', $row);
        return $this->view->fetch();
    }
}
