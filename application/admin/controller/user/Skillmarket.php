<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;
use think\Db;

/**
 * 技能市场管理
 * 上架技能：填名称/标题/描述 + 上传 zip（zip 内需含 SKILL.md，可为单技能或分类目录）
 * zip 存 public/downloads/skills/，客户端走 /api/client/v1/skill/download 下载
 */
class Skillmarket extends Backend
{

    public function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {
        if ($this->request->isAjax()) {
            $rows = Db::name('skill_market')->order('weigh desc, id desc')->select();
            foreach ($rows as &$r) {
                $r['size_text']  = $r['filesize'] > 1048576 ? round($r['filesize'] / 1048576, 1) . ' MB' : round($r['filesize'] / 1024, 1) . ' KB';
                $r['status_text'] = $r['status'] === 'normal' ? '上架' : '下架';
            }
            return json(['total' => count($rows), 'rows' => $rows]);
        }
        return $this->view->fetch();
    }

    /** 新增/编辑（POST 表单：id(编辑),name,title,description,category,version,weigh,status + file(zip)） */
    public function save()
    {
        if (!$this->request->isPost()) {
            $this->error('非法请求');
        }
        $id     = (int)$this->request->post('id', 0);
        $name   = trim($this->request->post('name', ''));
        $title  = trim($this->request->post('title', ''));
        $desc   = trim($this->request->post('description', ''));
        $cat    = trim($this->request->post('category', 'general')) ?: 'general';
        $ver    = trim($this->request->post('version', '1.0.0')) ?: '1.0.0';
        $weigh  = (int)$this->request->post('weigh', 0);
        $status = $this->request->post('status', 'normal') === 'hidden' ? 'hidden' : 'normal';

        if (!preg_match('/^[a-z0-9][a-z0-9-_]*$/i', $name)) {
            $this->error('技能标识只能含字母数字、中划线、下划线');
        }
        if (!$title) {
            $this->error('请填写技能名称');
        }

        $data = [
            'name' => strtolower($name), 'title' => $title, 'description' => $desc,
            'category' => $cat, 'version' => $ver, 'weigh' => $weigh, 'status' => $status,
            'updatetime' => time(),
        ];

        // 上传 zip（可选：编辑时不传则保留旧文件）
        $file = $this->request->file('file');
        if ($file) {
            $info = $file->validate(['size' => 50 * 1024 * 1024, 'ext' => 'zip'])->move(ROOT_PATH . 'public/downloads/skills', '');
            if (!$info) {
                $this->error('zip 上传失败：' . $file->getError());
            }
            $savedPath = $info->getRealPath();
            $filename  = strtolower($name) . '-' . $ver . '.zip';
            $dest      = ROOT_PATH . 'public/downloads/skills/' . $filename;
            if (!@rename($savedPath, $dest)) {
                $this->error('保存文件失败');
            }
            // zip 内容校验：必须包含 SKILL.md（根级或一级子目录）
            $hasSkillMd = false;
            $zip = new \ZipArchive();
            if ($zip->open($dest) === true) {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $entry = $zip->getNameIndex($i);
                    if (preg_match('#^[a-z0-9-_]+/SKILL\.md$#i', $entry) || strtolower($entry) === 'skill.md') {
                        $hasSkillMd = true;
                        break;
                    }
                }
                $zip->close();
            }
            if (!$hasSkillMd) {
                @unlink($dest);
                $this->error('zip 内未找到 SKILL.md（需在根目录或一级子目录）');
            }
            $data['filename'] = $filename;
            $data['filesize'] = filesize($dest);
        } elseif (!$id) {
            $this->error('新增技能必须上传 zip');
        }

        if ($id) {
            Db::name('skill_market')->where('id', $id)->update($data);
        } else {
            $data['createtime'] = time();
            $data['download_count'] = 0;
            if (Db::name('skill_market')->where('name', $data['name'])->find()) {
                $this->error('技能标识已存在');
            }
            Db::name('skill_market')->insert($data);
        }
        $this->success('保存成功');
    }

    /** 上下架切换 */
    public function toggle()
    {
        if (!$this->request->isPost()) {
            $this->error('非法请求');
        }
        $id = (int)$this->request->post('id', 0);
        $row = Db::name('skill_market')->where('id', $id)->find();
        if (!$row) {
            $this->error('不存在');
        }
        $new = $row['status'] === 'normal' ? 'hidden' : 'normal';
        Db::name('skill_market')->where('id', $id)->update(['status' => $new, 'updatetime' => time()]);
        $this->success('已' . ($new === 'normal' ? '上架' : '下架'));
    }

    /** 删除记录（zip 保留在服务器） */
    public function remove()
    {
        if (!$this->request->isPost()) {
            $this->error('非法请求');
        }
        $id = (int)$this->request->post('id', 0);
        Db::name('skill_market')->where('id', $id)->delete();
        $this->success('已删除');
    }
}
