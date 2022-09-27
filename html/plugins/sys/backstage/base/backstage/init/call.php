<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

use Tphp\Basic\Sql\Init as SqlInit;
use Tphp\Register;
use Tphp\Config as TphpConfig;

return new class
{
    public static $plu;

    /**
     * 后台模块
     * @param $obj
     * @param $tpl
     * @param $type
     */
    public static function index($obj, $tpl, $type)
    {
        $obj->tpl = $tpl;

        $domainConfig = TphpConfig::$domain;
        $dcUser = $domainConfig['user']['conn'];

        $dc = config('database.connections');
        $errMsg = "用户数据源未配置：文件config/database.php配置connections.{$dcUser}";
        $dcInfo = $dc[$dcUser];
        if (!isset($dcInfo)) {
            __exit($errMsg);
        }
        
        $tableUser = import("SystemInit", $obj)->tableUser();

        $btpt = Register::$topPath;
        $btpt = trim(trim($btpt), "/");
        $btpt = str_replace("/", "_", $btpt);
        $userInfoId = $btpt . "_sys_user_login_userinfo";
        $userInfo = Session::get($userInfoId);
        if (empty($userInfo)) {
            /*
             * TphpConfig::$domain 配置说明
             *
             * 'backstage', // 是否是后台
             * 'backstagearrow', // 允许直接访问URL，无需登录
             * 'color', // 后台主题颜色 ['088', '0aa']
             * 'user', // 用户登录数据库，默认为user数据库标识
             */
            $backstageArrow = TphpConfig::$domain['backstagearrow'];
            if (empty($backstageArrow) || !is_array($backstageArrow)) {
                $backstageArrow = [];
            }
            $backstageArrow[] = 'sys/user/login';
            $backstageArrow[] = 'sys/user/login/init';
            $backstageArrow[] = 'sys/user/login/captcha';
            if (!in_array($tpl, $backstageArrow)) {
                if (count($_POST) > 0) {
                    EXITJSON(0, "登录超时， 请重新登录！");
                } else {
                    redirect("/sys/user/login")->send();
                }
            }
        }

        $obj->userInfo = $userInfo;
        \Tphp\BackStage\UserInfo::$data = $userInfo;

        if ($tableUser->getAdmin()[$userInfo['username']]) {
            return;
        }

        $url = TphpConfig::$domain['url'];
        if (empty($url)) {
            return;
        }

        $menuIda = $userInfo['menuIda'];
        $md5 = substr(md5($url), 8, 8);

        $isError = false;
        if (!empty($menuIda)) {
            if (isset($menuIda[$md5])) {
                $isError = true;
            } else {
                $pos = strrpos($url, ".");
                if ($pos > 0) {
                    $url = substr($url, 0, $pos);
                    $md5 = substr(md5($url), 8, 8);
                    if (isset($menuIda[$md5])) {
                        $isError = true;
                    }
                }
            }
        }

        if ($isError) {
            if (count($_POST) > 0) {
                $id = $menuIda[$md5]['id'];
                $name = $menuIda[$md5]['name'];
                $url = $menuIda[$md5]['url'];
                EXITJSON(0, "无权限操作！<BR><BR>菜单ID： {$id}<BR>菜单名称： {$name}<BR>链接： {$url}");
            } else {
                __exit(self::$plu->view("public/err", [
                    'info' => $menuIda[$md5]
                ]));
            }
        }
    }

    /**
     * 系统初始化调用模块
     * @param $tplPath
     * @param $isBackstage
     */
    public static function config($tplPath, bool $isBackstage)
    {
        $tplBase = Register::getHtmlPath(true) . "/";
        $topPath = Register::getTopPath("/");

        $domainPath = TphpConfig::$domainPath;

        if (empty($tplPath)) {
            //内部初始化文件
            if ($isBackstage) {
                $tplPath = "sys/index";
            } else {
                $tplPath = "index";
            }
        } elseif (!$isBackstage) {
            $tparr = explode("/", $tplPath);
            if (count($tparr) > 0 && trim(strtolower($tparr[0])) == 'sys') {
                EXITJSON(0, "无权访问 sys 目录!");
            }
        }

        $dc = &TphpConfig::$domain;
        if (is_string($dc['user'])) {
            $dc['user'] = [
                'conn' => $dc['user']
            ];
        } elseif (is_array($dc['user'])) {
            if (!isset($dc['user']['conn'])) {
                $dc['user']['conn'] = $dc['conn'];
            }
        } else {
            $dc['user'] = [
                'conn' => $dc['conn']
            ];
        }
        
        if (empty($dc['user']['table']) || !is_string($dc['user']['table'])) {
            $dc['user']['table'] = 'admin';
        }

        if (isset($dc['user']) && !empty($dc['user'])) {
            $user = $dc['user']['conn'];
            if (is_string($user)) {
                $userInfo = config("database.connections.{$user}");
                if (!empty($userInfo) && is_array($userInfo)) {
                    $cUser = config("database.connections.user");
                    foreach ($userInfo as $key => $val) {
                        $cUser[$key] = $val;
                    }
                    config(["database.connections.user" => $cUser]);
                }
            }
        }

        $tplInPath = $tplBase . $topPath;
        if (is_null(Register::$mainWebPath)) {
            $mainWebPath = "";
        } else {
            $mainWebPath = "/" . Register::$mainWebPath;
        }
        $outPath = self::$plu->getBasePath("html{$mainWebPath}") . "/";
        $isMainPath = false;

        $tplType = TphpConfig::$domainPath->tplType;

        if (in_array($tplType, ['#', 'htm', 'html'])) {
            $tplType = 'tpl';
        }

        $dirPath = $tplInPath . $tplPath . "/";

        $isReadable = false;
        // 判断存在以下一种文件则表示路径有效
        $fileList = [
            "data.php",
            "_init.php",
            "src.php",
            "ini.php",
            "set.php",
            "method.php",
            "{$tplType}.blade.php"
        ];

        foreach ($fileList as $fl) {
            if (is_readable($dirPath . $fl)) {
                $isReadable = true;
                break;
            }
        }

        if (!$isReadable) {
            $topPath = $outPath;
            $isMainPath = true;
        }

        Register::$tplPath = $topPath;
        $domainPath->baseTplPath = $topPath;
        $domainPath->tplPath = $tplPath;
        $domainPath->isMainPath = $isMainPath;

        // user 数据库设置
        $sqlInit = SqlInit::__init();
        if (!empty($dc['user']) && !is_string($dc['user']['conn'])) {
            $dc['user']['conn'] = $sqlInit->getConnectionName($dc['user']['conn']);
        }
        if ($dc['user']['conn'] == '#') {
            $dc['user']['conn'] = $dc['conn'];
        }
    }
};
