<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

$btpt = \Tphp\Register::$topPath;
$btpt = trim(trim($btpt), "/");
$btpt = str_replace("/", "_", $btpt);
return [
    //编辑或增加
    'handle' => [
        'old_password' => ['name' => '旧密码', 'type' => 'password'],
        'password' => ['name' => '密码', 'type' => 'password', 'md5' => true, 'salt' => 'salt'],
        'create_time',
        'update_time'
    ],
    'handleInit' => [
        'id' => '_%'.$btpt.'_sys_user_login_userinfo.id%_'
    ]
];