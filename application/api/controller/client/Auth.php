<?php

namespace app\api\controller\client;

use app\common\controller\Api;
use app\common\library\Auth as AuthLib;
use think\Db;
use think\Validate;

/**
 * 客户端认证接口
 */
class Auth extends Api
{
    protected $noNeedLogin = ['login', 'register', 'activate'];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 用户注册
     * @ApiMethod (POST)
     */
    public function register()
    {
        $mobile      = $this->request->post('mobile', '');
        $password    = $this->request->post('password', '');
        $invite_code = $this->request->post('invite_code', '');

        if (!$mobile || !$password) {
            $this->error('手机号和密码不能为空');
        }
        if (!Validate::regex($mobile, "^1\d{10}$")) {
            $this->error('手机号格式不正确');
        }
        if (strlen($password) < 6) {
            $this->error('密码长度不能少于6位');
        }

        // 检查手机号是否已注册
        $existUser = Db::name('user')->where('mobile', $mobile)->find();
        if ($existUser) {
            $this->error('该手机号已注册');
        }

        // 邀请码可选：填了就校验并绑定代理，不填就是体验用户
        $agentId = 0;
        if ($invite_code) {
            $invite = Db::name('agent_invite')
                ->where('invite_code', $invite_code)
                ->where('status', 'normal')
                ->find();

            if (!$invite) {
                $this->error('邀请码无效');
            }
            if ($invite['max_count'] > 0 && $invite['used_count'] >= $invite['max_count']) {
                $this->error('邀请码已被使用完毕');
            }
            if ($invite['expiretime'] > 0 && $invite['expiretime'] < time()) {
                $this->error('邀请码已过期');
            }

            $agent = Db::name('agent')->where('id', $invite['agent_id'])->find();
            if (!$agent || $agent['status'] != 'normal') {
                $this->error('邀请码对应的代理不可用');
            }

            $agentId = $invite['agent_id'];
        }

        // 注册用户
        $auth     = AuthLib::instance();
        $username = $mobile;
        $ret      = $auth->register($username, $password, '', $mobile, [
            'agent_id'      => $agentId,
            'is_custom_key' => 0,
        ]);

        if ($ret) {
            if ($invite_code && isset($invite)) {
                Db::name('agent_invite')
                    ->where('id', $invite['id'])
                    ->setInc('used_count');
            }

            $userInfo = $this->buildUserInfo($auth->getUser());
            $this->success('注册成功', [
                'token'     => $auth->getToken(),
                'user_info' => $userInfo,
            ]);
        } else {
            $this->error($auth->getError() ?: '注册失败');
        }
    }

    /**
     * 用户登录
     * @ApiMethod (POST)
     */
    public function login()
    {
        $mobile   = $this->request->post('mobile', '');
        $password = $this->request->post('password', '');

        if (!$mobile || !$password) {
            $this->error('手机号和密码不能为空');
        }

        $auth = AuthLib::instance();
        $ret  = $auth->login($mobile, $password);

        if ($ret) {
            $userInfo = $this->buildUserInfo($auth->getUser());
            $this->success('登录成功', [
                'token'     => $auth->getToken(),
                'user_info' => $userInfo,
            ]);
        } else {
            $this->error($auth->getError() ?: '登录失败');
        }
    }

    /**
     * 激活账号（体验模式 → 正式模式）
     * @ApiMethod (POST)
     */
    public function activate()
    {
        $invite_code = $this->request->post('invite_code', '');
        $userId      = $this->auth->id;

        if (!$userId) {
            $this->error('请先登录');
        }
        if (!$invite_code) {
            $this->error('请输入邀请码');
        }

        $invite = Db::name('agent_invite')
            ->where('invite_code', $invite_code)
            ->where('status', 'normal')
            ->find();

        if (!$invite) {
            $this->error('邀请码无效');
        }

        $user = Db::name('user')->where('id', $userId)->find();
        if (!$user) {
            $this->error('用户不存在');
        }

        if ($user['agent_id'] && $user['agent_id'] > 0) {
            $this->error('您已绑定代理，无法更改');
        }

        Db::name('user')->where('id', $userId)->update([
            'agent_id' => $invite['agent_id'],
        ]);

        Db::name('agent_invite')
            ->where('id', $invite['id'])
            ->setInc('used_count');

        $this->success('激活成功，请联系代理充值点数');
    }

    /**
     * 构建用户信息返回体
     */
    private function buildUserInfo($user)
    {
        $mode = $user->score > 0 ? 'formal' : 'trial';

        $agentName = '';
        if ($user->agent_id) {
            $agent = Db::name('agent')->where('id', $user->agent_id)->find();
            if ($agent) {
                $agentName = $agent['name'] ?: $agent['username'] ?: '';
            }
        }

        return [
            'id'            => $user->id,
            'username'      => $user->username,
            'mobile'        => $user->mobile,
            'score'         => (int)$user->score,
            'mode'          => $mode,
            'is_custom_key' => (int)$user->is_custom_key,
            'agent_name'    => $agentName,
        ];
    }
}
