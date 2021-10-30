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
$conn = \Tphp\Config::$domain['user']['conn'];
empty($conn) && $conn = $this->config['config']['conn'];
empty($conn) && $conn = \Tphp\Config::$domain['conn'];
return [
    //编辑或增加
    'handle' => [
        'username' => [
            'type' => 'label'
        ],
        'nickname',
        'sex' => [
            'type' => 'radio',
            'list' => [
                '0' => '保密',
                '1' => '男',
                '2' => '女'
            ],
            'top' => false
        ],
        'image' => ['name' => '个人头像', 'type' => 'image', 'thumbs' => [
                [200, 200],
                'image_big' => [400, 400]
            ],
            'path' => 'sys/user/userinfo/'.$conn.'/_%'.$btpt.'_sys_user_login_userinfo.id%_', 'filename' => 'pic'
        ],
        'remark' => [
            'type' => 'textarea'
        ],
        'create_time',
        'update_time'
    ],
    'handleInit' => [
        'id' => '_%'.$btpt.'_sys_user_login_userinfo.id%_'
    ]
];