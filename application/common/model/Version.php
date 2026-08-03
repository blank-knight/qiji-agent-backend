<?php

namespace app\common\model;

use think\Model;
use think\Db;

class Version extends Model
{
    // 表名
    protected $name = 'version';

    // 自动写入时间戳
    protected $autoWriteTimestamp = 'int';

    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    /**
     * 检查是否有新版本
     * @param string $version 当前版本号
     * @return array|false
     */
    public static function check($version)
    {
        $versionRow = self::where('status', 'normal')
            ->order('id', 'desc')
            ->find();

        if (!$versionRow) {
            return false;
        }

        // 比较版本号
        if (self::compareVersion($versionRow['newversion'], $version) > 0) {
            return [
                'enforce'     => $versionRow['enforce'],
                'newversion'  => $versionRow['newversion'],
                'downloadurl' => $versionRow['downloadurl'],
                'packagesize' => $versionRow['packagesize'],
                'upgradetext' => $versionRow['upgradetext'],
            ];
        }

        return false;
    }

    /**
     * 版本号比较
     * @return int -1/0/1
     */
    private static function compareVersion($v1, $v2)
    {
        $v1Parts = explode('.', $v1);
        $v2Parts = explode('.', $v2);

        $maxLen = max(count($v1Parts), count($v2Parts));

        for ($i = 0; $i < $maxLen; $i++) {
            $v1Part = isset($v1Parts[$i]) ? (int)$v1Parts[$i] : 0;
            $v2Part = isset($v2Parts[$i]) ? (int)$v2Parts[$i] : 0;

            if ($v1Part > $v2Part) {
                return 1;
            } elseif ($v1Part < $v2Part) {
                return -1;
            }
        }

        return 0;
    }
}
