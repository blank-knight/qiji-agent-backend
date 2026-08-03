<?php

// 本地测试用 SQLite 配置
// 生产环境改回 MySQL：把此文件删除或改 type 为 mysql
use think\Env;

return [
    // 数据库类型
    'type'            => Env::get('database.type', 'sqlite'),
    // 服务器地址
    'hostname'        => Env::get('database.hostname', ''),
    // 数据库名
    'database'        => Env::get('database.type', 'sqlite') === 'sqlite'
        ? ROOT_PATH . 'runtime/qiji_admin.db'
        : Env::get('database.database', 'qiji_admin'),
    // 用户名
    'username'        => Env::get('database.username', ''),
    // 密码
    'password'        => Env::get('database.password', ''),
    // 端口
    'hostport'        => Env::get('database.hostport', ''),
    // 连接dsn
    'dsn'             => '',
    // 数据库连接参数
    'params'          => [],
    // 数据库编码默认采用utf8
    'charset'         => Env::get('database.charset', 'utf8mb4'),
    // 数据库表前缀
    'prefix'          => Env::get('database.prefix', 'fa_'),
    // 数据库调试模式
    'debug'           => Env::get('app.debug', true),
    // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
    'deploy'          => 0,
    // 数据库读写是否分离 主从式部署有效
    'rw_separate'     => false,
    // 读写分离后 主服务器数量
    'master_num'      => 1,
    // 指定从服务器序号
    'slave_no'        => '',
    // 是否严格检查字段是否存在
    'fields_strict'   => false,
    // 数据集返回类型
    'resultset_type'  => 'array',
    // 自动写入时间戳字段
    'auto_timestamp'  => false,
    // 时间字段取出后的默认时间格式
    'datetime_format' => 'Y-m-d H:i:s',
    // 是否需要进行SQL性能分析
    'sql_explain'     => false,
];
