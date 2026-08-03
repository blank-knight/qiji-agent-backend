<?php

namespace app\common\model;

use think\Model;

class AgentInvite extends Model
{
    // 表名
    protected $name = 'agent_invite';

    // 自动写入时间戳
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    // 追加属性
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
