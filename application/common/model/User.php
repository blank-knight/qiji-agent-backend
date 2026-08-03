<?php

namespace app\common\model;

use think\Model;
use think\Db;

/**
 * 用户公共模型
 * 给 API 层的 Auth 控制器使用
 */
class User extends Model
{
    // 表名
    protected $name = 'user';

    // 自动写入时间戳
    protected $autoWriteTimestamp = 'int';

    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    /**
     * 密码加密（FastAdmin 双重 MD5）
     */
    public static function getEncryptPassword($password, $salt = '')
    {
        return md5(md5($password) . $salt);
    }

    /**
     * 获取用户信息（含代理名称）
     */
    public static function info($userId)
    {
        $user = self::find($userId);
        if (!$user) {
            return null;
        }

        $agentName = '';
        if ($user['agent_id']) {
            $agent = Db::name('agent')->where('id', $user['agent_id'])->find();
            if ($agent) {
                $agentName = $agent['name'];
            }
        }

        return [
            'id'            => $user['id'],
            'username'      => $user['username'] ?: $user['mobile'],
            'mobile'        => $user['mobile'],
            'score'         => (int)$user['score'],
            'mode'          => $user['mode'] ?: ($user['agent_id'] ? 'formal' : 'trial'),
            'is_custom_key' => (int)$user['is_custom_key'],
            'agent_name'    => $agentName,
        ];
    }
}
