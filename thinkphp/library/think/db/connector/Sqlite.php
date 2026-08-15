<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

namespace think\db\connector;

use PDO;
use think\db\Connection;

/**
 * Sqlite数据库驱动
 */
class Sqlite extends Connection
{

    /**
     * 解析pdo连接的dsn信息
     * @access protected
     * @param array $config 连接信息
     * @return string
     */
    protected function parseDsn($config)
    {
        $dsn = 'sqlite:' . $config['database'];
        return $dsn;
    }

    /**
     * 连接数据库（重写：附加 SQLite 专用 PRAGMA）
     * WAL 模式下读不阻塞写、写不阻塞读，避免同进程多连接时
     * （如 db() 助手新建连接 + Session 写库）出现锁死等待
     */
    public function connect(array $config = [], $linkNum = 0, $autoConnection = false)
    {
        $isNew = !isset($this->links[$linkNum]);
        $pdo   = parent::connect($config, $linkNum, $autoConnection);
        if ($isNew && $pdo) {
            try {
                $pdo->exec('PRAGMA journal_mode = WAL');
                // 写锁冲突时最多等 5 秒（默认 60 秒会让单线程服务器长时间卡死）
                $pdo->exec('PRAGMA busy_timeout = 5000');
            } catch (\PDOException $e) {
                // 只读库或旧环境不支持时忽略，不影响常规查询
            }
        }
        return $pdo;
    }

    /**
     * 取得数据表的字段信息
     * @access public
     * @param string $tableName
     * @return array
     */
    public function getFields($tableName)
    {
        $this->initConnect(true);
        list($tableName) = explode(' ', $tableName);
        $sql             = 'PRAGMA table_info( ' . $tableName . ' )';
        // 调试开始
        $this->debug(true);
        $pdo = $this->linkID->query($sql);
        // 调试结束
        $this->debug(false, $sql);
        $result = $pdo->fetchAll(PDO::FETCH_ASSOC);
        $info   = [];
        if ($result) {
            foreach ($result as $key => $val) {
                $val                = array_change_key_case($val);
                $info[$val['name']] = [
                    'name'    => $val['name'],
                    'type'    => $val['type'],
                    'notnull' => 1 === $val['notnull'],
                    'default' => $val['dflt_value'],
                    'primary' => '1' == $val['pk'],
                    'autoinc' => '1' == $val['pk'],
                ];
            }
        }
        return $this->fieldCase($info);
    }

    /**
     * 取得数据库的表信息
     * @access public
     * @param string $dbName
     * @return array
     */
    public function getTables($dbName = '')
    {
        $sql = "SELECT name FROM sqlite_master WHERE type='table' "
            . "UNION ALL SELECT name FROM sqlite_temp_master "
            . "WHERE type='table' ORDER BY name";
        // 调试开始
        $this->debug(true);
        $pdo = $this->linkID->query($sql);
        // 调试结束
        $this->debug(false, $sql);
        $result = $pdo->fetchAll(PDO::FETCH_ASSOC);
        $info   = [];
        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }
        return $info;
    }

    /**
     * SQL性能分析
     * @access protected
     * @param string $sql
     * @return array
     */
    protected function getExplain($sql)
    {
        return [];
    }

    protected function supportSavepoint()
    {
        return true;
    }
}
