<?php

namespace app\common\controller;

use app\admin\library\Auth;
use think\Config;
use think\Controller;
use think\Hook;
use think\Lang;
use think\Loader;
use think\Request;
use think\Response;

/**
 * 后台控制器基类
 * 基于 FastAdmin 官方开源版 Backend.php 重写
 */
class Backend extends Controller
{
    /**
     * @var Auth
     */
    protected $auth = null;

    /**
     * @var Request
     */
    protected $request = null;

    /**
     * 默认响应输出类型
     * @var string
     */
    protected $responseType = 'json';

    /**
     * 无需登录的方法
     * @var array
     */
    protected $noNeedLogin = [];

    /**
     * 无需鉴权的方法
     * @var array
     */
    protected $noNeedRight = [];

    /**
     * 布局模板
     * @var string
     */
    protected $layout = 'default';

    /**
     * 前端JS附加配置（assignconfig使用）
     * @var array
     */
    protected $assignConfig = [];

    /**
     * 站点配置缓存（assignconfig 实时合并用）
     * @var array
     */
    protected $siteData = [];

    /**
     * 关联查询
     * @var bool
     */
    protected $relationSearch = false;

    /**
     * 模型对象
     * @var \think\Model
     */
    protected $model = null;

    /**
     * 快速搜索时执行查找的字段
     */
    protected $searchFields = 'id';

    /**
     * 是否是数据限制
     * @var string
     */
    protected $dataLimit = false;

    /**
     * 数据限制字段
     */
    protected $dataLimitField = 'admin_id';

    /**
     * 数据限制自动填充字段
     */
    protected $dataLimitFieldAutoFill = 'admin_id';

    /**
     * 是否开启Validate验证
     */
    protected $modelValidate = false;

    /**
     * 是否开启模型场景验证
     */
    protected $modelSceneValidate = false;

    /**
     * 多条件查询
     */
    protected $multiFields = 'status';

    /**
     * selectpage 字段
     */
    protected $selectpageFields = '*';

    /**
     * 导出字段
     */
    protected $exportFields = '*';

    /**
     * 前台发送过来需要额外排序的字段
     */
    protected $excludeFields = [];

    /**
     * 引入后台控制器的traits
     */
    use \app\admin\library\traits\Backend;

    /**
     * 获取当前登录管理员关联的Agent记录
     * @return array|null ['id'=>..., 'type'=>..., 'path'=>...]
     */
    protected function getCurrentAgent()
    {
        $adminId = $this->auth->id;
        if (!$adminId) {
            return null;
        }
        $agent = \think\Db::name('agent')->where('admin_id', $adminId)->find();
        return $agent ?: null;
    }

    /**
     * 是否是超级管理员
     * 判断标准：没有关联 agent 记录的后台管理员即为超管
     */
    protected function isSuperAdmin()
    {
        $adminId = $this->auth->id;
        if (!$adminId) {
            return false;
        }
        // admin_id=1 是初始超级管理员
        if ($adminId == 1) {
            return true;
        }
        // 检查是否关联了 agent 记录
        $agent = \think\Db::name('agent')->where('admin_id', $adminId)->find();
        return $agent ? false : true;
    }

    /**
     * 获取当前用户可见的代理ID范围
     * 超管: null（表示全部）
     * 贴牌/代理: 只看自己子树
     * @return array|null ID数组或null（全部）
     */
    protected function getAgentScope()
    {
        if ($this->isSuperAdmin()) {
            return null; // 全部
        }
        $agent = $this->getCurrentAgent();
        if (!$agent) {
            return [0]; // 无关联agent，看不到任何数据
        }
        return \app\admin\model\Agent::getDescendantIds($agent['id']);
    }

    /**
     * 获取当前用户可见的用户(agent_id)范围
     * 超管: null（全部）
     * 贴牌: 所有子孙代理的用户
     * 代理: 只有自己agent_id的用户
     * @return array|null agent_id数组或null（全部）
     */
    protected function getUserScope()
    {
        if ($this->isSuperAdmin()) {
            return null;
        }
        $agent = $this->getCurrentAgent();
        if (!$agent) {
            return [0];
        }
        return \app\admin\model\Agent::getDescendantIds($agent['id']);
    }

    public function _initialize()
    {
        //移除HTML标签
        $this->request->filter('trim,strip_tags,htmlspecialchars');

        $modulename = $this->request->module();
        $controllername = strtolower($this->request->controller());
        $actionname = strtolower($this->request->action());

        // 定义是否Addtabs请求
        // 必须同时满足：URL带addtabs参数 + 实际来自iframe请求（Sec-Fetch-Dest）
        // 这样浏览器F5刷新时（即使URL带addtabs=1）也会被重定向到主框架
        $addtabsParam = ($this->request->param("ref") == 'addtabs' || $this->request->param("addtabs"));
        $isIframe = $this->request->server('HTTP_SEC_FETCH_DEST') === 'iframe';
        !defined('IS_ADDTABS') && define('IS_ADDTABS', ($addtabsParam && $isIframe) ? true : false);

        // 定义是否Dialog请求（同样需要实际来自iframe）
        $dialogParam = $this->request->param("dialog");
        !defined('IS_DIALOG') && define('IS_DIALOG', ($dialogParam && $isIframe) ? true : false);

        // 定义是否AJAX请求
        !defined('IS_AJAX') && define('IS_AJAX', $this->request->isAjax());

        // 权限控制类
        $this->auth = Auth::instance();

        // 设置当前请求的URL
        $this->auth->setRequestUri($this->request->pathinfo());

        // 检测是否登录
        if (!$this->auth->isLogin() && !$this->match($this->noNeedLogin)) {
            // 多入口已用 BIND_MODULE 固定模块，URL 不再拼接模块名前缀，否则会被解析为 admin.index 控制器
            $url = 'index/login';
            if ($this->request->isAjax()) {
                $this->error(__('Please login first'), $url);
            }
            $this->redirect(url($url));
        }

        // 检测是否需要鉴权
        if ($this->auth->isLogin() && !$this->match($this->noNeedRight)) {
            // 判断控制器和方法判断是否有对应权限
            if (!$this->auth->check($this->request->pathinfo())) {
                $this->error(__('You have no permission'));
            }
        }

        // 非选项卡、非弹窗、直接访问后台页面时，重定向到 index/index 主框架（避免裸页面）
        // IS_ADDTABS=true 表示已被 iframe 加载，正常渲染；直接访问才需要包进主框架
        // 排除免登录接口（如 ajax/lang 由 RequireJS 通过 script 标签加载）
        if (!$this->request->isPost() && !$this->request->isAjax() && !IS_ADDTABS && !IS_DIALOG
            && !$this->match($this->noNeedLogin)
            && strtolower($controllername) != 'index' && strtolower($actionname) != 'login') {
            // 带上当前页面URL用于刷新后恢复
            $refUrl = $controllername . ($actionname != 'index' ? '/' . $actionname : '');
            $this->redirect(url('index/index') . '?ref=' . urlencode($refUrl));
        }

        // 语言检测并加载
        $lang = $this->request->langset();

        // 加载通用语言包（顶部栏、侧边栏、公共按钮等通用翻译）
        $this->loadlang('general');
        // 加载当前控制器对应的语言包
        $this->loadlang($controllername);

        // 将权限对象、管理员信息及路由信息赋值给模板（FastAdmin 模板依赖 $auth->check() 等）
        $this->view->assign([
            'auth'            => $this->auth,
            'admin'           => \think\Session::get('admin'),
            'modulename'      => $modulename,
            'controllername'  => $controllername,
            'actionname'      => $actionname,
            'requesturl'      => $modulename . '/' . $controllername . '/' . $actionname,
        ]);

        // 将前端 require-backend.js 依赖的站点配置赋值给模板
        $siteConfig = \think\Config::get('site') ?: [];
        $site = array_merge([
            'name'            => \think\Config::get('site.name') ?: 'FastAdmin',
            'version'         => \think\Config::get('site.version') ?: '1.0.0',
            'cdnurl'          => \think\Config::get('site.cdnurl') ?: '',
            'timezone'        => \think\Config::get('site.timezone') ?: 'Asia/Shanghai',
            'language'        => $lang ?: 'zh-cn',
            'moduleurl'       => 'admin.php',
            'controllername'  => $controllername,
            'actionname'      => $actionname,
            'jsname'          => 'backend/' . str_replace('.', '/', $controllername),
            'termurl'         => '',
            'apiurl'          => '',
            'referer'         => $this->request->get('ref', ''),
        ], $siteConfig);
        $this->view->assign('site', $site);

        // RequireJS 前端配置（meta.html 中 {$config|json_encode} 引用）
        // upload 配置必须放在 site 内部，因为 require-backend.min.js 中 window.Config = config.site
        $uploadConfig = [
            'cdnurl'    => '',
            'uploadurl' => 'ajax/upload',
            'bucket'    => 'local',
            'maxsize'   => '10mb',
            'mimetype'  => 'jpg,png,bmp,jpeg,gif',
            'chunking'  => false,
            'multipart' => [],
            'multiple'  => false,
            'storage'   => 'local',
        ];
        $site['upload'] = $uploadConfig;
        $this->siteData = $site;
        $config = [
            'site' => $site,
        ];
        $this->view->assign('config', $config);
        $this->view->assign('site', $site);

        // 设置布局模板（仅非AJAX请求，且控制器未禁用layout时）
        if (!IS_AJAX && $this->layout) {
            $this->view->engine->layout('layout/' . $this->layout);
        }
    }

    /**
     * 向前端JS配置中追加数据（合并到 config 变量）
     * @param string $name 键名
     * @param mixed $value 值
     */
    public function assignconfig($name, $value = '')
    {
        $this->assignConfig[$name] = $value;
        // 实时合并到 view 的 config 变量，确保子控制器 _initialize 中的调用生效
        if ($this->siteData) {
            $site = array_merge($this->siteData, $this->assignConfig);
            $this->view->assign('config', ['site' => $site]);
            $this->view->assign('site', $site);
        }
    }

    /**
     * 加载语言文件
     * @param string $name
     */
    protected function loadlang($name)
    {
        $name = strtolower($name);
        $name = str_replace(['.', '/'], '_', $name);
        // $this->request->langset() 在 TP5.0 下可能为空，回退到配置中的 default_lang
        $lang = $this->request->langset() ?: \think\Config::get('default_lang') ?: 'zh-cn';
        $langFile = APP_PATH . $this->request->module() . '/lang/' . $lang . '/' . str_replace('.', '/', $name) . '.php';
        Lang::load($langFile);
    }

    /**
     * 检测当前控制器和方法是否匹配传递的数组
     * @param array $arr 需要验证权限的数组
     * @return bool
     */
    protected function match($arr = [])
    {
        $request = Request::instance();
        $arr = is_array($arr) ? $arr : explode(',', $arr);
        if (!$arr) {
            return false;
        }
        $arr = array_map('strtolower', $arr);
        // 是否存在
        if (in_array(strtolower($request->action()), $arr) || in_array('*', $arr)) {
            return true;
        }
        // 没找到匹配
        return false;
    }

    /**
     * 操作成功返回的数据（FastAdmin 标准签名：msg, url, data）
     * @param string $msg  提示信息
     * @param string $url  跳转的URL
     * @param mixed  $data 返回的数据
     * @param int    $wait 跳转等待时间
     * @param array  $header 发送的Header信息
     */
    protected function success($msg = '', $url = null, $data = '', $wait = 3, array $header = [])
    {
        if (is_numeric($msg)) {
            $code = $msg;
            $msg = '';
        } else {
            $code = 1;
        }
        return $this->result($code, $msg, $url, $data, $wait, $header, 'success');
    }

    /**
     * 操作失败返回的数据（FastAdmin 标准签名：msg, url, data）
     * @param string $msg  提示信息
     * @param string $url  跳转的URL
     * @param mixed  $data 返回的数据
     * @param int    $wait 跳转等待时间
     * @param array  $header 发送的Header信息
     */
    protected function error($msg = '', $url = null, $data = '', $wait = 3, array $header = [])
    {
        if (is_numeric($msg)) {
            $code = $msg;
            $msg = '';
        } else {
            $code = 0;
        }
        return $this->result($code, $msg, $url, $data, $wait, $header, 'error');
    }

    /**
     * 返回封装后的 API 数据到客户端（FastAdmin 标准：输出 code/msg/data/url/wait）
     * @access protected
     * @param int    $code 状态码（1成功 0失败）
     * @param string $msg  提示信息
     * @param string $url  跳转URL
     * @param mixed  $data 返回数据
     * @param int    $wait 等待时间
     * @param array  $header 头信息
     * @param string $type  success/error（用于扩展）
     * @return void
     * @throws HttpResponseException
     */
    protected function result($code = 0, $msg = '', $url = null, $data = '', $wait = 3, array $header = [], $type = '')
    {
        // 处理跳转URL
        if (is_null($url) && isset($_SERVER["HTTP_REFERER"])) {
            $url = $_SERVER["HTTP_REFERER"];
        } elseif ($url) {
            $url = (strpos($url, '://') || 0 === strpos($url, '/')) ? $url : url($url);
        }

        $result = [
            'code' => (int)$code,
            'msg'  => $msg,
            'time' => $this->request->server('REQUEST_TIME'),
            'data' => $data,
            'url'  => (string)$url,
            'wait' => $wait,
        ];

        $responseType = $this->request->param(config('var_jsonp_handler')) ? 'jsonp' : ($this->responseType ?: 'json');

        // HTTP状态码：成功200，失败500
        if (isset($header['statuscode'])) {
            $httpCode = $header['statuscode'];
            unset($header['statuscode']);
        } else {
            $httpCode = $code >= 1 ? 200 : 500;
        }

        // 跨域头
        $header['Access-Control-Allow-Origin'] = '*';
        $header['Access-Control-Allow-Headers'] = 'X-Requested-With,X_Requested_With,content-type';

        $response = Response::create($result, $responseType, $httpCode)->header($header);
        throw new \think\exception\HttpResponseException($response);
    }

    /**
     * 生成查询所需要的条件
     * @param mixed $searchfields 快速查询的字段
     * @param boolean $relationSearch 是否关联查询
     * @return array
     */
    protected function buildparams($searchfields = null, $relationSearch = null)
    {
        $searchfields = is_null($searchfields) ? $this->searchFields : $searchfields;
        $relationSearch = is_null($relationSearch) ? $this->relationSearch : $relationSearch;

        $search = trim($this->request->get('search', ''));
        $filter = $this->request->get('filter', '');
        $op = $this->request->get('op', '');
        // 修复 HTML 实体编码导致 json_decode 失败
        $filter = is_string($filter) ? htmlspecialchars_decode($filter) : $filter;
        $op = is_string($op) ? htmlspecialchars_decode($op) : $op;
        $sort = $this->request->get('sort', 'id');
        $order = $this->request->get('order', 'DESC');
        $offset = $this->request->get('offset/d', 0);
        $limit = $this->request->get('limit/d', 0);

        $filter = (array)json_decode($filter, true);
        $op = (array)json_decode($op, true);

        $filter = $filter ? $filter : [];
        $op = $op ? $op : [];
        $sort = $sort ? str_replace(',', ' ', $sort) : 'id';

        $where = [];
        foreach ($filter as $k => $v) {
            if (!preg_match('/^\w+$/', $k)) {
                continue;
            }
            $sym = isset($op[$k]) ? strtoupper($op[$k]) : '=';
            switch ($sym) {
                case 'LIKE':
                case 'NOT LIKE':
                    $where[$k] = [$sym, '%' . $v . '%'];
                    break;
                case 'IN':
                case 'NOT IN':
                    $where[$k] = [$sym, is_array($v) ? $v : explode(',', $v)];
                    break;
                case 'RANGE':
                case 'BETWEEN':
                    $valArr = is_array($v) ? $v : explode(',', $v);
                    if (count($valArr) == 2) {
                        $where[$k] = ['between', $valArr];
                    }
                    break;
                case '=':
                case '<>':
                case '>':
                case '>=':
                case '<':
                case '<=':
                default:
                    $where[$k] = [$sym, $v];
                    break;
            }
        }

        // 控制器统一以 order($sort, $order) 方式调用，故 $order 返回排序方向字符串
        $order = $sort && $order ? (strtolower($order) == 'asc' ? 'ASC' : 'DESC') : 'DESC';

        return [$where, $sort, $order, $offset, $limit];
    }

    /**
     * Selectpage的实现方法
     */
    public function selectpage()
    {
        if ($this->request->isAjax()) {
            $list = [];
            $word = $this->request->request('searchWord', '');
            $page = $this->request->request('pageNumber', 1);
            $pageSize = $this->request->request('pageSize', 30);
            $keyValue = $this->request->request('keyValue', '');

            if ($this->model) {
                $query = $this->model;
                if ($keyValue) {
                    $query->where('id', $keyValue);
                } elseif ($word) {
                    $query->where($this->selectpageFields, 'like', '%' . $word . '%');
                }
                $total = $query->count();
                $list = $query->page($page, $pageSize)->select();
            }

            return json(['list' => $list, 'total' => $total]);
        }
        return '';
    }

    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->model
                ->where($where)
                ->count();

            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $result = ['total' => $total, 'rows' => $list];
            return json($result);
        }

        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            if (!$params) {
                $this->error(__('Parameter %s can not be empty', ''));
            }

            $result = $this->model->allowField(true)->save($params);
            if ($result !== false) {
                $this->success();
            } else {
                $this->error($this->model->getError());
            }
        }

        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }

        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            $result = $row->save($params);
            if ($result !== false) {
                $this->success();
            } else {
                $this->error($row->getError());
            }
        }

        $this->view->assign('row', $row);
        return $this->view->fetch();
    }

    /**
     * 删除
     */
    public function del($ids = '')
    {
        if ($ids) {
            $count = $this->model->where('id', 'in', $ids)->delete();
            if ($count) {
                $this->success();
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }

    /**
     * 批量更新
     */
    public function multi($ids = '')
    {
        $ids = $ids ? $ids : $this->request->param('ids');
        if ($ids) {
            $params = $this->request->param();
            $count = $this->model->where('id', 'in', $ids)->update($params);
            if ($count) {
                $this->success();
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }
}
