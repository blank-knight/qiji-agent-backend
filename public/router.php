<?php
/**
 * PHP 内置服务器路由器
 * 解决 /admin.php/controller/action 形式的 URL 在内置服务器下的路由问题
 */
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = urldecode($uri);

// 处理多入口: /admin.php/xxx, /agent.php/xxx, /index.php/xxx
// 必须在静态资源检查之前处理，否则 /admin.php/controller/action.html 会被误判为静态文件
$entryPoints = ['index.php', 'admin.php', 'agent.php'];
$matchedEntry = null;
foreach ($entryPoints as $entry) {
    $prefix = '/' . $entry;
    if ($uri === $prefix || strpos($uri, $prefix . '/') === 0) {
        $matchedEntry = $entry;
        break;
    }
}

if ($matchedEntry !== null) {
    $prefix = '/' . $matchedEntry;
    $pathInfo = substr($uri, strlen($prefix));
    $_SERVER['PATH_INFO'] = $pathInfo;
    $_SERVER['SCRIPT_NAME'] = $prefix;
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/' . $matchedEntry;
    $_SERVER['PHP_SELF'] = $prefix . $pathInfo;
    require __DIR__ . '/' . $matchedEntry;
    return true;
}

// 静态资源直接返回（仅对非入口路径）
if ($uri !== '/' && preg_match('/\.(css|js|jpg|jpeg|png|gif|ico|svg|woff|woff2|ttf|eot|map)$/i', $uri)) {
    $file = __DIR__ . $uri;
    if (file_exists($file)) {
        return false;
    }
}

// 直接访问的 PHP 文件
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

// 默认路由到 index.php
$_SERVER['PATH_INFO'] = $uri;
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/index.php';
$_SERVER['PHP_SELF'] = '/index.php' . $uri;
require __DIR__ . '/index.php';
