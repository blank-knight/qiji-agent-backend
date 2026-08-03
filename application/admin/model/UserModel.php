<?php

namespace app\admin\model;

use think\Cache;
use think\Model;

class UserModel extends Model
{

    // 表名
    protected $name = 'user_model';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = '';
    // 定义时间戳字段名
    protected $createTime = '';
    protected $updateTime = '';
    // 数据自动完成字段


}
