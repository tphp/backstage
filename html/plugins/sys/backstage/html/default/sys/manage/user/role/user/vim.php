<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

$tableUser = import("SystemInit", \Tphp\Config::$tpl ?? $this)->tableUser();
$isDefault = $tableUser->isDefault();

$field = [
    $tableUser->getId(false) => [
        'name' => 'ID',
        'width' => 100,
        'fixed' => true
    ],
    $tableUser->getUsername(false) => [
        'name' => '用户名',
        "order"=>true,
        'title' => true,
        'search' => true
    ],
    $tableUser->getNickname(false) => [
        'name' => '昵称',
        'edit' => true,
        'search' => true,
        "order"=>true,
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
}

return [
    'field' => $field,
];