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

// 执行应用
\think\App::run()->send();
