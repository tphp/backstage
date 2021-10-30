<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

$tableUser = import("SystemInit", $this)->tableUser();
$id = $tableUser->getId(false);
$username = $tableUser->getUsername(false);
$field = $tableUser->config['field'];
$ret = [
    'type' => 'sql',
    'method' => 'list',
    'config' => [
        'table' => $tableUser->table,
        'conn' => $tableUser->conn,
        'field' => [
            $id,
            $username,
            'status',
        ],
        'where' => [
            [$username, '<>', 'admin']
        ]
    ],
    'layout' => false
];

if ($tableUser->isDefault()) {
    $ret['config']['order'] = [
        $id => 'desc'
    ];
}

if (is_array($field)) {
    foreach ($field as $key => $val) {
        if (empty($val)) {
            continue;
        }

        if (is_integer($key)) {
            $ret['config']['field'][] = $val;
        } else {
            $ret['config']['field'][$key] = $val;
        }
    }
}

return $ret;