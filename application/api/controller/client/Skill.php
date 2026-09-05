<?php

namespace app\api\controller\client;

use think\Controller;
use think\Db;
use think\Request;

/**
 * 技能市场客户端 API
 * GET  /api/client/v1/skill/list     — 市场技能列表（登录态）
 * GET  /api/client/v1/skill/download?id=N — 下载 zip（登录态，计数后 302 直链）
 */
class Skill extends Controller
{

    protected $beforeActionList = [];

    public function _initialize()
    {
        parent::_initialize();
        // CORS 由 nginx 层处理；这里是客户端直调
    }

    /** 简易 Bearer 鉴权：返回 user 或直接输出 401 JSON */
    private function authUser()
    {
        $token = '';
        $header = $this->request->header('authorization', '');
        if (preg_match('/Bearer\s+(\S+)/i', $header, $m)) {
            $token = $m[1];
        }
        if (!$token) {
            $token = $this->request->request('token', '');
        }
        if (!$token) {
            return null;
        }
        // Token 驱动为 File/可配置：与 Auth 库同源（app\common\library\Token::get）
        $data = \app\common\library\Token::get($token);
        if (!$data || empty($data['user_id'])) {
            return null;
        }
        return Db::name('user')->where('id', $data['user_id'])->where('status', 'normal')->find();
    }

    private function fail($msg, $code = 0, $status = 401)
    {
        return json(['code' => $status, 'msg' => $msg, 'time' => time(), 'data' => null], $status);
    }

    /**
     * 市场列表
     */
    public function list()
    {
        $user = $this->authUser();
        if (!$user) {
            return $this->fail('请先登录');
        }
        $skills = Db::name('skill_market')
            ->field('id,name,title,description,category,version,filesize,download_count,updatetime')
            ->where('status', 'normal')
            ->order('weigh desc, id desc')
            ->select();
        foreach ($skills as &$s) {
            $s['updatetime_text'] = $s['updatetime'] ? date('Y-m-d', $s['updatetime']) : '';
        }
        return json(['code' => 1, 'msg' => '', 'time' => time(), 'data' => ['total' => count($skills), 'rows' => $skills]]);
    }

    /**
     * 下载：校验 → 计数 → 302 到静态直链
     * （直链放 public/downloads/skills/ 下，nginx 直接吐文件，服务端零带宽消耗）
     */
    public function download()
    {
        $user = $this->authUser();
        if (!$user) {
            return $this->fail('请先登录');
        }
        $id = (int)$this->request->get('id', 0);
        $skill = Db::name('skill_market')->where('id', $id)->where('status', 'normal')->find();
        if (!$skill || !$skill['filename']) {
            return $this->fail('技能不存在或已下架', 0, 404);
        }
        $file = ROOT_PATH . 'public/downloads/skills/' . $skill['filename'];
        if (!is_file($file)) {
            return $this->fail('技能文件缺失', 0, 404);
        }
        Db::name('skill_market')->where('id', $id)->setInc('download_count');
        $url = $this->request->domain() . '/downloads/skills/' . rawurlencode($skill['filename']);
        return redirect($url);
    }
}
