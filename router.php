<?php
// PHP 内置服务器路由文件
// 静态资源直接返回，不走应用入口

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// 如果请求包含 admin.php 或 index.php 入口，一定交给 ThinkPHP 处理
// （包括 admin.php/agent.html 这种带 .html 后缀的 pathinfo URL）
if (preg_match('#/(admin|index)\.php#i', $uri)) {
    require __DIR__ . '/admin.php';
    return;
}

// 静态资源扩展名列表（不含 html/htm，因为 .html 可能是 ThinkPHP URL 后缀）
$staticExts = ['css','js','woff2','woff','ttf','eot','otf','png','jpg','jpeg','gif','svg','ico','map'];

// FastAdmin 静态资源在 public/ 下，URL 为 /assets/ /uploads/ 等
$paths = [
    __DIR__ . '/public' . $uri,
    __DIR__ . $uri,
];

$ext = pathinfo($uri, PATHINFO_EXTENSION);

foreach ($paths as $filePath) {
    if ($uri !== '/' && file_exists($filePath) && !is_dir($filePath)) {
        // PHP 文件必须交给应用入口执行
        if ($ext === 'php') {
            break;
        }
        $types = [
            'css'   => 'text/css',
            'js'    => 'application/javascript',
            'woff2' => 'font/woff2',
            'woff'  => 'font/woff',
            'ttf'   => 'font/ttf',
            'eot'   => 'application/vnd.ms-fontobject',
            'otf'   => 'font/otf',
            'png'   => 'image/png',
            'jpg'   => 'image/jpeg',
            'jpeg'  => 'image/jpeg',
            'gif'   => 'image/gif',
            'svg'   => 'image/svg+xml',
            'ico'   => 'image/x-icon',
            'map'   => 'application/json',
        ];
        if (isset($types[$ext])) {
            header('Content-Type: ' . $types[$ext]);
        }
        readfile($filePath);
        return true;
    }
}

// 对静态资源类型的请求，如果文件不存在，返回404
if (in_array($ext, $staticExts)) {
    http_response_code(404);
    return true;
}

// /api/ 开头的请求走 index.php（前台/API入口，不绑定模块）
if (preg_match('#^/api/#i', $uri)) {
    require __DIR__ . '/index.php';
    return;
}

// 其余请求交给后台入口
require __DIR__ . '/admin.php';
