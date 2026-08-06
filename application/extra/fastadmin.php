<?php

// FastAdmin 核心配置
return [
    // 后台皮肤
    'adminskin'          => 'skin-black-blue',
    // 是否开启多标签页
    'multipletab'        => 1,
    // 是否开启多级导航
    'multiplenav'        => 0,
    // 是否固定导航
    'isfixednav'         => 0,
    // 是否固定侧边栏
    'isfixedsidebar'     => 1,
    // 是否显示子菜单
    'show_submenu'       => 1,
    // 登录验证码
    'login_captcha'      => 0,
    // 登录唯一（同一账号只能一处登录）
    'login_unique'       => 0,
    // 登录失败重试限制
    'login_failure_retry'=> 0,
    // 登录IP检查
    'loginip_check'      => 0,
    // CORS 允许域名
    'cors_request_domain'=> '',
    // 钩子配置
    'hooks'              => [],
    // 模块URL前缀
    'moduleurl'          => '',
    // 是否自动生成API文档
    'autocode'           => 0,
];
