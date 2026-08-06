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
    }

    /**
     * 查看
     */
    public function index()
    {
        $this->relationSearch = false;

        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->model
                ->where($where)
                ->count();

            $list = $this->model
                ->where($where)
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

            // 生成唯一邀请码
            $params['invite_code'] = $this->generateUniqueCode();
            $params['used_count']  = 0;
            $params['status']      = 'normal';

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
}
