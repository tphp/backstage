<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

$info = import("SystemInit", $this)->tableMenu();
return [
	'type' => 'sqlfind',
	'config' => [
        'table' => $info->table,
        'conn' => $info->conn,
        'field' => [
            'id',
            'name',
            'url',
        ],
        'where' => ['id', '=', $_GET['id']]
	],
    'layout' => ':public/handle'
];