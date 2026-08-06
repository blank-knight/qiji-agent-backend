<?php

namespace app\common\behavior;

use think\Config;
use think\Lang;
use think\Loader;

/**
 * FastAdmin 通用行为类（从加密版重写）
 * 负责加载配置、语言、全局常量
 */
class Common
{
    public function app_init($params)
    {
        // 加载公共函数库
        if (is_file(APP_PATH . 'common.php')) {
            require_once APP_PATH . 'common.php';
        }
    }

    public function app_dispatch($params)
    {
    }

    public function module_init($params = null)
    {
        // 设置时区
        $timezone = Config::get('default_timezone');
        date_default_timezone_set($timezone ?: 'Asia/Shanghai');

        // 从数据库加载配置
        try {
            $configList = \think\Db::name('config')
                ->where('status', '<>', 'hidden')
                ->cache(true)
                ->column('value', 'name');

            foreach ($configList as $name => $value) {
                Config::set($name, $value);
            }
        } catch (\Exception $e) {
            // 数据库未就绪时忽略
        }

        // 加载语言包
        $this->loadLang();
    }

    public function addon_begin($params)
    {
    }

    /**
     * 加载语言包
     */
    protected function loadLang()
    {
        $langSet = strtolower(Lang::detect());

        // 加载应用公共语言包
        $appLangFile = APP_PATH . 'lang' . DS . $langSet . EXT;
        if (is_file($appLangFile)) {
            Lang::load($appLangFile);
        }

        // 加载模块语言包
        $module = request()->module();
        if ($module) {
            $moduleLangFile = APP_PATH . $module . DS . 'lang' . DS . $langSet . EXT;
            if (is_file($moduleLangFile)) {
                Lang::load($moduleLangFile);
            }
        }
    }
}
