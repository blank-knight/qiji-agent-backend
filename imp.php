<?php
// +----------------------------------------------------------------------
// | 下级后台独立入口（impersonate 隔离会话）
// |
// | 与 admin.php 唯一区别：session cookie 名不同（PHPSESSIMP），使"进入下级
// | 后台"的新标签与原超管标签各自持有独立会话——原标签保持超管身份不受
// | 领票切换影响，真正并行操作。票据跨会话传递：领票时读主会话的票据存
// | 值（loginas 写入），本会话无主会话上下文也能完成切换。
// +----------------------------------------------------------------------

define('APP_PATH', __DIR__ . '/application/');

// 绑定到 admin 模块
define('BIND_MODULE', 'admin');

// 独立 session cookie 名 —— 必须在框架启动前生效
ini_set('session.name', 'PHPSESSIMP');
ini_set('session.use_cookies', 1);

// 加载框架引导文件
require __DIR__ . '/thinkphp/base.php';

// PHP 8 兼容：非调试模式下屏蔽 E_WARNING / E_DEPRECATED
if (!\think\Env::get('app.debug', false)) {
    error_reporting(E_ALL & ~E_WARNING & ~E_DEPRECATED & ~E_NOTICE);
}

// 修复 ThinkPHP 5.0 多级控制器 URL 后缀解析 bug（同 admin.php）
if (isset($_SERVER['PATH_INFO'])) {
    $_SERVER['PATH_INFO'] = preg_replace('/\.html$/i', '', $_SERVER['PATH_INFO']);
}

// 执行应用
\think\App::run()->send();
// DEBUG
