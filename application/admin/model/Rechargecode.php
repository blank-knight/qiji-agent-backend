<?php

namespace app\admin\model;

use think\Model;

class Rechargecode extends Model
{
    // 表名（fa_ 前缀后接 recharge_code）
    protected $name = 'recharge_code';

    // 自动写入时间戳
    protected $autoWriteTimestamp = 'int';

    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
}

