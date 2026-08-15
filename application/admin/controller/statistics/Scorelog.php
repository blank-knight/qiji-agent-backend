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

            // 批量取用户名：不要在循环里用 db()（每次都会新建 PDO 连接，
            // SQLite 下多连接读写锁冲突会导致请求结束时 session 写入死锁）
            $userIds = [];
            foreach ($list as $row) {
                $userIds[] = $row['user_id'];
            }
            $usernames = $userIds
                ? \think\Db::name('user')->where('id', 'in', $userIds)->column('username', 'id')
                : [];
            foreach ($list as &$row) {
                $row['username'] = isset($usernames[$row['user_id']]) ? $usernames[$row['user_id']] : '';
            }
            unset($row);

            $result = ['total' => $total, 'rows' => $list];
            return json($result);
        }

        return $this->view->fetch();
    }
}
