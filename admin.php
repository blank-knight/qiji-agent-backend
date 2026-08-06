<?php
// +----------------------------------------------------------------------
// | 后台入口文件
// +----------------------------------------------------------------------

// PHP 8.1: 屏蔽 deprecation 警告（TP5.0 模板中大量 null 传参）
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

// 定义后台模块绑定
define('BIND_MODULE', 'admin');

// 定义应用目录
define('APP_PATH', __DIR__ . '/application/');

// 加载框架基础文件
require __DIR__ . '/thinkphp/base.php';

// 执行应用并返回响应
\think\App::run()->send();
