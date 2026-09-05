<?php

namespace app\admin\controller\agent;

use app\common\controller\Backend;
use think\Db;
use think\Session;

/**
 * 大模型配置（代理/贴牌自助配置 API 地址与密钥）
 * 显示条件：非超管时须上级授权（fa_agent.allow_model_config=1）
 */
class Modelconfig extends Backend
{

    protected $model = null;
    protected $layout = 'default';

    public function _initialize()
    {
        parent::_initialize();
        // 超管：始终可进（可查看/维护所有贴牌与代理的配置？不——超管用代理编辑表单即可，这里超管也放行仅作兜底）
        // 非超管：必须是 agent 且 allow_model_config=1
        if (!$this->isSuperAdmin()) {
            $agent = $this->getCurrentAgent();
            if (!$agent || !$agent['allow_model_config']) {
                $this->error('上级未授权使用大模型配置');
            }
        }
    }

    /**
     * 配置页
     */
    public function index()
    {
        if ($this->request->isPost()) {
            $baseUrl = trim($this->request->post('base_url', ''));
            $apiKey  = trim($this->request->post('api_key', ''));
            $models  = trim($this->request->post('models', ''));
            $useCustom = (int)$this->request->post('is_custom_key', 0);

            if ($useCustom) {
                if ($baseUrl !== '' && !preg_match('#^https?://#i', $baseUrl)) {
                    $this->error('API地址必须以 http:// 或 https:// 开头');
                }
                if ($apiKey === '') {
                    $this->error('自定义模式下 API Key 不能为空');
                }
            }

            // 模型列表：中英文逗号分隔 → 归一化（去空格/去重/过滤非法字符），上限 50 个
            $modelList = [];
            foreach (preg_split('/[,，]/u', $models) as $m) {
                $m = trim($m);
                if ($m === '' || strlen($m) > 100 || !preg_match('/^[\w.\-:\/]+$/u', $m)) {
                    continue;
                }
                $modelList[$m] = true;
            }
            $modelList = array_keys($modelList);
            if (count($modelList) > 50) {
                $this->error('模型数量最多 50 个');
            }

            $update = [
                'is_custom_key' => $useCustom ? 1 : 0,
                'updatetime'    => time(),
            ];
            if ($useCustom) {
                if ($baseUrl !== '') {
                    $update['base_url'] = rtrim($baseUrl, '/');
                }
                $update['api_key'] = base64_encode($apiKey);
                $update['models'] = implode(',', $modelList);
            } else {
                // 回退上级API时清掉自定义模型（跟随继承链）
                $update['models'] = '';
            }

            $agent = $this->getCurrentAgent();
            Db::name('agent')->where('id', $agent['id'])->update($update);

            $this->success('保存成功');
        }

        $agent = $this->getCurrentAgent();
        $row = [
            'is_custom_key' => $agent ? $agent['is_custom_key'] : 0,
            'base_url'      => $agent && isset($agent['base_url']) ? $agent['base_url'] : '',
            'api_key'       => $agent && $agent['api_key'] ? base64_decode($agent['api_key']) : '',
            'models'        => $agent && isset($agent['models']) ? $agent['models'] : '',
        ];
        $this->view->assign('row', $row);
        return $this->view->fetch();
    }
}

