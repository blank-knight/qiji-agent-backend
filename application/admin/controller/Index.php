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
    protected $noNeedLogin = ['login', 'logout'];
    protected $noNeedRight = ['*'];
    protected $layout = '';

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
        $this->view->assign('referermenu', $referermenu);

        return $this->view->fetch();
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

            $rule = [
                'username'  => 'require|length:3,30',
                'password'  => 'require|length:3,30',
                '__token__' => 'require|token',
            ];

            $data = [
                'username'  => $username,
                'password'  => $password,
                '__token__' => $token,
            ];

            if (Config::get('fastadmin.login_captcha')) {
                $rule['captcha'] = 'require|captcha';
                $data['captcha'] = $this->request->post('captcha');
            }

            $validate = new Validate($rule, [], ['username' => __('Username'), 'password' => __('Password'), 'captcha' => __('Captcha')]);
            $result = $validate->check($data);
            if (!$result) {
                $this->error($validate->getError(), $url, ['token' => $this->request->token()]);
            }

            Auth::instance()->logout();
            $result = $this->auth->login($username, $password, $keeplogin ? 86400 : 0);
            if ($result === true) {
                Hook::listen("admin_login_after", $this->request);
                $this->success(__('Login successful'), $url, ['url' => $url, 'id' => $this->auth->id, 'username' => $username]);
            } else {
                $msg = $this->auth->getError();
                $msg = $msg ? $msg : __('Username or password is incorrect');
                $this->error($msg, $url, ['token' => $this->request->token()]);
            }
        }

        // 根据客户端的cookie自动登录
        Auth::instance()->autologin();

        Session::set('referer', $url);
        return $this->view->fetch();
    }

    /**
     * 注销登录
     */
    public function logout()
    {
        $this->auth->logout();
        Session::delete("referer");
        $this->success(__('Logout successful'), 'index/login');
    }
}
