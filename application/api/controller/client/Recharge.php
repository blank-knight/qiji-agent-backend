<?php

namespace app\api\controller\client;

use app\common\controller\Api;
use app\common\model\ScoreLog;
use think\Db;

/**
 * 充值码兑换接口（客户端个人中心）
 */
class Recharge extends Api
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
     * 兑换充值码
     * @ApiMethod (POST)
     */
    public function redeem()
    {
        if (!$this->auth->isLogin()) {
            $this->error('请先登录', null, 401);
        }
        $user = $this->auth->getUser();

        $code = strtoupper(trim($this->request->post('code', '')));
        if (!$code) {
            $this->error('请输入充值码');
        }

        Db::startTrans();
        try {
            // 不用 lock(true)：SQLite 不支持 FOR UPDATE 语法会直接抛异常；
            // 防双花靠下方「仅 unused 状态可翻 used」的条件原子更新
            $card = Db::name('recharge_code')->where('code', $code)->find();
            if (!$card) {
                $this->error('充值码不存在');
            }
            if ($card['status'] === 'used') {
                $this->error('充值码已被使用');
            }
            if ($card['status'] !== 'unused') {
                $this->error('充值码已停用');
            }

            // 归属校验：非通用码(0)时，用户所属代理必须在发码代理子树内
            if ($card['agent_id']) {
                $userAgentId = (int)$user->agent_id;
                if (!$userAgentId) {
                    $this->error('该充值码不适用于你的账号');
                }
                $cardAgent = Db::name('agent')->where('id', $card['agent_id'])->find();
                $userAgent = Db::name('agent')->where('id', $userAgentId)->find();
                if (!$cardAgent || !$userAgent || strpos($userAgent['path'], $cardAgent['path']) !== 0) {
                    $this->error('该充值码不适用于你的账号');
                }
            }

            // 原子核销：只有 unused 状态能翻成 used，防止并发窗口双兑
            $claimed = Db::name('recharge_code')
                ->where('id', $card['id'])
                ->where('status', 'unused')
                ->update([
                    'status'     => 'used',
                    'used_by'    => $user->id,
                    'used_at'    => time(),
                    'updatetime' => time(),
                ]);
            if (!$claimed) {
                Db::rollback();
                $this->error('充值码已被使用');
            }

            $before = (int)$user->score;
            $after  = $before + (int)$card['score'];

            // 到账 + 流水
            Db::name('user')->where('id', $user->id)->setInc('score', (int)$card['score']);
            ScoreLog::create([
                'user_id' => $user->id,
                'score'   => (int)$card['score'],
                'before_score' => $before,
                'after_score'  => $after,
                'memo'    => '充值码兑换',
                'model'   => '',
                'input_tokens'  => 0,
                'output_tokens' => 0,
                'request_id'    => 'recharge-' . $card['id'],
            ]);

            Db::commit();
        } catch (\think\exception\HttpResponseException $e) {
            // $this->error() 的正常业务拒绝，直接透传响应
            throw $e;
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('兑换失败，请稍后重试');
        }

        $this->success('充值成功', [
            'added_score'     => (int)$card['score'],
            'remaining_score' => $after,
        ]);
    }
}

