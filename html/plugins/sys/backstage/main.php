<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

use Tphp\Basic\Tpl\Init as TplInit;

class MainController extends InitController
{
    private static $isBackstage;
    private static $thisPlu;

    function __construct($tpl, $type)
    {
        if (method_exists(parent::class, '__construct')) {
            parent::__construct($tpl, $type);
        }
        if (self::isBackstage()) {
            $this->pluMain()->call('backstage.init', $this, $tpl, $type);
        }
    }

    /**
     * 判断是否是后台
     * @return bool
     */
    protected static function isBackstage()
    {
        if (isset(self::$isBackstage)) {
            return self::$isBackstage;
        }

        $config = \Tphp\Config::$domain;
        self::$isBackstage = false;
        if (isset($config['backstage']) && $config['backstage'] === true) {
            self::$isBackstage = true;
        }

        return self::$isBackstage;
    }

    /**
     * 系统初始化调用模块 （优先级： 1）
     * @param $tplPath
     */
    public static function __initConfig($tplPath)
    {
        if (method_exists(parent::class, '__initConfig')) {
            parent::__initConfig($tplPath);
        }
        TplInit::getPluMain()->call('backstage.init:config', $tplPath, self::isBackstage());
    }

    /**
     * 请求设置（优先级： 2）
     */
    public function __method()
    {
        if (method_exists(parent::class, '__method')) {
            parent::__method();
        }
        if (!self::isBackstage()) {
            return;
        }
        $this->pluMain()->call('backstage.method:create', $this);
    }

    /**
     * data.php 文件信息基本设置（优先级： 3）
     * @param $data
     * @param $type
     */
    public static function __data(&$data, $type)
    {
        if (method_exists(parent::class, '__data')) {
            parent::__data($data, $type);
        }

        if (!self::isBackstage()) {
            return;
        }

        $arrowTypes = [
            'list', // 列表
            'bind', // 绑定列表
            'isbind', // 已绑定
            'unbind', // 未绑定
            'extends', // 扩展
            'add', // 新增
            'edit', // 编辑
            'view', // 查看
            'handle' // 单页编辑
        ];

        if (!isset($data['layout']) && in_array($type, $arrowTypes)) {
            $data['layout'] = false;
        }
    }

    /**
     * 清空缓存
     */
    public function flushCache()
    {
        // 自定义缓存处理
        $cache = Tphp\Config::$domain['cache'];
        if (is_function($cache)) {
            $cache($this);
        }

        $xFile = import('XFile');

        // 删除插件软连接缓存
        $staticPath = public_path('static/plugins');
        $staticDirs = $xFile->getDirs($staticPath);
        foreach ($staticDirs as $staticDir) {
            $xFile->deleteDir("{$staticPath}/{$staticDir}");
        }

        // 删除文件缓存
        $cachePath = storage_path('framework/cache');
        $cacheDirs = $xFile->getDirs($cachePath);
        foreach ($cacheDirs as $cacheDir) {
            $xFile->deleteDir("{$cachePath}/{$cacheDir}");
        }

        // 删除视图缓存
        $viewPath = storage_path('framework/views');
        $viewDirs = $xFile->getFiles($viewPath);
        foreach ($viewDirs as $viewDir) {
            if ($viewDir == '.gitignore') {
                continue;
            }
            $xFile->delete("{$viewPath}/{$viewDir}");
        }
        return Cache::flush();
    }
}
