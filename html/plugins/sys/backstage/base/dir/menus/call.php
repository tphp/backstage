<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

use Tphp\Config as TphpConfig;

return new class {

    function __construct()
    {
        $this->rootPath = \Tphp\Register::getHtmlPath(true) . "/" . TphpConfig::$domain['tpl'];
        $this->xFile = import('XFile');
    }

    /**
     * 系统默认目录
     * @return array
     */
    public function extMenus()
    {
        $ret = [];

        // 系统设置
        $ret[] = [
            "id" => -1,
            "parent_id" => 0,
            "name" => "系统设置",
            "icon" => "cog",
            "url" => "/sys/manage"
        ];

        if (TphpConfig::$domain['folder']) {
            //菜单图标
            $ret[] = [
                "id" => -2,
                "parent_id" => -1,
                "name" => "菜单图标",
                "icon" => "navicon",
                "url" => "/sys/manage/menu/icon?key=copy"
            ];
        } else {
            //菜单管理
            $ret[] = [
                "id" => -2,
                "parent_id" => -1,
                "name" => "菜单管理",
                "icon" => "navicon",
                "url" => "/sys/manage/menu.list"
            ];
        }

        //用户管理
        $ret[] = [
            "id" => -3,
            "parent_id" => -1,
            "name" => "用户管理",
            "icon" => "user",
            "url" => "/sys/manage/user"
        ];

        $ret[] = [
            "id" => -31,
            "parent_id" => -3,
            "name" => "角色管理",
            "icon" => "male",
            "url" => "/sys/manage/user/role.list"
        ];

        $ret[] = [
            "id" => -32,
            "parent_id" => -3,
            "name" => "用户列表",
            "icon" => "user-o",
            "url" => "/sys/manage/user/list.list"
        ];

        //配置函数
        $ret[] = [
            "id" => -4,
            "parent_id" => -1,
            "name" => "配置函数",
            "icon" => "code",
            "url" => "/sys/manage/menu/ini"
        ];

        //插件管理
        $ret[] = [
            "id" => -5,
            "parent_id" => -1,
            "name" => "插件管理",
            "icon" => "plug",
            "url" => "/sys/manage/plugins"
        ];

        //数据库
        $ret[] = [
            "id" => -6,
            "parent_id" => -1,
            "name" => "数据库同步",
            "icon" => "table",
            "url" => "/sys/manage/sql/diff"
        ];

        //GIT管理
        $ret[] = [
            "id" => -7,
            "parent_id" => -1,
            "name" => "GIT管理",
            "icon" => "git",
            "url" => "/sys/manage/git"
        ];

        return $ret;
    }

    /**
     * 获取文件夹
     * @param string $dir
     * @return array
     */
    private function getDirs($dir = '')
    {
        $path = $this->rootPath . "/" . $dir;
        $dirs = $this->xFile->getDirs($path);
        $infos = [];
        $rets = [];
        if (empty($dirs)) {
            return $rets;
        }
        
        foreach ($dirs as $dir) {
            $tPath = "{$path}/{$dir}/";
            if (is_file($tPath . "hide")) {
                continue;
            }

            $tMenu = "{$tPath}menu";
            $menuStr = trim($this->xFile->read($tMenu));
            if (empty($menuStr)) {
                continue;
            }
            
            $pos = strripos($menuStr, "@");
            $icon = '';
            if ($pos !== false) {
                $icon = trim(substr($menuStr, $pos + 1));
                $menuStr = trim(substr($menuStr, 0, $pos));
                if (empty($menuStr)) {
                    continue;
                }
            }
            
            if (empty($icon)) {
                $icon = 'xxx';
            }
            
            $sort = trim($this->xFile->read($tPath . "sort"));
            if ($sort == '0') {
                $sort = 0;
            } elseif (is_numeric($sort)) {
                $sort = intval($sort);
                if ($sort < 0) {
                    $sort = 0;
                }
            } else {
                $sort = 10000;
            }
            
            if (!isset($infos[$sort])) {
                $infos[$sort] = [];
            }

            $infos[$sort][$dir] = [
                'name' => $menuStr,
                'icon' => $icon,
                'sort' => $sort
            ];
        }

        ksort($infos);

        foreach ($infos as $info) {
            foreach ($info as $key => $val) {
                $rets[$key] = $val;
            }
        }

        return $rets;
    }

    /**
     * 目录ID唯一性
     * @param string $dir
     * @return float|int
     */
    private function getDirId($dir = '')
    {
        return hexdec(substr(md5($dir), 10, 6));
    }

    /**
     * 获取菜单列表
     * @return array
     */
    public function index()
    {
        $topDirs = $this->getDirs();
        $menus = [];
        foreach ($topDirs as $topDir => $topValue) {
            $topId = $this->getDirId($topDir);
            $topPath = "/{$topDir}";
            $topValue['id'] = $topId;
            $topValue['parent_id'] = 0;
            $topValue['url'] = $topPath;
            $menus[] = $topValue;
            $subDirs = $this->getDirs($topDir);
            if (empty($subDirs)) {
                continue;
            }

            foreach ($subDirs as $subDir => $subValue) {
                $subDir = "{$topDir}/{$subDir}";
                $subId = $this->getDirId($subDir);
                $subPath = "/{$subDir}";
                $subValue['id'] = $subId;
                $subValue['parent_id'] = $topId;
                $subValue['url'] = $subPath;
                $menus[] = $subValue;
                $minDirs = $this->getDirs($subDir);

                if (empty($minDirs)) {
                    continue;
                }

                foreach ($minDirs as $minDir => $minValue) {
                    $minDir = "{$subDir}/{$minDir}";
                    $minId = $this->getDirId($minDir);
                    $minPath = "/{$minDir}";
                    $minValue['id'] = $minId;
                    $minValue['parent_id'] = $subId;
                    $minValue['url'] = $minPath;
                    $menus[] = $minValue;
                }
            }
        }

        $folderMenus = TphpConfig::$domain['foldermenus'];
        if (!empty($folderMenus) && is_array($folderMenus)) {
            $fNames = [];
            foreach ($folderMenus as $dir => $name) {
                if (!is_string($dir) || empty($name) || !is_string($name)) {
                    continue;
                }

                $name = trim($name);
                if (empty($name)) {
                    continue;
                }

                $dir = str_replace("\\", "/", $dir);
                $dir = trim($dir, " /");
                if (empty($dir)) {
                    continue;
                }

                $icon = "";
                $pos = strrpos($name, "@");
                if ($pos !== false) {
                    $icon = trim(substr($name, $pos + 1));
                    $name = trim(substr($name, 0, $pos));
                }

                $info = [];

                !empty($name) && $info['name'] = $name;
                !empty($icon) && $info['icon'] = $icon;

                if (!empty($info)) {
                    $fNames["/" . $dir] = $info;
                }
            }

            foreach ($menus as $key => $val) {
                $fInfo = $fNames[$val['url']];
                if (empty($fInfo)) {
                    continue;
                }

                foreach ($fInfo as $k => $v) {
                    $menus[$key][$k] = $v;
                }
            }
        }
        return $menus;
    }
};