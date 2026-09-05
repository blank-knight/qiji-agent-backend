<?php

namespace app\admin\model;

use think\Model;

class ScorePlan extends Model
{
    protected $name = 'score_plan';

    protected $autoWriteTimestamp = 'int';

    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    public function getStatusList()
    {
        return ['normal' => '上架', 'hidden' => '下架'];
    }

    public function getStatusTextAttr($value, $data)
    {
        $list = $this->getStatusList();
        return isset($list[$data['status']]) ? $list[$data['status']] : '';
    }

    protected $append = [
        'status_text',
    ];
}
