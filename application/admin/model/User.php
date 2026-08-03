<?php

namespace app\admin\model;

use think\Model;
use think\Db;

class User extends Model
{
    // 表名
    protected $name = 'user';

    // 自动写入时间戳
    protected $autoWriteTimestamp = 'int';

    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    /**
     * 配额继承 beforeUpdate 钩子
     * 用户 score 变动时，自动从代理扣减/返还
     */
    public static function onBeforeUpdate($self)
    {
        $data = $self->getData();

        // 只在 score 字段发生变化时触发
        if (!isset($data['score'])) {
            return true;
        }

        $userId     = isset($data['id']) ? $data['id'] : ($self->id ?? 0);
        if (!$userId) {
            return true;
        }

        $oldUser = Db::name('user')->where('id', $userId)->find();
        if (!$oldUser) {
            return true;
        }

        $oldScore = (int)$oldUser['score'];
        $newScore = (int)$data['score'];
        $diff     = $newScore - $oldScore;

        // 没有变化
        if ($diff == 0) {
            return true;
        }

        $agentId = isset($data['agent_id']) ? $data['agent_id'] : $oldUser['agent_id'];
        if (!$agentId) {
            return true;
        }

        // 代理 score 反向变动（用户加 → 代理减）
        $agentScoreDiff = -$diff;

        Db::name('agent')->where('id', $agentId)->setInc('score', $agentScoreDiff);

        return true;
    }
}
