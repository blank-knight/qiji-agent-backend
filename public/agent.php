<?php
// +----------------------------------------------------------------------
// | 代理后台入口
// +----------------------------------------------------------------------

define('APP_PATH', __DIR__ . '/../application/');

// 绑定到 agent 模块
\think\Route::bind('agent');

// 关闭路由
\think\App::route(false);

// 设置根域名
\think\Url::root('');

require __DIR__ . '/../thinkphp/base.php';

\think\App::run()->send();
