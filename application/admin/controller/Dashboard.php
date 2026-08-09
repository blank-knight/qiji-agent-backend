<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;

/**
 * 后台Dashboard仪表盘
 */
class Dashboard extends Backend
{
    protected $layout = '';

    /**
     * 查看
     */
    public function index()
    {
        $thirtyDaysAgo = strtotime('-30 days', strtotime(date('Y-m-d')));
        $isSuper = $this->isSuperAdmin();
        $currentAgent = $this->getCurrentAgent();

        // 一次性获取 scope，避免在循环中重复查询导致查询状态污染
        $agentScope = $this->getAgentScope();  // null=全部，array=指定ID
        $userScope = $this->getUserScope();    // null=全部，array=指定agent_id

        // 拼接 scope where 条件字符串（用于安全地构建查询）
        $userWhere = '';
        $userBind = [];
        if ($userScope !== null) {
            $userWhere = ' AND agent_id IN (' . implode(',', array_map('intval', $userScope)) . ')';
        }

        // 统计函数：每次创建全新 Db 查询，避免状态污染
        $countUsers = function($extra = '') use ($userWhere) {
            $sql = "SELECT COUNT(*) FROM fa_user WHERE 1=1" . $userWhere . $extra;
            try { return (int)Db::query($sql)[0]['COUNT(*)']; } catch (\Exception $e) { return 0; }
        };
        $sumScoreLog = function($field, $extra = '') use ($userScope) {
            $subWhere = '';
            $binds = [];
            if ($userScope !== null) {
                $ids = implode(',', array_map('intval', $userScope));
                $subWhere = " AND user_id IN (SELECT id FROM fa_user WHERE agent_id IN ($ids))";
            }
            $sql = "SELECT SUM($field) FROM fa_user_score_log WHERE 1=1" . $subWhere . $extra;
            try { return (float)Db::query($sql)[0]["SUM($field)"]; } catch (\Exception $e) { return 0; }
        };
        $countScoreLog = function($extra = '') use ($userScope) {
            $subWhere = '';
            if ($userScope !== null) {
                $ids = implode(',', array_map('intval', $userScope));
                $subWhere = " AND user_id IN (SELECT id FROM fa_user WHERE agent_id IN ($ids))";
            }
            $sql = "SELECT COUNT(*) FROM fa_user_score_log WHERE 1=1" . $subWhere . $extra;
            try { return (int)Db::query($sql)[0]['COUNT(*)']; } catch (\Exception $e) { return 0; }
        };

        // 趋势数据
        $userTrend = $this->getDailyTrend('fa_user', 'jointime', $thirtyDaysAgo, [], $userScope);
        $tokenTrend = $this->getDailyTrend('fa_user_score_log', 'createtime', $thirtyDaysAgo, ['score' => ['<', 0]], $userScope);
        $modelDistribution = $this->getModelDistribution($userScope);

        // 当前账号积分
        $myScore = 0;
        $myTotalUsers = 0;
        if ($currentAgent) {
            $myScore = $currentAgent['score'];
            $descIds = \app\admin\model\Agent::getDescendantIds($currentAgent['id']);
            $ids = implode(',', array_map('intval', $descIds));
            try {
                $myTotalUsers = (int)Db::query("SELECT COUNT(*) FROM fa_user WHERE agent_id IN ($ids)")[0]['COUNT(*)'];
            } catch (\Exception $e) {
                $myTotalUsers = 0;
            }
        }

        // 代理状态统计（排除自己）
        $agentNormal = 0;
        $agentHidden = 0;
        if ($agentScope === null) {
            $agentNormal = Db::name('agent')->where('status', 'normal')->count();
            $agentHidden = Db::name('agent')->where('status', 'hidden')->count();
        } else {
            // 排除当前账号自己的 agent id
            $excludeId = $currentAgent ? intval($currentAgent['id']) : 0;
            $agentIds = implode(',', array_map('intval', $agentScope));
            try {
                $agentNormal = (int)Db::query("SELECT COUNT(*) FROM fa_agent WHERE id IN ($agentIds) AND id != $excludeId AND status='normal'")[0]['COUNT(*)'];
                $agentHidden = (int)Db::query("SELECT COUNT(*) FROM fa_agent WHERE id IN ($agentIds) AND id != $excludeId AND status='hidden'")[0]['COUNT(*)'];
            } catch (\Exception $e) {}
        }

        // 今日、周数据
        $todayStart = strtotime(date('Y-m-d'));
        $todayEnd = $todayStart + 86400 - 1;
        $weekStart = strtotime('-6 days', $todayStart);

        // 近7天数据
        $sevenDaysAgo = \fast\Date::unixtime('day', -7);
        $paylist = [];
        $createlist = [];
        for ($i = 0; $i < 7; $i++) {
            $day = date("Y-m-d", $sevenDaysAgo + ($i * 86400));
            $dayStart = strtotime($day);
            $dayEnd = $dayStart + 86400 - 1;

            $createlist[$day] = $countUsers(" AND jointime BETWEEN $dayStart AND $dayEnd");
            $paylist[$day] = abs($sumScoreLog('score', " AND score < 0 AND createtime BETWEEN $dayStart AND $dayEnd"));
        }

        $this->view->assign([
            // 角色信息
            'isSuper'          => $isSuper,
            'currentAgent'     => $currentAgent,
            'myScore'          => $myScore,
            'myTotalUsers'     => $myTotalUsers,

            // 概览
            'totaluser'        => $countUsers(),
            'totalorder'       => $countScoreLog(" AND score < 0"),
            'totaltoken'       => abs($sumScoreLog('score', " AND score < 0")),
            'todayuserlogin'   => $countUsers(" AND logintime BETWEEN $todayStart AND $todayEnd"),
            'todayusersignup'  => $countUsers(" AND jointime BETWEEN $todayStart AND $todayEnd"),
            'todayorder'       => $countScoreLog(" AND score < 0 AND createtime BETWEEN $todayStart AND $todayEnd"),
            'unsettleorder'    => abs($sumScoreLog('score', " AND score < 0 AND createtime BETWEEN $todayStart AND $todayEnd")),
            'sevendnu'         => $countUsers(" AND jointime BETWEEN $weekStart AND $todayEnd"),
            'sevendau'         => $countUsers(" AND logintime BETWEEN $weekStart AND $todayEnd"),
            'article_count'    => $agentScope === null ? Db::name('agent')->count() : ($currentAgent ? (int)Db::query("SELECT COUNT(*) FROM fa_agent WHERE id IN (" . implode(',', array_map('intval', $agentScope)) . ") AND id != " . intval($currentAgent['id']))[0]['COUNT(*)'] : 0),
            'totalkeys'        => $agentScope === null ? Db::name('agent')->where('api_key', '<>', '')->count() : ($currentAgent ? (int)Db::query("SELECT COUNT(*) FROM fa_agent WHERE id IN (" . implode(',', array_map('intval', $agentScope)) . ") AND id != " . intval($currentAgent['id']) . " AND api_key <> ''")[0]['COUNT(*)'] : 0),
            'agent_normal'     => $agentNormal,
            'agent_hidden'     => $agentHidden,

            // 兼容
            'paylist'          => $paylist,
            'createlist'       => $createlist,
            'totalviews'       => 0,
            'totalorderamount' => 0,
            'activeusers'      => 0,

            // 图表
            'chart_dates'      => json_encode(array_keys($userTrend)),
            'chart_user_trend'  => json_encode(array_values($userTrend)),
            'chart_token_trend' => json_encode(array_values($tokenTrend)),
            'chart_request_trend' => json_encode(array_values($tokenTrend)),
            'chart_models'     => json_encode($modelDistribution['names']),
            'chart_model_counts' => json_encode($modelDistribution['counts']),
            'admin'            => $this->auth->getUserInfo(),
        ]);

        return $this->view->fetch();
    }

    /**
     * 按天聚合统计趋势（使用原生 SQL 避免 ThinkPHP 查询状态污染）
     */
    private function getDailyTrend($table, $timeField, $startTime, $where = [], $userScope = null)
    {
        $result = [];
        $today = strtotime(date('Y-m-d'));

        // 构建 scope 子句
        $scopeSql = '';
        if ($userScope !== null && $table == 'fa_user') {
            $ids = implode(',', array_map('intval', $userScope));
            $scopeSql = " AND agent_id IN ($ids)";
        } elseif ($userScope !== null && $table == 'fa_user_score_log') {
            $ids = implode(',', array_map('intval', $userScope));
            $scopeSql = " AND user_id IN (SELECT id FROM fa_user WHERE agent_id IN ($ids))";
        }

        // where 条件
        $extraSql = '';
        if (isset($where['score'])) {
            $extraSql = " AND score < 0";
        }

        for ($day = $startTime; $day <= $today; $day += 86400) {
            $dateStr = date('m-d', $day);
            $dayEnd = $day + 86400 - 1;

            try {
                if ($table == 'fa_user_score_log') {
                    $sql = "SELECT SUM(ABS(score)) FROM $table WHERE $timeField BETWEEN $day AND $dayEnd" . $extraSql . $scopeSql;
                    $val = (float)Db::query($sql)[0]["SUM(ABS(score))"];
                } else {
                    $sql = "SELECT COUNT(*) FROM $table WHERE $timeField BETWEEN $day AND $dayEnd" . $scopeSql;
                    $val = (int)Db::query($sql)[0]['COUNT(*)'];
                }
            } catch (\Exception $e) {
                $val = 0;
            }

            $result[$dateStr] = intval($val);
        }

        return $result;
    }

    /**
     * 模型使用占比
     */
    private function getModelDistribution($userScope = null)
    {
        $names = [];
        $counts = [];

        $scopeSql = '';
        if ($userScope !== null) {
            $ids = implode(',', array_map('intval', $userScope));
            $scopeSql = " AND user_id IN (SELECT id FROM fa_user WHERE agent_id IN ($ids))";
        }

        try {
            $sql = "SELECT model, COUNT(*) as cnt FROM fa_user_score_log WHERE score < 0" . $scopeSql . " GROUP BY model ORDER BY cnt DESC LIMIT 10";
            $rows = Db::query($sql);

            foreach ($rows as $row) {
                $names[] = $row['model'] ?: '未知模型';
                $counts[] = intval($row['cnt']);
            }
        } catch (\Exception $e) {}

        return ['names' => $names, 'counts' => $counts];
    }
}
