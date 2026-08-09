<?php
// +----------------------------------------------------------------------
// | 管理后台入口
// +----------------------------------------------------------------------

define('APP_PATH', __DIR__ . '/../application/');

// 绑定到 admin 模块
define('BIND_MODULE', 'admin');

// 加载框架引导文件
require __DIR__ . '/../thinkphp/base.php';

// PHP 8 兼容：非调试模式下屏蔽 E_WARNING / E_DEPRECATED
// ThinkPHP 5 的 Error::register() 设了 E_ALL，会把 warning 转成异常导致页面崩溃
if (!\think\Env::get('app.debug', false)) {
    error_reporting(E_ALL & ~E_WARNING & ~E_DEPRECATED & ~E_NOTICE);
}

// 修复 ThinkPHP 5.0 多级控制器 URL 后缀解析 bug：
// 当 pathinfo 为 /auth/group/add.html 时，ThinkPHP 把 action 解析为 "add.html" 而非 "add"
// 这里在框架启动前剥离末尾的 .html，确保 action 名称正确
if (isset($_SERVER['PATH_INFO'])) {
    $_SERVER['PATH_INFO'] = preg_replace('/\.html$/i', '', $_SERVER['PATH_INFO']);
}

// 执行应用
\think\App::run()->send();
