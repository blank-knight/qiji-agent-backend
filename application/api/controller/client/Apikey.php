<?php

namespace app\api\controller\client;

use app\common\controller\Api;
use think\Db;

/**
 * API Key 管理接口
 * 层级继承：用户 → 代理 → 贴牌 → 系统
 */
class Apikey extends Api
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
     * 查询当前 Key 配置
     * @ApiMethod (GET)
     */
    public function index()
    {
        if (!$this->auth->isLogin()) {
            $this->error('请先登录', null, 401);
        }

        $user = $this->auth->getUser();

        $apiKey        = '';
        $keySource     = 'system';
        $keySourceName = '系统默认';

        if ($user->is_custom_key && $user->api_key_encrypted) {
            $apiKey        = $this->decryptKey($user->api_key_encrypted);
            $keySource     = 'user';
            $keySourceName = '自定义';
        } elseif ($user->agent_id) {
            $agent = Db::name('agent')->where('id', $user->agent_id)->find();
            if ($agent) {
                if (!empty($agent['api_key'])) {
                    $apiKey        = $this->decryptKey($agent['api_key']);
                    $keySource     = 'agent';
                    $keySourceName = $agent['name'] ?: $agent['username'] ?: '代理';
                }

                if (!$apiKey && !empty($agent['agent_id'])) {
                    $parent = Db::name('agent')->where('id', $agent['agent_id'])->find();
                    if ($parent && !empty($parent['api_key'])) {
                        $apiKey        = $this->decryptKey($parent['api_key']);
                        $keySource     = 'tiepai';
                        $keySourceName = $parent['name'] ?: $parent['username'] ?: '贴牌商';
                    }
                }
            }
        }

        if (!$apiKey) {
            $apiKey = config('site.default_api_key') ?: '';
        }

        $this->success('', [
            'is_custom_key'  => (int)$user->is_custom_key,
            'api_key'        => $apiKey,
            'key_source'     => $keySource,
            'key_source_name'=> $keySourceName,
            'can_customize'  => (int)$user->is_custom_key ? true : false,
        ]);
    }

    /**
     * 设置自定义 Key（仅 is_custom_key=1 可用）
     * @ApiMethod (POST)
     */
    public function customize()
    {
        if (!$this->auth->isLogin()) {
            $this->error('请先登录', null, 401);
        }

        $user = $this->auth->getUser();

        if (!$user->is_custom_key) {
            $this->error('您没有自定义 Key 的权限');
        }

        $apiKey = $this->request->post('api_key', '');
        if (!$apiKey) {
            $this->error('api_key 不能为空');
        }

        $encrypted = $this->encryptKey($apiKey);

        Db::name('user')->where('id', $user->id)->update([
            'api_key_encrypted' => $encrypted,
        ]);

        $this->success('设置成功');
    }

    /**
     * 加密 Key（base64 占位，后续可换 AES）
     */
    private function encryptKey($plain)
    {
        return base64_encode($plain);
    }

    /**
     * 解密 Key
     */
    private function decryptKey($encrypted)
    {
        if (!$encrypted) {
            return '';
        }
        return base64_decode($encrypted);
    }
}
