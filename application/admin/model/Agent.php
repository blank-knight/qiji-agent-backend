<?php

namespace app\admin\model;

use think\Model;

class Agent extends Model
{
    protected $name = 'agent';

    protected $autoWriteTimestamp = 'int';

    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    protected $append = [
        'status_text',
    ];

    public function getStatusList()
    {
        return ['normal' => '正常', 'hidden' => '禁用'];
    }

    public function getStatusTextAttr($value, $data)
    {
        $list = $this->getStatusList();
        return isset($list[$data['status']]) ? $list[$data['status']] : '';
    }
}
