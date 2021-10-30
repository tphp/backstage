<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

$tableRole = import("SystemInit", $this)->tableRole();
return [
	'type' => 'sql',
	'config' => [
		'table' => $tableRole->table,
        'conn' => $tableRole->conn,
		'order' =>[
		    'sort' => 'asc',
            'id' => 'desc',
		]
	],
    'layout' => false
];