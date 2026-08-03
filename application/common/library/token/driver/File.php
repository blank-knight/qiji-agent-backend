<?php

namespace app\common\library\token\driver;

/**
 * Token File 驱动
 * Token 存储为 ['user_id' => xxx, 'expiretime' => xxx] 格式
 */
class File
{
    protected $options = [
        'path'    => '',
        'expire'  => 0,
        'prefix'  => 'token',
    ];

    public function __construct($options = [])
    {
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }
        if (substr($this->options['path'], -1) != DIRECTORY_SEPARATOR) {
            $this->options['path'] .= DIRECTORY_SEPARATOR;
        }
        if (!is_dir($this->options['path'])) {
            @mkdir($this->options['path'], 0755, true);
        }
    }

    /**
     * 设置 Token
     * @param string $token   Token标识
     * @param int    $user_id 用户ID
     * @param int    $expire  过期时间（秒）
     * @return bool
     */
    public function set($token, $user_id, $expire = null)
    {
        $expire = !is_null($expire) ? $expire : $this->options['expire'];
        $expiretime = $expire > 0 ? time() + $expire : 0;
        $filename = $this->getFileName($token);
        $data = serialize(['user_id' => $user_id, 'expiretime' => $expiretime, 'createtime' => time()]);
        return file_put_contents($filename, $data) !== false;
    }

    /**
     * 获取 Token
     * @param string $token Token标识
     * @return array|null
     */
    public function get($token)
    {
        $filename = $this->getFileName($token);
        if (!file_exists($filename)) {
            return null;
        }
        $content = file_get_contents($filename);
        $data = @unserialize($content);
        if (!is_array($data)) {
            return null;
        }
        if ($data['expiretime'] > 0 && $data['expiretime'] < time()) {
            @unlink($filename);
            return null;
        }
        return $data;
    }

    /**
     * 检查 Token 是否存在
     */
    public function has($token)
    {
        return $this->get($token) !== null;
    }

    /**
     * 删除 Token
     */
    public function delete($token)
    {
        $filename = $this->getFileName($token);
        if (file_exists($filename)) {
            return @unlink($filename);
        }
        return true;
    }

    /**
     * 清理过期 Token
     */
    public function clear()
    {
        $files = glob($this->options['path'] . '*');
        foreach ($files as $file) {
            $content = @file_get_contents($file);
            $data = @unserialize($content);
            if (is_array($data) && $data['expiretime'] > 0 && $data['expiretime'] < time()) {
                @unlink($file);
            }
        }
    }

    /**
     * 获取存储文件名
     */
    protected function getFileName($token)
    {
        $token = md5($this->options['prefix'] . $token);
        return $this->options['path'] . $token . '.php';
    }
}
