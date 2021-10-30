<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

if(count($_POST) > 0){
    return [
        'layout' => false,
        'tplDelete' => true
    ];
}else {
    return [
        'layout' => ':public/layer',
        'tplDelete' => true
    ];
}