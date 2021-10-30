<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

$info = import("SystemInit", $this)->tableUser();
return [
	'type' => 'sql',
	'config' => [
		'table' => $info->table,
        'conn' => $info->conn,
        'field' => [
            'create_time'
        ]
	]
];