<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

return function(){
    $systemInit = import("SystemInit", $this);
    $userInfo = $systemInit->getUserInfo();
    $isPost = $this->isPost();
    if(!empty($userInfo) && !$isPost) redirect("/")->send();

    $user = \Tphp\Config::$domain['user'];
    if (is_array($user) && $user['default'] === false) {
        redirect("/sys/user/login")->send();
    }

    if ($isPost) {
        $password = $_POST['password'];
        $passwordConfirm = $_POST['password_confirm'];
        if (empty($password)) {
            EXITJSON(0, '密码不能为空');
        }
        if ($password !== $passwordConfirm) {
            EXITJSON(0, '两次输入密码不一致');
        }
        import("SystemInit", $this)->tableUser($password);
        EXITJSON(1, '设置成功');
    }
};
