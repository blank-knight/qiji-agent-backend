<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;
use think\Db;

/**
 * Token 套餐管理
 * 超管/贴牌/代理均可设置自己名下的推荐套餐，客户端个人中心展示并可直接购买
 */
class Scoreplan extends Backend
{

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\ScorePlan;
    }

    /**
     * 查看
     */
    public function index()
    {
        $this->relationSearch = false;
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            // 层级隔离：只能看自己(含子树代理)创建的套餐
            $scopeAgentIds = $this->getScopeAgentIds();

            $list = $this->model
                ->where($where)
                ->where('agent_id', 'in', $scopeAgentIds)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $total = $this->model
                ->where($where)
                ->where('agent_id', 'in', $scopeAgentIds)
                ->count();

            foreach ($list as &$row) {
                $row['agent_name'] = $row['agent_id'] ? (Db::name('agent')->where('id', $row['agent_id'])->value('name') ?: ('#' . $row['agent_id'])) : '平台';
            }
            $result = ['total' => $total, 'rows' => collection($list)->toArray()];
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 归属代理ID集合：超管=全部，贴牌/代理=自己+子树
     */
    private function getScopeAgentIds()
    {
        if ($this->isSuperAdmin()) {
            // 超管可见全部：含平台套餐(agent_id=0)
            return array_merge([0], array_map('intval', Db::name('agent')->column('id')));
        }
        $currentAgent = $this->getCurrentAgent();
        if (!$currentAgent) {
            return [0]; // 无代理身份：空集(0=平台套餐也不可见)
        }
        // 自己 + path 前缀匹配的子树
        $ids = Db::name('agent')->where('path', 'like', $currentAgent['path'] . '%')->column('id');
        $ids[] = $currentAgent['id'];
        return array_map('intval', array_unique($ids));
    }

    /**
     * 添加：归属自动取当前身份
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            if (!$params) {
                $this->error(__('Parameter %s can not be empty', ''));
            }
            // 归属：超管=平台(0)，贴牌/代理=自己
            $params['agent_id'] = 0;
            if (!$this->isSuperAdmin()) {
                $currentAgent = $this->getCurrentAgent();
                if (!$currentAgent) {
                    $this->error('无代理身份，无法创建套餐');
                }
                $params['agent_id'] = (int)$currentAgent['id'];
            }
            // 校验
            $params['score']  = (int)$params['score'];
            $params['price']  = round((float)$params['price'], 2);
            $params['weigh']  = (int)($params['weigh'] ?? 0);
            if ($params['score'] <= 0) {
                $this->error('Token数量必须大于0');
            }
            if ($params['price'] < 0) {
                $this->error('价格不能为负');
            }
            $params['createtime'] = time();
            $params['updatetime'] = time();
            $this->model->allowField(true)->save($params);
            $this->success();
        }
        return $this->view->fetch();
    }

    /**
     * 编辑：仅限自己子树内的套餐
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if (!in_array((int)$row['agent_id'], $this->getScopeAgentIds()) && !(int)$row['agent_id'] === 0 && $this->isSuperAdmin()) {
            // 超管可编辑平台套餐(agent_id=0)；其余按子树校验
        }
        if (!$this->isSuperAdmin() && !in_array((int)$row['agent_id'], $this->getScopeAgentIds())) {
            $this->error('无权编辑该套餐');
        }

        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            unset($params['agent_id']); // 归属不可改
            $params['score']  = (int)$params['score'];
            $params['price']  = round((float)$params['price'], 2);
            if ($params['score'] <= 0) {
                $this->error('Token数量必须大于0');
            }
            $params['updatetime'] = time();
            $row->allowField(true)->save($params);
            $this->success();
        }
        $this->view->assign('row', $row);
        return $this->view->fetch();
    }

    /**
     * 删除：仅限自己子树内的套餐
     */
    public function del($ids = '')
    {
        $ids = $ids ? explode(',', $ids) : [];
        if (!$ids) {
            $this->error('参数错误');
        }
        $scope = $this->getScopeAgentIds();
        foreach ($ids as $id) {
            $row = $this->model->get($id);
            if ($row && ($this->isSuperAdmin() || in_array((int)$row['agent_id'], $scope))) {
                $row->delete();
            }
        }
        $this->success();
    }
}
