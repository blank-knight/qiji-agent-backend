<?php

namespace app\admin\model;

use think\Model;
use think\Db;

class Agent extends Model
{
    protected $name = 'agent';

    protected $autoWriteTimestamp = 'int';

    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    protected $append = [
        'status_text',
        'type_text',
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

    public function getTypeList()
    {
        return ['tiepai' => '贴牌商', 'agent' => '代理'];
    }

    public function getTypeTextAttr($value, $data)
    {
        $list = $this->getTypeList();
        return isset($list[$data['type']]) ? $list[$data['type']] : '';
    }

    /**
     * 计算path：/父path/自己id/
     */
    public static function computePath($agentId, $parentId = 0)
    {
        if ($parentId == 0) {
            return '/' . $agentId . '/';
        }
        $parent = self::get($parentId);
        if (!$parent) {
            return '/' . $agentId . '/';
        }
        return rtrim($parent['path'], '/') . '/' . $agentId . '/';
    }

    /**
     * 获取某个agent的所有子孙ID（含自己）
     */
    public static function getDescendantIds($agentId)
    {
        $agent = self::get($agentId);
        if (!$agent) {
            return [];
        }
        $path = $agent['path'];
        // 匹配 path LIKE '%/agentId/%' 的所有记录
        $ids = self::where('path', 'like', $path . '%')->column('id');
        return $ids ? $ids : [$agentId];
    }

    /**
     * 获取某个agent的所有下级代理ID（不含自己）
     */
    public static function getChildrenIds($agentId)
    {
        $descendants = self::getDescendantIds($agentId);
        return array_diff($descendants, [$agentId]);
    }
}
