<?php

// +----------------------------------------------------------------------
// | 路由设置
// +----------------------------------------------------------------------

use think\Route;

// 客户端 API 路由（/api/client/v1/* → api/controller/client/*）
Route::group('api/client/v1', function () {
    // 认证
    Route::post('auth/register', 'api/client/Auth/register');
    Route::post('auth/login', 'api/client/Auth/login');
    Route::post('auth/activate', 'api/client/Auth/activate');

    // 额度
    Route::get('quota', 'api/client/Quota/index');
    Route::post('quota/report', 'api/client/Quota/report');

    // API Key
    Route::get('apikey', 'api/client/Apikey/index');
    Route::post('apikey/customize', 'api/client/Apikey/customize');

    // 更新检查
    Route::get('update/check', 'api/client/Update/check');
});

// 兼容不带 v1 前缀的请求
Route::group('api/client', function () {
    Route::post('auth/register', 'api/client/Auth/register');
    Route::post('auth/login', 'api/client/Auth/login');
    Route::post('auth/activate', 'api/client/Auth/activate');
    Route::get('quota', 'api/client/Quota/index');
    Route::post('quota/report', 'api/client/Quota/report');
    Route::get('apikey', 'api/client/Apikey/index');
    Route::post('apikey/customize', 'api/client/Apikey/customize');
    Route::get('update/check', 'api/client/Update/check');
});
