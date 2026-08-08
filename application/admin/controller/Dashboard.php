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
    // 模板自带完整HTML，禁用layout
    protected $layout = '';

    /**
     * 查看
     */
    public function index()
    {
        // 近30天数据
        $thirtyDaysAgo = strtotime('-30 days', strtotime(date('Y-m-d')));

        // 1. 近30天新增用户趋势（按天聚合）
        $userTrend = $this->getDailyTrend('user', 'jointime', $thirtyDaysAgo);

        // 2. 近30天Token消耗趋势（按天聚合 score < 0 的绝对值）
        $tokenTrend = $this->getDailyTrend('user_score_log', 'createtime', $thirtyDaysAgo, ['score' => ['<', 0]]);

        // 3. 近30天API请求数趋势
        $requestTrend = $this->getDailyTrend('user_score_log', 'createtime', $thirtyDaysAgo, ['score' => ['<', 0]]);

        // 4. 模型使用占比（按 model 字段分组统计请求数）
        $modelDistribution = $this->getModelDistribution();

        // 5. 代理状态占比
        $agentStatus = [
            'normal' => Db::name('agent')->where('status', 'normal')->count(),
            'hidden' => Db::name('agent')->where('status', 'hidden')->count(),
        ];

        // 近7天数据（保留旧变量兼容）
        $sevenDaysAgo = \fast\Date::unixtime('day', -7);
        $paylist = [];
        $createlist = [];
        for ($i = 0; $i < 7; $i++) {
            $day = date("Y-m-d", $sevenDaysAgo + ($i * 86400));
            $dayStart = strtotime($day);
            $dayEnd = $dayStart + 86400 - 1;

            $createlist[$day] = $this->safeCountByTime('user', 'jointime', $dayStart, $dayEnd);
            $paylist[$day] = abs($this->safeSumByTime('user_score_log', 'score', ['score' => ['<', 0]], 'createtime', $dayStart, $dayEnd));
        }

        $this->view->assign([
            // 概览数字
            'totaluser'        => Db::name('user')->count(),
            'totalviews'       => Db::name('user')->sum('successions'),
            'totalorder'       => Db::name('user_score_log')->where('score', '<', 0)->count(),
            'totalorderamount' => abs(Db::name('user_score_log')->where('score', '<', 0)->sum('score')),
            'todayuserlogin'   => Db::name('user')->whereTime('logintime', 'today')->count(),
            'todayusersignup'  => Db::name('user')->whereTime('jointime', 'today')->count(),
            'todayorder'       => Db::name('user_score_log')->where('score', '<', 0)->whereTime('createtime', 'today')->count(),
            'unsettleorder'    => abs(Db::name('user_score_log')->where('score', '<', 0)->whereTime('createtime', 'today')->sum('score')),
            'sevendnu'         => Db::name('user')->whereTime('jointime', 'week')->count(),
            'sevendau'         => Db::name('user')->whereTime('logintime', 'week')->count(),
            'activeusers'      => Db::name('user')->count(),
            'article_count'    => Db::name('agent')->count(),
            'totalkeys'        => Db::name('agent')->where('api_key', '<>', '')->count(),
            'totaltoken'       => abs(Db::name('user_score_log')->where('score', '<', 0)->sum('score')),

            // 兼容旧变量
            'paylist'          => $paylist,
            'createlist'       => $createlist,

            // 图表数据
            'chart_dates'      => json_encode(array_keys($userTrend)),
            'chart_user_trend'  => json_encode(array_values($userTrend)),
            'chart_token_trend' => json_encode(array_values($tokenTrend)),
            'chart_request_trend' => json_encode(array_values($requestTrend)),
            'chart_models'     => json_encode($modelDistribution['names']),
            'chart_model_counts' => json_encode($modelDistribution['counts']),
            'agent_normal'     => $agentStatus['normal'],
            'agent_hidden'     => $agentStatus['hidden'],
            'admin'            => $this->auth->getUserInfo(),
        ]);

        return $this->view->fetch();
    }

    /**
     * 按天聚合统计趋势
     */
    private function getDailyTrend($table, $timeField, $startTime, $where = [])
    {
        $result = [];
        $today = strtotime(date('Y-m-d'));

        for ($day = $startTime; $day <= $today; $day += 86400) {
            $dateStr = date('m-d', $day);
            $dayEnd = $day + 86400 - 1;

            try {
                $query = Db::name($table)
                    ->where($timeField, 'between', [$day, $dayEnd]);
                if ($where) {
                    $query = $query->where($where);
                }

                if ($table == 'user_score_log' && $timeField == 'createtime') {
                    // Token 消费返回 score 绝对值
                    $val = abs($query->sum('score'));
                } else {
                    $val = $query->count();
                }
            } catch (\Exception $e) {
                $val = 0;
            }

            $result[$dateStr] = intval($val);
        }

        return $result;
    }

    /**
     * 获取模型使用占比
     */
    private function getModelDistribution()
    {
        $names = [];
        $counts = [];

        try {
            $rows = Db::name('user_score_log')
                ->field('model, COUNT(*) as cnt')
                ->where('score', '<', 0)
                ->group('model')
                ->order('cnt', 'desc')
                ->limit(10)
                ->select();

            foreach ($rows as $row) {
                $names[] = $row['model'] ?: '未知模型';
                $counts[] = intval($row['cnt']);
            }
        } catch (\Exception $e) {
            // ignore
        }

        return ['names' => $names, 'counts' => $counts];
    }

    /**
     * 安全按时间范围统计数量
     */
    private function safeCountByTime($table, $timeField, $startTime, $endTime)
    {
        try {
            return Db::name($table)
                ->where($timeField, 'between', [$startTime, $endTime])
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * 安全按时间范围求和
     */
    private function safeSumByTime($table, $field, $where, $timeField, $startTime, $endTime)
    {
        try {
            return Db::name($table)
                ->where($where)
                ->where($timeField, 'between', [$startTime, $endTime])
                ->sum($field);
        } catch (\Exception $e) {
            return 0;
        }
    }
}
