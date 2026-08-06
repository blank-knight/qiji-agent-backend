<?php

// +----------------------------------------------------------------------
// | FastAdmin 全局函数库（开源重写版）
// +----------------------------------------------------------------------

use think\Config;
use think\Cookie;
use think\Request;
use think\Response;

// 将 session 保存路径指向项目 runtime（解决 /var/lib/php/sessions 无写权限问题）
// common.php 在 app_init 行为阶段加载，早于 Session 启动，此时设置 save_path 生效
if (!defined('RUNTIME_PATH')) {
    define('RUNTIME_PATH', dirname(__DIR__) . '/runtime/');
}
$sessionPath = RUNTIME_PATH . 'session';
if (!is_dir($sessionPath)) {
    @mkdir($sessionPath, 0777, true);
}
if (is_dir($sessionPath) && is_writable($sessionPath)) {
    session_save_path($sessionPath);
}

/**
 * 跨域检测
 */
function check_cors_request()
{
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        $info = parse_url($_SERVER['HTTP_ORIGIN']);
        $domainArr = explode(',', config('fastadmin.cors_request_domain') ?: '');
        $domainArr[] = request()->host(true);
        if (in_array("*", $domainArr) || (isset($info['host']) && in_array($info['host'], $domainArr))) {
            header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
            header("Access-Control-Allow-Credentials: true");
            header("Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS");
            header("Access-Control-Allow-Headers: DNT,X-Mx-ReqToken,Keep-Alive,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Accept-Language,Origin,Authorization,Token");
            header('Access-Control-Max-Age: 1728000');
            if (request()->isOptions()) {
                exit;
            }
        }
    }
}

/**
 * 语言翻译（简化版）
 * @param string $name
 * @param array $vars
 * @return string
 */
if (!function_exists('__')) {
    function __($name, $vars = [], $lang = '')
    {
        if (is_numeric($name) || !$name || !is_string($name)) {
            return $name;
        }
        // 尝试从语言包翻译
        $result = \think\Lang::get($name, $vars);
        // 如果翻译后和原文相同，直接返回原文
        return $result === $name ? $name : $result;
    }
}

/**
 * 获取输入数据
 */
if (!function_exists('input')) {
    function input($key = '', $default = '', $filter = '')
    {
        return Request::instance()->input($key, $default, $filter);
    }
}

/**
 * 返回字符长度
 */
if (!function_exists('mbstrlen')) {
    function mbstrlen($string)
    {
        return mb_strlen($string, 'utf-8');
    }
}

/**
 * 生成 UUID
 */
if (!function_exists('uuid')) {
    function uuid()
    {
        return \fast\Random::uuid();
    }
}

/**
 * 获取时间戳
 */
if (!function_exists('time')) {
    function time()
    {
        return $_SERVER['REQUEST_TIME'];
    }
}

/**
 * 将字符串转换为数组
 */
if (!function_exists('str2arr')) {
    function str2arr($str, $glue = ',')
    {
        $arr = explode($glue, $str);
        $arr = array_filter($arr);
        return $arr;
    }
}

/**
 * 将数组转换为字符串
 */
if (!function_exists('arr2str')) {
    function arr2str($arr, $glue = ',')
    {
        return implode($glue, $arr);
    }
}

/**
 * 获取站点的配置
 */
if (!function_exists('get_site_config')) {
    function get_site_config($name)
    {
        return config('site.' . $name);
    }
}

/**
 * 格式化字节大小
 * @param  string $size      字节大小
 * @param  string $delimiter 自定义分隔符
 * @return string            格式化带单位的输出
 */
if (!function_exists('format_bytes')) {
    function format_bytes($size, $delimiter = '')
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        for ($i = 0; $size >= 1024 && $i < 5; $i++) {
            $size /= 1024;
        }
        return round($size, 2) . $delimiter . $units[$i];
    }
}

/**
 * 格式化时间
 */
if (!function_exists('datetime')) {
    function datetime($time, $format = 'Y-m-d H:i:s')
    {
        $time = is_numeric($time) ? $time : strtotime($time);
        return date($format, $time);
    }
}

/**
 * 加密密码（FastAdmin 标准方式：双MD5 + salt）
 */
if (!function_exists('encrypt_password')) {
    function encrypt_password($password, $salt = '')
    {
        return md5(md5($password) . $salt);
    }
}

/**
 * 生成随机字符串
 */
if (!function_exists('random_string')) {
    function random_string($length = 8, $type = 'alnum')
    {
        return \fast\Random::build($type, $length);
    }
}

/**
 * 获取客户端真实IP
 */
if (!function_exists('get_client_ip')) {
    function get_client_ip()
    {
        return Request::instance()->ip();
    }
}

/**
 * 判断当前是否为微信浏览器
 */
if (!function_exists('is_wechat')) {
    function is_wechat()
    {
        return strpos(Request::instance()->server('HTTP_USER_AGENT', ''), 'MicroMessenger') !== false;
    }
}

/**
 * 获取栏目的子分类
 */
if (!function_exists('get_category_children')) {
    function get_category_children($pid)
    {
        $list = \think\Db::name('category')->where('pid', $pid)->where('status', 'normal')->order('weigh', 'desc,id', 'asc')->select();
        return $list;
    }
}

/**
 * 检测数组某字段是否存在且不为空
 */
if (!function_exists('array_value')) {
    function array_value($array, $key, $default = '')
    {
        return isset($array[$key]) && $array[$key] !== '' ? $array[$key] : $default;
    }
}

/**
 * JSON 返回（兼容旧代码）
 */
if (!function_exists('json_result')) {
    function json_result($code = 0, $msg = '', $data = null, $url = '', $wait = 3)
    {
        $result = [
            'code' => $code,
            'msg'  => $msg,
            'data' => $data,
            'url'  => $url,
            'wait' => $wait,
        ];
        return Response::create($result, 'json');
    }
}

/**
 * 获取配置值
 */
if (!function_exists('get_config_value')) {
    function get_config_value($name, $default = '')
    {
        $value = config('site.' . $name);
        if ($value === null || $value === '') {
            return $default;
        }
        return $value;
    }
}

/**
 * Token生成
 */
if (!function_exists('generate_token')) {
    function generate_token()
    {
        return md5(uniqid() . time() . mt_rand(0, 999999));
    }
}

/**
 * HTML安全过滤
 */
if (!function_exists('purify_html')) {
    function purify_html($html)
    {
        if (class_exists('\\ezyang\\htmlpurifier\\HTMLPurifier')) {
            $config = \ezyang\htmlpurifier\HTMLPurifier_Config::createDefault();
            $purifier = new \ezyang\htmlpurifier\HTMLPurifier($config);
            return $purifier->purify($html);
        }
        return htmlspecialchars($html);
    }
}

/**
 * 包装数据集（FastAdmin 辅助函数）
 * 将数组或模型查询结果包装为 think\Collection 对象，便于链式调用 toArray 等
 */
if (!function_exists('collection')) {
    function collection($result)
    {
        if (is_array($result)) {
            $result = new \think\Collection($result);
        }
        return $result;
    }
}

/**
 * 构建页面标题区块（FastAdmin 辅助函数）
 * 返回页面顶部的标题与描述 HTML
 */
if (!function_exists('build_heading')) {
    function build_heading($title = null, $content = null, $model = null)
    {
        if ($title === null) {
            // 尝试从菜单表获取中文标题
            $controller = Request::instance()->controller();
            // 多级控制器名 agent.agent → agent/agent 匹配数据库 name 字段
            $ruleName = str_replace('.', '/', strtolower($controller));
            try {
                $menuTitle = \think\Db::name('auth_rule')
                    ->where('name', $ruleName)
                    ->value('title');
            } catch (\Exception $e) {
                $menuTitle = null;
            }
            $title = $menuTitle ?: ucwords(str_replace(['_', '.'], ' ', $controller));
            $title = __($title);
        }
        $html = '';
        if ($title) {
            $html .= '<div class="panel-heading"><h3 class="panel-title">' . htmlspecialchars($title) . '</h3></div>';
        }
        return $html;
    }
}

/**
 * CDN URL 处理（FastAdmin 辅助函数）
 */
if (!function_exists('cdnurl')) {
    function cdnurl($url, $domain = false)
    {
        // 本地资源直接返回，外部链接原样返回
        if (empty($url) || preg_match('/^((https?:)?\/\/|data:image\/)/i', $url)) {
            return $url;
        }
        $cdn = config('site.cdnurl') ?: '';
        return rtrim($cdn, '/') . '/' . ltrim($url, '/');
    }
}

/**
 * 构建工具栏按钮（FastAdmin 辅助函数）
 * 生成列表页的新增/编辑/删除等操作按钮
 */
if (!function_exists('build_toolbar')) {
    function build_toolbar($btns = null, $table = null)
    {
        $btns = $btns === null ? ['refresh', 'add', 'edit', 'del', 'import'] : (is_array($btns) ? $btns : explode(',', $btns));
        $btnAttr = [
            'refresh' => ['javascript:;', 'btn btn btn-primary btn-refresh', 'fa fa-refresh', ''],
            'add'     => ['javascript:;', 'btn btn-success btn-add', 'fa fa-plus', __('Add')],
            'edit'    => ['javascript:;', 'btn btn-success btn-edit btn-disabled disabled', 'fa fa-pencil', __('Edit')],
            'del'     => ['javascript:;', 'btn btn-danger btn-del btn-disabled disabled', 'fa fa-trash', __('Del')],
            'import'  => ['javascript:;', 'btn btn-info btn-import', 'fa fa-upload', __('Import')],
        ];
        $html = '<div class="toolbar btn-toolbar" style="margin-bottom:10px;">';
        foreach ($btns as $k => $v) {
            if (!isset($btnAttr[$v])) continue;
            list($href, $class, $icon, $text) = $btnAttr[$v];
            $html .= '<a href="' . $href . '" class="' . $class . '" ><i class="' . $icon . '"></i> ' . $text . '</a> ';
        }
        $html .= '</div>';
        return $html;
    }
}

/**
 * 构建下拉选择框（FastAdmin 辅助函数）
 */
if (!function_exists('build_select')) {
    function build_select($name, $options, $selected = [], $attr = [])
    {
        $selected = is_array($selected) ? $selected : explode(',', $selected);
        $attrStr = '';
        foreach ($attr as $k => $v) {
            $attrStr .= ' ' . $k . '="' . htmlspecialchars($v) . '"';
        }
        $html = '<select name="' . $name . '"' . $attrStr . '>';
        foreach ($options as $k => $v) {
            $isSel = in_array((string)$k, array_map('strval', $selected)) ? ' selected' : '';
            $html .= '<option value="' . htmlspecialchars($k) . '"' . $isSel . '>' . htmlspecialchars($v) . '</option>';
        }
        $html .= '</select>';
        return $html;
    }
}

/**
 * 生成时间日期（兼容别名）
 */
if (!function_exists('build_radios')) {
    function build_radios($name, $list, $selected = null)
    {
        $html = '';
        $selected = is_null($selected) ? key($list) : $selected;
        foreach ($list as $k => $v) {
            $checked = $k == $selected ? ' checked' : '';
            $html .= '<label><input type="radio" name="' . $name . '" value="' . htmlspecialchars($k) . '"' . $checked . '> ' . htmlspecialchars($v) . '</label> ';
        }
        return $html;
    }
}
