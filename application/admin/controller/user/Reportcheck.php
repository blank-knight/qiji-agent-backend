<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;
use think\Db;

/**
 * 上报异常检测（防白嫖）
 * 规则一【活跃零上报】：3 天内登录过、但从未上报过计费、且用平台 Key（is_custom_key=0）且积分>0
 *           → 疑点：一直在用但从不扣费（客户端被改 / 抓包拿 key 直调）
 * 规则二【上报中断】：历史有上报、最近 7 天有登录、但 3 天以上未上报
 *           → 疑点：中途停报（改客户端 / 换工具直连）
 */
class Reportcheck extends Backend
{

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {
        if ($this->request->isAjax()) {
            $threeDaysAgo = time() - 3 * 86400;
            $sevenDaysAgo = time() - 7 * 86400;

            // 规则一：活跃零上报（有积分、平台key、近3天登录过、从未上报）
            $rows1 = Db::name('user')
                ->field('id,username,mobile,score,agent_id,logintime,last_report_time,is_custom_key')
                ->where('is_custom_key', 0)
                ->where('score', '>', 0)
                ->where('logintime', '>=', $threeDaysAgo)
                ->where(function ($q) {
                    $q->where('last_report_time', 0);
                })
                ->select();
            foreach ($rows1 as &$r) {
                $r['rule'] = '活跃零上报';
                $r['risk'] = '高';
            }

            // 规则二：上报中断（有历史上报、近7天登录、3天+未上报）
            $rows2 = Db::name('user')
                ->field('id,username,mobile,score,agent_id,logintime,last_report_time,is_custom_key')
                ->where('is_custom_key', 0)
                ->where('last_report_time', '>', 0)
                ->where('last_report_time', '<', $threeDaysAgo)
                ->where('logintime', '>=', $sevenDaysAgo)
                ->select();
            foreach ($rows2 as &$r) {
                $r['rule'] = '上报中断';
                $r['risk'] = '中';
            }

            $rows = array_merge($rows1, $rows2);
            foreach ($rows as &$r) {
                $r['logintime_text'] = $r['logintime'] ? date('m-d H:i', $r['logintime']) : '-';
                $r['last_report_text'] = $r['last_report_time'] ? date('m-d H:i', $r['last_report_time']) : '从未';
            }

            return json(['total' => count($rows), 'rows' => $rows]);
        }
        return $this->view->fetch();
    }
}
