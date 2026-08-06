<?php

namespace app\admin\controller\statistics;

use app\common\controller\Backend;

/**
 * Token 消耗记录
 */
class Scorelog extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\ScoreLog();
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
                $row['username'] = db('user')->where('id', $row['user_id'])->value('username');
            }

            $result = ['total' => $total, 'rows' => $list];
            return json($result);
        }

        return $this->view->fetch();
    }
}
