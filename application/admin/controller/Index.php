<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\admin\library\Auth;
use think\Config;
use think\Hook;
use think\Session;
use think\Validate;

/**
 * 后台首页控制器（登录/登出/主界面）
 */
class Index extends Backend
{
    protected $noNeedLogin = ['login', 'logout', 'impersonate'];
    protected $noNeedRight = ['*'];
    protected $layout = '';

    /**
     * 越权登录票据领取（配合 agent/agent/loginas）
     * 新标签打开 admin.php/index/impersonate?ticket=xxx，凭票换取目标身份会话
     */
    public function impersonate()
    {
        $ticket = $this->request->get('ticket', '');
        if (!$ticket) {
            $this->error('缺少票据');
        }
        $info = Session::get('impersonate_ticket');
        Session::delete('impersonate_ticket');
        if (!$info || $info['ticket'] !== $ticket || $info['expire'] < time()) {
            $this->error('票据无效或已过期');
        }

        $admin = \think\Db::name('admin')->where('id', $info['admin_id'])->where('status', 'normal')->find();
        if (!$admin) {
            $this->error('目标账号不存在或已禁用');
        }

        // 记录切换前的身份，供"返回原身份"使用
        $previous = Session::get('admin');
        $previousId = $previous && !empty($previous['id']) ? (int)$previous['id'] : 0;
        if ($previousId && $previousId != $admin['id']) {
            Session::set('impersonate_back', $previousId);
        }

        // 建立目标身份会话（对齐 Auth::login 的关键字段）
        $admin['token'] = \fast\Random::uuid();
        \think\Db::name('admin')->where('id', $admin['id'])->update([
            'token' => $admin['token'],
            'logintime' => time(),
            'loginip' => $this->request->ip(),
        ]);
        Session::set('admin', $admin);
        Session::set('admin.safecode', md5(md5($admin['username']) . md5(substr($admin['password'], 0, 6)) . config('token.key')));

        $this->redirect(url('index/index'));
    }

    /**
     * 后台首页
     */
    public function index()
    {
        // 左侧菜单
        list($menulist, $navlist, $fixedmenu, $referermenu) = $this->auth->getSidebar([
            'dashboard' => 'hot',
            'addon'     => ['new', 'red', 'badge'],
            'auth/rule' => __('Menu'),
            'general/config' => __('Config'),
            'mail/message' => __('Mail'),
        ], 'dashboard');

        $this->view->assign('menulist', $menulist);
        $this->view->assign('navlist', $navlist);
        $this->view->assign('fixedmenu', $fixedmenu);
        $this->view->assign('referermenu', null);

        // 越权切换提示条（上级进入下级后台时显示"返回原身份"）
        $impersonating = null;
        $backId = \think\Session::get('impersonate_back');
        if ($backId && $backId != $this->auth->id) {
            $backAdmin = \think\Db::name('admin')->where('id', $backId)->field('id,username,nickname')->find();
            if ($backAdmin) {
                $impersonating = [
                    'current' => $this->auth->nickname ?: $this->auth->username,
                    'back'    => $backAdmin['nickname'] ?: $backAdmin['username'],
                ];
            }
        }
        $this->view->assign('impersonating', $impersonating);

        return $this->view->fetch();
    }

    /**
     * 返回原身份（越权切换的还原）
     */
    public function impersonateBack()
    {
        $backId = \think\Session::get('impersonate_back');
        if (!$backId) {
            $this->error('没有可返回的身份');
        }
        $admin = \think\Db::name('admin')->where('id', $backId)->where('status', 'normal')->find();
        if (!$admin) {
            $this->error('原账号不存在或已禁用');
        }
        Session::delete('impersonate_back');
        $admin['token'] = \fast\Random::uuid();
        \think\Db::name('admin')->where('id', $admin['id'])->update(['token' => $admin['token'], 'logintime' => time(), 'loginip' => $this->request->ip()]);
        Session::set('admin', $admin);
        Session::set('admin.safecode', md5(md5($admin['username']) . md5(substr($admin['password'], 0, 6)) . config('token.key')));
        $this->success('已返回原身份', 'index/index');
    }

    /**
     * 管理员登录
     */
    public function login()
    {
        $url = $this->request->get('url', 'index/index');
        $url = $url && $url != 'login' ? $url : 'index/index';

        if ($this->auth->isLogin()) {
            // 浏览器直接访问时做 HTTP 302 跳转，AJAX 请求返回 JSON
            if (!$this->request->isAjax()) {
                $this->redirect(url($url));
            }
            $this->success(__("You've logged in, do not login again"), $url);
        }

        if ($this->request->isPost()) {
            $username = $this->request->post('username');
            $password = $this->request->post('password');
            $keeplogin = $this->request->post('keeplogin');
            $token = $this->request->post('__token__');

            // 直接返回JSON，绕过Jump trait的HTTP 500问题
            $jsonResponse = function($code, $msg, $data = []) use ($url) {
                throw new \think\exception\HttpResponseException(json([
                    'code' => $code,
                    'msg'  => $msg,
                    'data' => $data,
                    'url'  => $code ? ('/admin.php/' . $url . '.html') : '',
                    'wait' => 3,
                ]));
            };

            // token 校验（登录页不强制验证 token，避免 validator 插件冲突）
            $validate = new Validate([
                'username'  => 'require|length:3,30',
                'password'  => 'require|length:3,30',
            ], [], ['username' => __('Username'), 'password' => __('Password')]);

            if (!$validate->check(['username' => $username, 'password' => $password])) {
                $jsonResponse(0, $validate->getError(), ['token' => $this->request->token()]);
            }

            Auth::instance()->logout();
            $result = $this->auth->login($username, $password, $keeplogin ? 86400 : 0);
            if ($result === true) {
                Hook::listen("admin_login_after", $this->request);
                $jsonResponse(1, __('Login successful'), ['url' => $url, 'id' => $this->auth->id, 'username' => $username]);
            } else {
                $msg = $this->auth->getError() ?: __('Username or password is incorrect');
                $jsonResponse(0, $msg, ['token' => $this->request->token()]);
            }
        }

        // 根据客户端的cookie自动登录
        Auth::instance()->autologin();

        Session::set('referer', $url);
        $this->view->assign('background', '');
        $this->view->assign('keeyloginhours', 24);
        return $this->view->fetch();
    }

    /**
     * 注销登录
     */
    public function logout()
    {
        $this->auth->logout();
        Session::delete("referer");
        $this->redirect('index/login');
    }
}
