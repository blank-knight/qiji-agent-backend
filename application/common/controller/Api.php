<?php

namespace app\common\controller;

use app\common\library\Auth;
use think\Config;
use think\Hook;
use think\Loader;
use think\Request;
use think\Response;

/**
 * API 控制器基类
 * 基于 FastAdmin 官方开源版 Api.php 重写
 */
class Api extends \think\Controller
{
    /**
     * @var Auth 前台用户认证实例
     */
    protected $auth = null;

    /**
     * @var Request
     */
    protected $request = null;

    /**
     * 默认响应输出类型,支持json/xml/jsonp
     * @var string
     */
    protected $responseType = 'json';

    /**
     * @var bool 是否检测IP黑名单
     */
    protected $checkIpBlacklist = false;

    /**
     * 无需登录的方法
     * @var array
     */
    protected $noNeedLogin = ['*'];

    /**
     * 无需鉴权的方法
     * @var array
     */
    protected $noNeedRight = ['*'];

    /**
     * 权限控制类
     */
    protected $token = '';

    /**
     * 成功状态码
     */
    protected $successCode = 1;

    /**
     * 失败状态码
     */
    protected $errorCode = 0;

    public function _initialize()
    {
        //跨域请求检测
        check_cors_request();

        // 解析 JSON body（客户端用 Content-Type: application/json 发送数据）
        $contentType = $this->request->server('HTTP_CONTENT_TYPE', '');
        if (stripos($contentType, 'application/json') !== false) {
            $rawBody = file_get_contents('php://input');
            if ($rawBody) {
                $jsonData = json_decode($rawBody, true);
                if (is_array($jsonData)) {
                    $_POST = array_merge($_POST, $jsonData);
                    $this->request->post($_POST);
                }
            }
        }

        //初始化 Auth
        $this->auth = Auth::instance();

        //设置 token
        $this->token = $this->request->server('HTTP_TOKEN', $this->request->request('token', \think\Cookie::get('token')));

        // 设置请求数据
        $modulename = $this->request->module();
        $controllername = strtolower($this->request->controller());
        $actionname = strtolower($this->request->action());

        // 检测IP黑名单
        if ($this->checkIpBlacklist) {
            $ipBlacklist = config('site.ipblacklist') ?: '';
            if ($ipBlacklist) {
                $ipList = array_filter(explode("\n", str_replace("\r\n", "\n", trim($ipBlacklist))));
                if (in_array($this->request->ip(), $ipList)) {
                    $this->error(__('Invalid IP'));
                }
            }
        }

        // 尝试初始化用户
        if ($this->token) {
            try {
                $this->auth->init($this->token);
            } catch (\Exception $e) {
                // token 无效时忽略
            }
        }

        // 导入语言包
        $lang = $this->request->langset();
        $langDir = APP_PATH . $modulename . '/lang/' . $lang . '/';
        if (is_dir($langDir)) {
            Lang::directory($langDir);
        }

        // 加载通用语言包
        $this->loadlang($controllername);
    }

    /**
     * 加载语言文件
     * @param string $name
     */
    protected function loadlang($name)
    {
        $name = strtolower($name);
        $name = str_replace(['.', '/'], '_', $name);
        \think\Lang::load(APP_PATH . $this->request->module() . '/lang/' . $this->request->langset() . '/' . str_replace('.', '/', $name) . '.php');
    }

    /**
     * 操作成功返回的数据
     * @param string $msg 提示信息
     * @param mixed $data 要返回的数据
     * @param int $code 错误码，默认为1
     * @param string $type 输出类型
     * @param array $header 发送的 Header 信息
     */
    protected function success($msg = '', $data = null, $code = 1, $type = null, array $header = [])
    {
        $this->result($msg, $data, $code, $type, $header);
    }

    /**
     * 操作失败返回的数据
     * @param string $msg 提示信息
     * @param mixed $data 要返回的数据
     * @param int $code 错误码，默认为0
     * @param string $type 输出类型
     * @param array $header 发送的 Header 信息
     */
    protected function error($msg = '', $data = null, $code = 0, $type = null, array $header = [])
    {
        $this->result($msg, $data, $code, $type, $header);
    }

    /**
     * 返回封装后的 API 数据到客户端
     * @access protected
     * @param mixed $msg 提示信息
     * @param mixed $data 要返回的数据
     * @param int $code 错误码，默认为0
     * @param string $type 输出类型
     * @param array $header 发送的 Header 信息
     * @return void
     * @throws HttpResponseException
     */
    protected function result($msg, $data = null, $code = 0, $type = null, array $header = [])
    {
        $result = [
            'code' => $code,
            'msg'  => $msg,
            'time' => $this->request->server('REQUEST_TIME'),
            'data' => $data,
        ];

        // 如果未设置类型则自动判断
        $type = $type ? $type : ($this->request->param(config('var_jsonp_handler')) ? 'jsonp' : $this->responseType);

        if (isset($header['statuscode'])) {
            $code = $header['statuscode'];
            unset($header['statuscode']);
        } elseif ($code === 401) {
            //未登录：HTTP 状态码也设为 401，客户端据此触发登录跳转
        } else {
            //其他情况：HTTP 200，业务状态码放在 JSON body 的 code 字段中
            $code = 200;
        }

        $response = Response::create($result, $type, $code)->header($header);
        throw new \think\exception\HttpResponseException($response);
    }
}
