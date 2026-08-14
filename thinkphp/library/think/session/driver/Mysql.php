<?php
// +----------------------------------------------------------------------
// | MySQL Session 驱动
// | 用于多机部署时共享登录态（写入数据库而非本地文件）
// | 配置：config.php 中 session.type = 'mysql'
// +----------------------------------------------------------------------

namespace think\session\driver;

class Mysql implements \SessionHandlerInterface
{
    /**
     * session表名（含前缀）
     */
    protected $table = 'fa_session';

    /**
     * session有效期（秒）
     */
    protected $expire = 86400;

    public function __construct(array $config = [])
    {
        if (isset($config['expire']) && $config['expire'] > 0) {
            $this->expire = intval($config['expire']);
        }
        if (!empty($config['table'])) {
            $this->table = $config['table'];
        }
    }

    /**
     * 打开Session（自动建表，幂等，兼容 MySQL/SQLite）
     */
    #[\ReturnTypeWillChange]
    public function open($savePath, $sessName)
    {
        try {
            \think\Db::query("SELECT id FROM {$this->table} LIMIT 1");
        } catch (\Exception $e) {
            $prefix = config('database.prefix') ?: 'fa_';
            // 通用建表语句（不带 COMMENT/ENGINE，MySQL 和 SQLite 均可执行）
            \think\Db::execute("
                CREATE TABLE IF NOT EXISTS `{$prefix}session` (
                    `id` CHAR(32) NOT NULL,
                    `expire` INTEGER NOT NULL DEFAULT 0,
                    `data` BLOB,
                    PRIMARY KEY (`id`)
                )
            ");
            \think\Db::execute("CREATE INDEX IF NOT EXISTS idx_expire ON `{$prefix}session` (`expire`)");
        }
        return true;
    }

    /**
     * 关闭Session
     */
    #[\ReturnTypeWillChange]
    public function close()
    {
        return true;
    }

    /**
     * 读取Session
     */
    #[\ReturnTypeWillChange]
    public function read($sessID)
    {
        try {
            $result = \think\Db::query(
                "SELECT data FROM {$this->table} WHERE id = ? AND expire > ?",
                [$sessID, time()]
            );
            return $result ? $result[0]['data'] : '';
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * 写入Session
     */
    #[\ReturnTypeWillChange]
    public function write($sessID, $sessData)
    {
        try {
            if ('' === $sessData) {
                // 空数据直接删记录，节省空间
                \think\Db::execute("DELETE FROM {$this->table} WHERE id = ?", [$sessID]);
                return true;
            }
            $expire = time() + $this->expire;
            \think\Db::execute(
                "REPLACE INTO {$this->table} (id, expire, data) VALUES (?, ?, ?)",
                [$sessID, $expire, $sessData]
            );
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 删除Session（退出登录时调用）
     */
    #[\ReturnTypeWillChange]
    public function destroy($sessID)
    {
        try {
            \think\Db::execute("DELETE FROM {$this->table} WHERE id = ?", [$sessID]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 垃圾回收（按概率触发）
     */
    #[\ReturnTypeWillChange]
    public function gc($sessMaxLifeTime)
    {
        try {
            \think\Db::execute("DELETE FROM {$this->table} WHERE expire < ?", [time()]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
