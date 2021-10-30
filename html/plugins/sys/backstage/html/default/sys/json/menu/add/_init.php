<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

return function(){
    $ret = [];

    // 系统设置
    $ret[] = [
        "id" => -1,
        "parent_id" => 0,
        "name" => "系统设置",
        "icon" => "cog",
        "url" => "/sys/manage"
    ];

    if (\Tphp\Config::$domain['folder']) {
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
};