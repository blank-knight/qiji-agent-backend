<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\admin\model\AuthGroup;
use app\admin\model\AuthGroupAccess;
use think\Db;
use think\Config;

/**
 * 后台Dashboard仪表盘
 */
class Dashboard extends Backend
{
    /**
     * 查看
     */
    public function index()
    {
        $seventtime = \fast\Date::unixtime('day', -7);
        $paylist = [];
        $createlist = [];

        for ($i = 0; $i < 7; $i++) {
            $day = date("Y-m-d", $seventtime + ($i * 86400));
            $createlist[$day] = mt_rand(20, 200);
            $paylist[$day] = mt_rand(1, 80);
        }

        $hooks = config('fastadmin.hooks');
        $this->view->assign([
            'totaluser'        => Db::name('user')->count(),
            'totalviews'       => Db::name('user')->sum('successions'),
            'totalorder'       => Db::name('user_score_log')->where('score', '<', 0)->count(),
            'totalorderamount' => abs(Db::name('user_score_log')->where('score', '<', 0)->sum('score')),
            'todayuserlogin'   => Db::name('user')->whereTime('logintime', 'today')->count(),
            'todayusersignup'  => Db::name('user')->whereTime('jointime', 'today')->count(),
            'todayorder'       => Db::name('user_score_log')->where('score', '<', 0)->whereTime('createtime', 'today')->count(),
            'unsettleorder'    => Db::name('user_score_log')->where('score', '<', 0)->whereTime('createtime', 'today')->sum('score'),
            'sevendnu'         => Db::name('user')->whereTime('jointime', 'week')->count(),
            'sevendau'         => Db::name('user')->whereTime('logintime', 'week')->count(),
            'paylist'          => $paylist,
            'createlist'       => $createlist,
            'activeusers'      => Db::name('user')->count(),
        ]);

        return $this->view->fetch();
    }
}
