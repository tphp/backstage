<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

use Tphp\Config as TphpConfig;

$tableUser = import("SystemInit", TphpConfig::$tpl ?? $this)->tableUser();
$isDefault = $tableUser->isDefault();
if ($isDefault) {
    $pk = $_GET['pk'];
    $id = "";
    $conn = TphpConfig::$domain['user']['conn'];
    empty($conn) && $conn = $this->config['config']['conn'];
    empty($conn) && $conn = TphpConfig::$domain['conn'];
    if(!empty($pk)){
        $idInfo = json_decode($pk, true)[0];
        if(!empty($idInfo) && is_string($idInfo)){
            $id = json_decode($idInfo, true)['id'];
        }
    }
    if (empty($id)) {
        $userInfo = $tableUser->db()->orderBy($tableUser->getId(false), 'desc')->first();
        if (empty($userInfo)) {
            $id = 0;
        } else {
            $id = $userInfo->id;
        }
    }
    $bPath = $conn . "/" . $id;
}

$tableRole = import("SystemInit")->tableRole();
$tableRoleUser = import("SystemInit")->tableRoleUser();
//dump($tableRoleUser);

$tId = $tableUser->getId(false);
$tUsername = $tableUser->getUsername(false);
$tNickname = $tableUser->getNickname(false);
$field = [
    $tId => [
        'name' => 'ID',
        'width' => 100,
        "order"=>true,
        'fixed' => true
    ],
    $tUsername => [
        'name' => '用户名',
        "order"=>true,
        'edit' => true,
        'title' => true,
        'search' => true
    ],
    $tNickname => [
        'name' => '昵称',
        'edit' => true,
        'search' => true,
        "order"=>true,
    ],
    "role_bind" => [
        'name' => '角色',
        'search' => true,
        'type' => 'selects',
        'from' => [
            [$tableRole->table, $tableRole->conn],
            'id',
            'name',
            [
                [$tableRoleUser->table, $tableRoleUser->conn],
                [$tId, 'user_id'],
                ['id', 'role_id'],
            ]
        ],
        'order' => true
    ],
    'status' => [
        'name' => '状态',
        'status' => true,
        'width' => 45,
        'fixed' => true,
        'search' => true
    ],
];

if (!$isDefault) {
    unset($field['status']);
    foreach ($field as $key => $val) {
        unset($field[$key]['edit']);
    }

    return [
        'field' => $field,
        //编辑或增加
        'handle' => [
            $tUsername => [
                'view' => true
            ],
            'role_bind',
        ],
        'handleinfo' => [
            'width' => 800, //宽
            'height' => 500, //高
            'fixed' => true
        ],
        'is' => [
            'numbers' => true, //序列
        ],
    ];
}

if ($this->tplType === 'add') {
    $passwordVerify = 'required';
} else {
    $passwordVerify = '';
}

return [
    'field' => $field,
    //编辑或增加
    'handle' => [
        $tUsername => [
            'key' => true,
            'verify' => 'required'
        ],
        'role_bind',
        $tNickname,
        'password' => [
            'name' => '密码',
            'type' => 'password',
            'md5' => true,
            'salt' => 'salt',
            'verify' => $passwordVerify
        ],
        'status' => [
            'value' => '1',
            'batch' => "修改状态"
        ],
        'sex' => [
            'type' => 'radio',
            'list' => [
                '0' => '保密',
                '1' => '男',
                '2' => '女'
            ],
            'top' => false,
            'group' => '详情'
        ],
        'image' => [
            'name' => '个人头像',
            'type' => 'image',
            'thumbs' => [
                [200, 200],
                [100, 100]
            ],
            'path' => 'sys/user/userinfo/'.$bPath,
            'filename' => 'pic',
            'group' => '详情'
        ],
        'remark' => [
            'type' => 'textarea',
            'rows' => 14,
            'group' => '简介'
        ],
        'create_time',
        'update_time'
    ],
    'handleinfo' => [
        'width' => 800, //宽
        'height' => 500, //高
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
