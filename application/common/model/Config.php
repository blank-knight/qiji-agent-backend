<?php

namespace app\common\model;

use think\Model;
use think\Db;

class Config extends Model
{
    // 表名
    protected $name = 'config';

    // 自动写入时间戳
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    /**
     * 获取配置分组列表
     */
    public static function getGroupList()
    {
        $list = self::field('id,name,`group`,title,type,value')->select();
        $groupList = [];
        foreach ($list as $k => $v) {
            $group = $v['group'];
            if (isset($groupList[$group])) {
                continue;
            }
            $groupList[$group] = $group;
        }
        return $groupList;
    }

    /**
     * 获取字段类型列表
     */
    public static function getTypeList()
    {
        return [
            'string'        => '字符',
            'text'          => '文本',
            'editor'        => '编辑器',
            'number'        => '数字',
            'date'          => '日期',
            'time'          => '时间',
            'datetime'      => '日期时间',
            'select'        => '列表',
            'selects'       => '列表(多选)',
            'image'         => '图片',
            'images'        => '图片(多选)',
            'file'          => '文件',
            'files'         => '文件(多选)',
            'switch'        => '开关',
            'checkbox'      => '复选',
            'radio'         => '单选',
            'city'          => '城市地区',
            'selectpage'    => '关联表',
            'array'         => '数组',
        ];
    }

    /**
     * 获取正则规则列表
     */
    public static function getRegexList()
    {
        return [
            'required' => '必选',
            'digits'   => '数字',
            'letters'  => '字母',
            'date'     => '日期',
            'time'     => '时间',
            'email'    => '邮箱',
            'url'      => '网址',
            'qq'       => 'QQ号',
            'IDcard'   => '身份证',
            'tel'      => '电话',
            'mobile'   => '手机',
            'zipcode'  => '邮编',
            'chinese'  => '中文',
            'username' => '用户名',
            'password' => '密码',
        ];
    }

    /**
     * 解析配置内容
     */
    public static function decode($string, $type = '')
    {
        if (empty($string)) {
            return [];
        }
        if ($type == 'array') {
            return json_decode($string, true);
        }
        $array = preg_split('/[,;\r\n]+/', trim($string, ",;\r\n"));
        if (strpos($string, ':')) {
            $value = [];
            foreach ($array as $val) {
                list($k, $v) = explode(':', $val);
                $value[$k] = $v;
            }
        } else {
            $value = $array;
        }
        return $value;
    }

    /**
     * 获取数组数据
     */
    public static function getArrayData($data)
    {
        if (!is_array($data)) {
            $array = preg_split('/[,;\r\n]+/', trim($data, ",;\r\n"));
            $data = [];
            foreach ($array as $val) {
                $val = trim($val);
                if ($val) {
                    $data[] = $val;
                }
            }
        }
        return $data;
    }

    /**
     * 刷新配置文件
     */
    public static function refreshFile()
    {
        // 简化实现：不需要文件缓存
        return true;
    }

    /**
     * 获取配置列表
     */
    public static function getConfigs()
    {
        $list = self::field('name,value')->select();
        $config = [];
        foreach ($list as $k => $v) {
            if (isset($config[$v['name']])) {
                continue;
            }
            $config[$v['name']] = $v['value'];
        }
        return $config;
    }
}
