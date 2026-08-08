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

    public function _initialize()
    {
        //移除HTML标签
        $this->request->filter('trim,strip_tags,htmlspecialchars');

        $modulename = $this->request->module();
        $controllername = strtolower($this->request->controller());
        $actionname = strtolower($this->request->action());

        // 定义是否Addtabs请求（FastAdmin前端通过 ref=addtabs 或 addtabs=1 标识iframe标签页）
        // 但刷新页面时URL也会带 ref=addtabs，需用 Sec-Fetch-Dest 头区分顶层导航和iframe
        $secFetchDest = $this->request->header('sec-fetch-dest');
        $isIframe = ($secFetchDest === 'iframe');
        $addtabsVal = ($this->request->param("ref") == 'addtabs' || $this->request->param("addtabs"));
        // Sec-Fetch-Dest 存在时用它判断；不存在时回退到参数判断
        if ($secFetchDest !== null) {
            !defined('IS_ADDTABS') && define('IS_ADDTABS', $isIframe && $addtabsVal);
        } else {
            !defined('IS_ADDTABS') && define('IS_ADDTABS', $addtabsVal ? true : false);
        }

        // 定义是否Dialog请求
        $dialogVal = $this->request->param("dialog");
        !defined('IS_DIALOG') && define('IS_DIALOG', $dialogVal ? true : false);

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
        $config = [
            'site' => $site,
        ];
        $this->view->assign('config', $config);

        // 设置布局模板（仅非AJAX请求，且控制器未禁用layout时）
        if (!IS_AJAX && $this->layout) {
            // 合并 assignConfig 到 config 变量
            if ($this->assignConfig) {
                // 将 assignConfig 合并到 site 下，以便前端 window.Config 能访问
                $site = array_merge($site, $this->assignConfig);
                $config = ['site' => $site];
                $this->view->assign('config', $config);
                $this->view->assign('site', $site);
            }
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
        $sort = $this->request->get('sort', 'id');
        $order = $this->request->get('order', 'DESC');
        $offset = $this->request->get('offset/d', 0);
        $limit = $this->request->get('limit/d', 0);

        $filter = (array)json_decode($filter, true);
        $op = (array)json_decode($op, true);

        $filter = $filter ? $filter : [];
        $op = $op ? $op : [];
        $sort = $sort ? str_replace(',', ' ', $sort) : 'id';

        $params = [];
        foreach ($filter as $k => $v) {
            if (!preg_match('/^\w+$/', $k)) {
                continue;
            }
            $sym = isset($op[$k]) ? $op[$k] : '=';
            if (in_array($sym, ['=', '<>', '>', '>=', '<', '<=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN'])) {
                $params[$k] = $v;
            }
        }

        $where = [];
        foreach ($params as $k => $v) {
            $op = isset($op[$k]) ? $op[$k] : '=';
            $sym = $op;
            if ($sym == 'LIKE' || $sym == 'NOT LIKE') {
                $where[$k] = [$sym, '%' . $v . '%'];
            } elseif ($sym == 'IN' || $sym == 'NOT IN') {
                $where[$k] = [$sym, is_array($v) ? $v : explode(',', $v)];
            } else {
                $where[$k] = [$sym, $v];
            }
        }

        // 快速搜索
        if ($search) {
            $searchArr = is_array($searchfields) ? $searchfields : explode(',', $searchfields);
            $searchWhere = [];
            foreach ($searchArr as $field) {
                $searchWhere[] = [$field, 'LIKE', '%' . $search . '%'];
            }
            // 合并到 where
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
