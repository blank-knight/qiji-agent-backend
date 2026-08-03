<?php
// +----------------------------------------------------------------------
// | QIJI Agent 后端 — 主入口
// +----------------------------------------------------------------------

// 定义项目路径
define('APP_PATH', __DIR__ . '/../application/');

// 加载框架引导文件
require __DIR__ . '/../thinkphp/base.php';

// PHP 8 兼容：非调试模式下屏蔽 E_WARNING / E_DEPRECATED
if (!\think\Env::get('app.debug', false)) {
    error_reporting(E_ALL & ~E_WARNING & ~E_DEPRECATED & ~E_NOTICE);
}

// 执行应用
\think\App::run()->send();
