<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

use Tphp\Config as TphpConfig;

$endData = TphpConfig::$domainPath->plu->call('dir.menus:extMenus');
if (TphpConfig::$domain['folder']) {
    $menuList = TphpConfig::$domainPath->plu->call('dir.menus');
    foreach ($endData as $ed) {
        $menuList[] = $ed;
    }

    $trees = [
        "parent" => "parent_id",
        "child" => "id",
        "value" => 0,
        'name' => 'name',
        'where' => [
            ['status', '=', '-1000']
        ],
        'end' => $menuList
    ];
} else {
    $trees = [
        "parent" => "parent_id",
        "child" => "id",
        "value" => 0,
        "table" => import("SystemInit", $this)->tableMenu()->table,
        'name' => 'name',
        'sort' => ['sort', 'asc'],
        'where' => [
            ['status', '=', '1']
        ],
        'end' => $endData
    ];
}

$tableUser = import("SystemInit", TphpConfig::$tpl ?? $this)->tableUser();
$tableRoleUser = import("SystemInit", $this)->tableRoleUser();

$tId = $tableUser->getId(false);

return [
    'field' => [
        'id' => [
            'name' => 'ID',
            'width' => 20,
            'fixed' => true
        ],
        "name" => [
            'name' => '角色名称',
            "order"=>true,
            'edit' => true,
            'title' => true,
        ],
        'sort' => [
            'name' => '排序',
            'width' => 45,
            'fixed' => true,
            'edit' => true,
        ],
        'status' => [
            'name' => '状态',
            'status' => true,
            'width' => 45,
            'fixed' => true
        ],
    ],
    //编辑或增加
    'handle' => [
        'name' => [
            'verify' => 'required',
        ],
        'sort' => [
            'value' => 255
        ],
        'status' => [
            'value' => '1'
        ],
        'json' => [
            'name' => '授权',
            'trees' => $trees
        ],
        'bind_user' => [
            'name' => '用户绑定',
            'bind' => [
                'extends' => 'user',
                'table' => $tableRoleUser->table,
                'conn' => $tableRoleUser->conn,
                'this' => ['id', 'role_id'],
                'that' => [$tId, 'user_id'],
            ],
        ],
        'create_time',
        'update_time'
    ],
    'extends' => [
        'user' => 'sys.manage.user.role.user',
    ],
    'handleinfo' => [
        'width' => 500, //宽
        'height' => 300, //高
        'ismax' => true
    ],
//    //'ispage' => true,
//    //'pagesize' => 5,
    'is' => [
        'checkbox' => true, //选择框
        'numbers' => true, //序列
        'delete' => true, //删除
        'add' => true,
    ],
];