<?php

namespace app\common\model;

use think\Model;

/**
 * 附件模型
 */
class Attachment extends Model
{
    // 数据表默认主键
    protected $pk = 'id';

    /**
     * 获取mime类型列表
     * @return array
     */
    public static function getMimetypeList()
    {
        // 与Backend Upload控制器的mimetype分类保持一致
        return [
            '*'           => '所有类型',
            'image/*'     => '图片',
            'application/octet-stream' => '文件',
            'video/*'     => '视频',
            'audio/*'     => '音频',
            'text/*'      => '文档',
            'application/msword'       => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.ms-powerpoint' => 'ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'application/pdf' => 'pdf',
        ];
    }

    /**
     * 获取附件归类列表
     * @return array
     */
    public static function getCategoryList()
    {
        // 按需扩展: 前缀分类可由配置驱动(null防御: 未配置时explode(',',null)在PHP8告警)
        $categoryList = config('site.attachment_category') ?: '';
        $arr = [];
        foreach (explode(',', $categoryList) as $k => $v) {
            $arr[$v] = lang($v);
        }
        // 若未配置则给默认归类
        if (!$arr) {
            $arr = ['default' => 'Default'];
        }
        $arr['unclassed'] = '未归类';
        return $arr;
    }
}
