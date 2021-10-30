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
	'type' => 'sql',
	'config' => [
		'table' => $info->table,
        'conn' => $info->conn,
        'field' => [
            'id', 'parent_id',
            'name',
            'icon_view',
            'icon' => '图标代码',
            'url' => '模块链接',
            'sort' => '排序',
            'status',
            'create_time',
            'update_time'
        ],
		'order' =>[
			'sort' => 'asc',
            'id' => 'desc',
		]
	],
    'layout' => false
];
