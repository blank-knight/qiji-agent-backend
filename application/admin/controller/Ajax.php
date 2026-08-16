<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Config;
use think\Cache;
use think\Validate;
use think\Db;

/**
 * Ajax通用接口
 */
class Ajax extends Backend
{
    protected $noNeedLogin = ['lang'];
    protected $noNeedRight = ['*'];
    protected $layout = '';

    /**
     * 语言文件加载
     */
    public function lang()
    {
        $this->request->filter(['trim']);
        $controllername = $this->request->get('controllername', '');
        $this->loadlang($controllername);
        $langs = \think\Lang::get();
        // RequireJS 通过 callback=define 以 JSONP 方式加载，需要 AMD 格式
        $callback = $this->request->get('callback', '');
        if ($callback === 'define') {
            return response($callback . '(' . json_encode($langs) . ');')->header(['Content-Type' => 'application/javascript']);
        }
        return json($langs);
    }

    /**
     * 清空系统缓存
     */
    public function wipecache()
    {
        $type = $this->request->request('type');
        switch ($type) {
            case 'content':
                \think\Cache::clear();
                break;
            case 'template':
                $this->clearTemplateCache();
                break;
            case 'addons':
                break;
            case 'all':
            default:
                \think\Cache::clear();
                $this->clearTemplateCache();
                break;
        }

        \think\Hook::listen("wipecache_after");
        $this->success();
    }

    /**
     * 清理模板编译缓存(仅限 runtime/temp/ 目录, 绝不相对路径glob)
     * 修复: Config里无template.compiled_path键时glob('*')会匹配public/下所有文件并删除
     */
    protected function clearTemplateCache()
    {
        $dir = TEMP_PATH; // RUNTIME_PATH . 'temp' . DS, 由框架定义
        if (!is_dir($dir)) {
            return;
        }
        $tempFiles = glob($dir . '*');
        if ($tempFiles) {
            foreach ($tempFiles as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }
    }

    /**
     * 通用文件上传
     */
    public function upload()
    {
        $file = $this->request->file('file');
        if (!$file) {
            $this->error(__('No file choose'));
        }

        $uploadDir = ROOT_PATH . 'public' . DS . 'uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $info = $file->validate(['size' => 10485760, 'ext' => 'jpg,jpeg,png,gif,bmp'])
            ->move($uploadDir);

        if ($info) {
            $fileName = $info->getSaveName();
            $url = '/uploads/' . str_replace('\\', '/', $fileName);

            // mime检测: fileinfo扩展可能未安装(致命错误), 带fallback
            $mime = 'application/octet-stream';
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $info->getPathname());
                finfo_close($finfo);
            } elseif (function_exists('mime_content_type')) {
                $mime = @mime_content_type($info->getPathname());
            } else {
                // 图片场景兜底: getimagesize能返回mime, 非图片返回false
                $imgInfo = @getimagesize($info->getPathname());
                if ($imgInfo && !empty($imgInfo['mime'])) {
                    $mime = $imgInfo['mime'];
                }
            }

            Db::name('attachment')->insert([
                'admin_id'   => $this->auth->id,
                'url'        => $url,
                'filename'   => $info->getFilename(),
                'filesize'   => $info->getSize(),
                'mimetype'   => $mime,
                'extension'  => strtolower($info->getExtension()),
                'uploadtime' => time(),
                'storage'    => 'local',
            ]);

            $this->success(__('Upload successful'), null, [
                'url'     => $url,
                'fullurl' => $url,
            ]);
        } else {
            $this->error($file->getError());
        }
    }

    /**
     * 通用排序
     */
    public function weigh()
    {
        $ids = $this->request->post('ids');
        $changeids = $this->request->post('changeids');

        if (!$ids) {
            $this->error(__('Parameter %s can not be empty', 'ids'));
        }

        $pk = $this->model->getPk();
        $table = $this->model->getQuery()->getTable();

        $idsArray = explode(',', $ids);

        foreach ($idsArray as $n => $id) {
            Db::name($table)->where($pk, $id)->update(['weigh' => $n + 1]);
        }

        $this->success();
    }

    /**
     * 通用状态修改
     */
    public function state()
    {
        $id = $this->request->post('id/d');
        $field = $this->request->post('field', 'status');
        $value = $this->request->post('value');

        if (!$id) {
            $this->error(__('Parameter %s can not be empty', 'id'));
        }

        $pk = $this->model->getPk();
        $result = $this->model->where($pk, $id)->update([$field => $value]);

        if ($result !== false) {
            $this->success();
        } else {
            $this->error($this->model->getError());
        }
    }
}
