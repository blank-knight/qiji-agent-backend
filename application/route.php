<?php

// +----------------------------------------------------------------------
// | 路由设置
// +----------------------------------------------------------------------

use think\Route;

// 客户端 API 路由
// ThinkPHP 5.0 子目录控制器用点号: api/client.Auth = application/api/controller/client/Auth.php
Route::group('api/client/v1', function () {
    // 认证
    Route::post('auth/register', 'api/client.Auth/register');
    Route::post('auth/login', 'api/client.Auth/login');
    Route::post('auth/activate', 'api/client.Auth/activate');
    Route::get('auth/forgottip', 'api/client.Auth/forgottip');
    Route::post('auth/changepwd', 'api/client.Auth/changepwd');

    // 额度
    Route::get('quota', 'api/client.Quota/index');
    Route::post('quota/report', 'api/client.Quota/report');

    // API Key
    Route::get('apikey', 'api/client.Apikey/index');
    Route::post('apikey/customize', 'api/client.Apikey/customize');

    // 个人中心
    Route::get('profile/scorelogs', 'api/client.Profile/scorelogs');
    Route::get('profile', 'api/client.Profile/index');
    Route::post('profile/update', 'api/client.Profile/update');
    Route::post('profile/avatar', 'api/client.Profile/avatar');

    // 充值
    Route::post('recharge/redeem', 'api/client.Recharge/redeem');

    // 套餐购买
    Route::get('plan/index', 'api/client.Plan/index');
    Route::post('plan/order', 'api/client.Plan/order');
    Route::get('plan/status', 'api/client.Plan/status');
    Route::any('plan/notify', 'api/client.Plan/notify');
    Route::get('skill/list', 'api/client.Skill/list');
    Route::get('skill/download', 'api/client.Skill/download');
    Route::any('plan/return', 'api/client.Plan/orderreturn');

    // 更新检查
    Route::get('update/check', 'api/client.Update/check');
});

// 兼容不带 v1 前缀的请求
Route::group('api/client', function () {
    Route::post('auth/register', 'api/client.Auth/register');
    Route::post('auth/login', 'api/client.Auth/login');
    Route::post('auth/activate', 'api/client.Auth/activate');
    Route::get('auth/forgottip', 'api/client.Auth/forgottip');
    Route::post('auth/changepwd', 'api/client.Auth/changepwd');
    Route::get('quota', 'api/client.Quota/index');
    Route::post('quota/report', 'api/client.Quota/report');
    Route::get('apikey', 'api/client.Apikey/index');
    Route::post('apikey/customize', 'api/client.Apikey/customize');
    Route::get('profile/scorelogs', 'api/client.Profile/scorelogs');
    Route::get('profile', 'api/client.Profile/index');
    Route::post('profile/update', 'api/client.Profile/update');
    Route::post('profile/avatar', 'api/client.Profile/avatar');
    Route::post('recharge/redeem', 'api/client.Recharge/redeem');
    Route::get('update/check', 'api/client.Update/check');
});
