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
            // 沿 agent path 逐级向上查找第一个有 api_key 的节点
            $result = $this->resolveKeyFromPath($user->agent_id);
            if ($result) {
                $apiKey        = $result['api_key'];
                $keySource     = $result['source'];
                $keySourceName = $result['name'];
            }
        }

        if (!$apiKey) {
            $apiKey = config('site.default_api_key') ?: '';
        }

        // base_url/models 沿代理继承链取（代理自定义了 API 配置则覆盖系统默认）
        $baseUrl = config('site.default_base_url') ?: '';
        $models = [];
        if ($user->agent_id) {
            $agent = Db::name('agent')->where('id', $user->agent_id)->find();
            if ($agent) {
                $node = $agent;
                $checked = [];
                while ($node && !in_array($node['id'], $checked)) {
                    $checked[] = $node['id'];
                    if (!empty($node['is_custom_key']) && ($node['base_url'] || $node['models'])) {
                        if ($node['base_url']) {
                            $baseUrl = $node['base_url'];
                        }
                        if ($node['models']) {
                            $models = array_values(array_filter(array_map('trim', preg_split('/[,，]/u', $node['models']))));
                        }
                        break;
                    }
                    if (empty($node['agent_id'])) {
                        break;
                    }
                    $node = Db::name('agent')->where('id', $node['agent_id'])->find();
                }
            }
        }

        // 平台是否允许平台用户自选模型（site 配置，默认允许；代理链 models 非空时强制限定）
        $allowModelSelect = (int)(config('site.allow_model_select') ?? 1);
        if (!empty($models)) {
            // 代理限定了模型清单：允许选择，但仅限清单内（前端过滤）
            $allowModelSelect = 1;
        }

        $this->success('', [
            'is_custom_key'  => (int)$user->is_custom_key,
            'api_key'        => $apiKey,
            'base_url'       => $baseUrl,
            'models'         => $models,
            'key_source'     => $keySource,
            'key_source_name'=> $keySourceName,
            'can_customize'  => (int)$user->is_custom_key ? true : false,
            'allow_model_select' => $allowModelSelect,
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

    /**
     * 沿 agent path 逐级向上查找第一个有 api_key 的节点
     * @return array|null ['api_key'=>..., 'source'=>..., 'name'=>...]
     */
    private function resolveKeyFromPath($agentId)
    {
        $agent = Db::name('agent')->where('id', $agentId)->find();
        if (!$agent) {
            return null;
        }

        // 从 path 中提取祖先ID（从近到远）
        // path 格式: /1/5/12/
        $pathParts = array_filter(explode('/', trim($agent['path'], '/')));
        if (!$pathParts) {
            return null;
        }

        // 倒序：先查最近的上级，再查更远的
        $pathParts = array_reverse($pathParts);
        foreach ($pathParts as $ancestorId) {
            if ($ancestorId == $agentId) {
                continue; // 跳过自己
            }
            $ancestor = Db::name('agent')->where('id', $ancestorId)->find();
            if ($ancestor && !empty($ancestor['api_key'])) {
                return [
                    'api_key' => $this->decryptKey($ancestor['api_key']),
                    'source'  => $ancestor['type'] === 'tiepai' ? 'tiepai' : 'agent',
                    'name'    => $ancestor['name'] ?: $ancestor['username'] ?: '上级',
                ];
            }
        }

        return null;
    }
}
