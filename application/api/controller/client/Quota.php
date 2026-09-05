<?php

namespace app\api\controller\client;

use app\common\controller\Api;
use app\common\model\ScoreLog;
use think\Db;

/**
 * Token 额度管理接口
 */
class Quota extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();
        $this->initAuth();
    }

    /**
     * 手动通过 Bearer token 初始化认证
     */
    private function initAuth()
    {
        $token = $this->getBearerToken();
        if ($token) {
            $this->auth->init($token);
        }
    }

    /**
     * 从 Authorization Header 获取 Bearer token
     */
    private function getBearerToken()
    {
        $header = $this->request->header('authorization', '');
        if ($header && preg_match('/Bearer\s+(.+)/i', $header, $matches)) {
            return trim($matches[1]);
        }
        return $this->request->request('token', '');
    }

    /**
     * 查询当前额度
     * @ApiMethod (GET)
     */
    public function index()
    {
        if (!$this->auth->isLogin()) {
            $this->error('请先登录', null, 401);
        }

        $user = $this->auth->getUser();
        $mode = $user->score > 0 ? 'formal' : 'trial';

        $this->success('', [
            'score'         => (int)$user->score,
            'mode'          => $mode,
            'is_custom_key' => (int)$user->is_custom_key,
        ]);
    }

    /**
     * 上报 token 用量（客户端每次 LLM 调用后调用）
     * @ApiMethod (POST)
     */
    public function report()
    {
        if (!$this->auth->isLogin()) {
            $this->error('请先登录', null, 401);
        }

        $user = $this->auth->getUser();

        // 自定义 key 用户不上报
        if ($user->is_custom_key) {
            $this->success('自定义Key用户无需上报', [
                'remaining_score' => (int)$user->score,
            ]);
        }

        $model        = $this->request->post('model', '');
        $inputTokens  = (int)$this->request->post('input_tokens', 0);
        $outputTokens = (int)$this->request->post('output_tokens', 0);
        $requestId    = $this->request->post('request_id', '');

        if (!$requestId) {
            $this->error('request_id 不能为空');
        }

        if ($inputTokens <= 0 && $outputTokens <= 0) {
            $this->error('token 数量必须大于0');
        }

        // 幂等去重
        $existLog = ScoreLog::where('request_id', $requestId)->find();
        if ($existLog) {
            $this->success('已上报过', [
                'remaining_score' => (int)$user->score,
            ]);
        }

        $totalTokens = $inputTokens + $outputTokens;

        $tokenPerScore = (int)$this->getSiteConfig('token_per_score', 10000);
        if ($tokenPerScore <= 0) {
            $tokenPerScore = 10000;
        }

        $deductScore = (int)ceil($totalTokens / $tokenPerScore);
        if ($deductScore <= 0) {
            $deductScore = 1;
        }

        if ($user->score < $deductScore) {
            $this->error('额度不足，请联系代理充值', [
                'remaining_score' => (int)$user->score,
            ], 429);
        }

        Db::startTrans();
        try {
            Db::name('user')->where('id', $user->id)->dec('score', $deductScore)->update(['last_report_time' => time()]);

            ScoreLog::create([
                'user_id'       => $user->id,
                'score'         => -$deductScore,
                'before_score'  => $user->score,
                'after_score'   => $user->score - $deductScore,
                'memo'          => 'Token消耗：' . $model,
                'model'         => $model,
                'input_tokens'  => $inputTokens,
                'output_tokens' => $outputTokens,
                'request_id'    => $requestId,
            ]);

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('上报失败：' . $e->getMessage());
        }

        $newScore = (int)Db::name('user')->where('id', $user->id)->value('score');

        $this->success('上报成功', [
            'remaining_score' => $newScore,
        ]);
    }

    private function getSiteConfig($key, $default = '')
    {
        $value = config('site.' . $key);
        if ($value === null || $value === '') {
            return $default;
        }
        return $value;
    }
}
