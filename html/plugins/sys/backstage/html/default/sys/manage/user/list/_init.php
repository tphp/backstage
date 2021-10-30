<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

return function (){
    if ($this->isPost()) {
        $username = $_POST['username'];
        if (isset($username) && empty(trim($username))) {
            EXITJSON(0, '用户名不能为空');
        }
        if ($this->tplType === 'add') {
            $password = $_POST['password'];
            if (empty($password)) {
                EXITJSON(0, '密码不能为空');
            }
        }
    }
};