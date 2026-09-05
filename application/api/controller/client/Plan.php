<?php

namespace app\api\controller\client;

use app\common\controller\Api;
use app\common\model\ScoreLog;
use think\Db;

/**
 * Token 套餐购买接口（客户端个人中心）
 * 套餐展示：归属用户代理(含祖先链)的套餐 ∪ 平台套餐(agent_id=0，仅当无可用代理套餐时兜底)
 * 支付：易支付(epay)聚合通道，收款配置沿代理链取最近一个已配置节点
 */
class Plan extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();
        $this->initAuth();
    }

    private function initAuth()
    {
        $token = $this->getBearerToken();
        if ($token) {
            $this->auth->init($token);
        }
    }

    private function getBearerToken()
    {
        $header = $this->request->header('authorization', '');
        if ($header && preg_match('/Bearer\s+(.+)/i', $header, $matches)) {
            return trim($matches[1]);
        }
        return $this->request->request('token', '');
    }

    /**
     * 套餐列表
     * @ApiMethod (GET)
     */
    public function index()
    {
        $user = $this->auth->getUser();
        $agentId = $user ? (int)$user->agent_id : 0;

        // 候选集：用户所属代理的套餐 ∪ 祖先链套餐(代理未自建时继承上级推荐) ∪ 平台套餐
        $agentIds = [0];
        $payOwner = null; // 收款配置归属节点(沿祖先链最近的已配置者)
        if ($agentId) {
            $agent = Db::name('agent')->where('id', $agentId)->find();
            if ($agent) {
                // 祖先链（含自己）：path 逐级 + 自己
                $chain = [(int)$agent['id']];
                $parts = array_filter(explode('/', trim($agent['path'], '/')));
                foreach ($parts as $aid) {
                    $chain[] = (int)$aid;
                }
                // 最近的已配置收款的节点（自己优先，逐级向上）
                foreach ($chain as $aid) {
                    $node = $aid == $agent['id'] ? $agent : Db::name('agent')->where('id', $aid)->find();
                    if ($node && !empty($node['epay_url']) && !empty($node['epay_pid']) && !empty($node['epay_key'])) {
                        $payOwner = $node;
                        break;
                    }
                }
                // 展示顺序：最近祖先优先（自己→贴牌→平台兜底）
                $agentIds = array_merge($chain, [0]);
            }
        }

        // 逐级取：任何一级有自己的上架套餐则用该级+更远级(允许贴牌定标准、代理补充)
        // 简化语义：展示 = 自己代理 + 全部祖先 + 平台，按 weigh 排序，同级去重不必(套餐本身独立)
        $plans = Db::name('score_plan')
            ->where('agent_id', 'in', $agentIds)
            ->where('status', 'normal')
            ->order('weigh', 'desc')
            ->order('id', 'asc')
            ->select();

        // 平台收款兜底：代理链无人配置时，平台站点配置可用则开启
        if (!$payOwner) {
            $siteUrl = config('site.epay_url') ?: '';
            $sitePid = config('site.epay_pid') ?: '';
            $siteKey = config('site.epay_key') ?: '';
            if ($siteUrl && $sitePid && $siteKey) {
                $payOwner = true;
            }
        }

        $list = [];
        foreach ($plans as $p) {
            $list[] = [
                'id'     => (int)$p['id'],
                'name'   => $p['name'],
                'score'  => (int)$p['score'],
                'price'  => round((float)$p['price'], 2),
                'remark' => $p['remark'],
            ];
        }

        $this->success('', [
            'plans'       => $list,
            'pay_enabled' => $payOwner ? 1 : 0,
        ]);
    }

    /**
     * 下单：生成易支付跳转链接
     * @ApiMethod (POST)
     */
    public function order()
    {
        if (!$this->auth->isLogin()) {
            $this->error('请先登录', null, 401);
        }
        $user = $this->auth->getUser();
        $planId = (int)$this->request->post('plan_id', 0);
        $plan = Db::name('score_plan')->where('id', $planId)->where('status', 'normal')->find();
        if (!$plan) {
            $this->error('套餐不存在或已下架');
        }

        // 套餐可见性：归属必须是 用户代理链∪平台
        $agentId = (int)$user->agent_id;
        $visible = [(int)$plan['agent_id']] === [0];
        $chain = [0];
        if ($agentId) {
            $agent = Db::name('agent')->where('id', $agentId)->find();
            if ($agent) {
                $chain[] = (int)$agent['id'];
                foreach (array_filter(explode('/', trim($agent['path'], '/'))) as $aid) {
                    $chain[] = (int)$aid;
                }
            }
        }
        if (!in_array((int)$plan['agent_id'], $chain)) {
            $this->error('该套餐不适用于你的账号');
        }

        // 收款配置：沿祖先链就近查找——代理自己优先，逐级向上，平台兜底
        // （与 index() 的 pay_enabled 判定顺序一致：自己配了钱进自己，不用上级的）
        $payOwner = null;
        foreach ($chain as $aid) {
            if (!$aid) {
                // 平台收款配置：站点配置（链尾兜底）
                $siteEpay = [
                    'id' => 0,
                    'epay_url' => config('site.epay_url') ?: '',
                    'epay_pid' => config('site.epay_pid') ?: '',
                    'epay_key' => config('site.epay_key') ?: '',
                ];
                if ($siteEpay['epay_url'] && $siteEpay['epay_pid'] && $siteEpay['epay_key']) {
                    $payOwner = $siteEpay;
                    break;
                }
                continue;
            }
            $node = Db::name('agent')->where('id', $aid)->find();
            if ($node && !empty($node['epay_url']) && !empty($node['epay_pid']) && !empty($node['epay_key'])) {
                $payOwner = $node;
                break;
            }
        }
        if (!$payOwner) {
            $this->error('暂未开通在线支付，请联系你的服务商');
        }

        // 订单号：日期+随机
        $orderNo = date('YmdHis') . mt_rand(1000, 9999);
        // 实际收款方（agent_id；平台收款=0）——对账/感知用
        $payeeAgentId = isset($payOwner['id']) ? (int)$payOwner['id'] : 0;
        $orderData = [
            'order_no'   => $orderNo,
            'user_id'    => (int)$user->id,
            'agent_id'   => (int)$plan['agent_id'],
            'plan_id'    => (int)$plan['id'],
            'plan_name'  => $plan['name'],
            'score'      => (int)$plan['score'],
            'price'      => round((float)$plan['price'], 2),
            'status'     => 'pending',
            'payee_agent_id' => $payeeAgentId,
            'createtime' => time(),
            'updatetime' => time(),
        ];
        Db::name('score_order')->insert($orderData);

        // 易支付参数
        $notifyUrl = $this->request->domain() . '/api/client/v1/plan/notify';
        $returnUrl = $this->request->domain() . '/api/client/v1/plan/return';
        $params = [
            'pid'          => $payOwner['epay_pid'],
            'type'         => $this->request->post('pay_type', 'alipay'),
            'out_trade_no' => $orderNo,
            'notify_url'   => $notifyUrl,
            'return_url'   => $returnUrl,
            'name'         => 'Token套餐-' . $plan['name'],
            'money'        => sprintf('%.2f', $orderData['price']),
            'sitename'     => 'Token充值',
        ];
        ksort($params);
        $signStr = http_build_query($params);
        $sign = md5($signStr . $payOwner['epay_key']);
        $params['sign'] = $sign;
        $params['sign_type'] = 'MD5';
        $payUrl = rtrim($payOwner['epay_url'], '/') . '/submit.php?' . http_build_query($params);

        Db::name('score_order')->where('order_no', $orderNo)->update(['pay_url' => $payUrl]);

        $this->success('', [
            'order_no' => $orderNo,
            'pay_url'  => $payUrl,
        ]);
    }

    /**
     * 支付结果查询（客户端轮询）
     * @ApiMethod (GET)
     */
    public function status()
    {
        if (!$this->auth->isLogin()) {
            $this->error('请先登录', null, 401);
        }
        $orderNo = trim($this->request->get('order_no', ''));
        $order = Db::name('score_order')
            ->where('order_no', $orderNo)
            ->where('user_id', (int)$this->auth->id)
            ->find();
        if (!$order) {
            $this->error('订单不存在');
        }
        $this->success('', [
            'order_no' => $order['order_no'],
            'status'   => $order['status'],
            'score'    => (int)$order['score'],
        ]);
    }

    /**
     * 易支付异步回调
     * @ApiMethod (GET)
     */
    public function notify()
    {
        $params = $this->request->get();
        $sign = $params['sign'] ?? '';
        unset($params['sign'], $params['sign_type']);
        ksort($params);
        $signStr = http_build_query($params);

        $orderNo = $params['out_trade_no'] ?? '';
        $order = Db::name('score_order')->where('order_no', $orderNo)->find();
        if (!$order) {
            return response('order not found');
        }

        // 验签 key：下单时的收款配置方
        $verifyKey = $this->getOrderEpayKey($order);
        if (!$verifyKey || md5($signStr . $verifyKey) !== $sign) {
            return response('sign error');
        }

        // 状态：易支付 trade_status=TRADE_SUCCESS
        if (($params['trade_status'] ?? '') !== 'TRADE_SUCCESS') {
            return response('success'); // 非成功态直接确认收讫
        }

        if ($order['status'] === 'paid') {
            return response('success'); // 幂等
        }

        Db::startTrans();
        try {
            // 原子翻状态：pending→paid 防双发
            $claimed = Db::name('score_order')
                ->where('id', $order['id'])
                ->where('status', 'pending')
                ->update([
                    'status'     => 'paid',
                    'trade_no'   => $params['trade_no'] ?? '',
                    'paytime'    => time(),
                    'updatetime' => time(),
                ]);
            if (!$claimed) {
                Db::rollback();
                return response('success'); // 并发已处理
            }

            $user = Db::name('user')->where('id', $order['user_id'])->find();
            $before = (int)$user['score'];
            $after  = $before + (int)$order['score'];
            Db::name('user')->where('id', $order['user_id'])->setInc('score', (int)$order['score']);
            ScoreLog::create([
                'user_id' => $order['user_id'],
                'score'   => (int)$order['score'],
                'before_score' => $before,
                'after_score'  => $after,
                'memo'    => '套餐购买[' . $order['plan_name'] . ']',
                'model'   => '',
                'input_tokens'  => 0,
                'output_tokens' => 0,
                'request_id'    => 'plan-' . $order['id'],
            ]);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return response('fail');
        }
        return response('success');
    }

    /**
     * 同步回跳（用户浏览器落地页）
     */
    public function orderreturn()
    {
        // 不做业务（以 notify 为准），给一个轻量提示页
        return response('<!DOCTYPE html><html><head><meta charset="utf-8"><title>支付完成</title></head><body style="font-family:sans-serif;text-align:center;padding-top:80px"><h2>支付完成</h2><p>积分将在几秒内到账，请回到客户端查看</p></body></html>');
    }

    /**
     * 取订单验签key：agent_id=0→站点配置；否则该代理(或其链上下单时选中的收款方)的key
     * 注：下单时收款方可能不是套餐归属方（代理无收款配置时用贴牌的），
     *     验签沿同一逻辑再找一遍——若归属方和收款方不同，以链上最近已配置者为准（与下单逻辑一致）
     */
    private function getOrderEpayKey($order)
    {
        $chain = [(int)$order['agent_id']];
        if ($order['agent_id']) {
            $agent = Db::name('agent')->where('id', $order['agent_id'])->find();
            if ($agent) {
                foreach (array_filter(explode('/', trim($agent['path'], '/'))) as $aid) {
                    $chain[] = (int)$aid;
                }
            }
        }
        foreach ($chain as $aid) {
            if (!$aid) {
                $k = config('site.epay_key') ?: '';
                $u = config('site.epay_url') ?: '';
                $p = config('site.epay_pid') ?: '';
                if ($k && $u && $p) {
                    return $k;
                }
                continue;
            }
            $node = Db::name('agent')->where('id', $aid)->find();
            if ($node && !empty($node['epay_url']) && !empty($node['epay_pid']) && !empty($node['epay_key'])) {
                return $node['epay_key'];
            }
        }
        return '';
    }
}
