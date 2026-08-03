<?php
// +----------------------------------------------------------------------
// | API模块配置
// +----------------------------------------------------------------------

return [
    // 默认模块名
    'default_module'       => 'api',
    // 默认控制器名
    'default_controller'   => 'Index',
    // 默认操作名
    'default_action'       => 'index',
    // 默认输出类型（API统一用JSON）
    'default_return_type'  => 'json',
    // 默认AJAX数据返回格式
    'default_ajax_return'  => 'json',
    // 是否开启路由
    'url_route_on'         => true,
    // 是否强制使用路由
    'url_route_must'       => false,
    // URL伪静态后缀（API不需要）
    'url_html_suffix'      => false,
];
