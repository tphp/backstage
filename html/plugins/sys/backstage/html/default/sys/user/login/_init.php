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
    $rememberId = $this->getCacheId("remember");
    $usernameCacheId = $this->getCacheId("username");
    $loginCaptcha = getenv('LOGIN_CAPTCHA');
    is_string($loginCaptcha) && $loginCaptcha = strtolower(trim($loginCaptcha));
    if($loginCaptcha === true || $loginCaptcha === 'true'){
        $loginCaptcha = true;
    }else{
        $loginCaptcha = false;
    }
    $dbConfig = config("database.connections");
    $conn = trim(\Tphp\Config::$domain['conn']);
    if(empty($conn)){
        if($this->isPost()){
            EXITJSON(0, "数据库链接未设置");
        }
        __exit("数据库链接未设置");
    }elseif(!isset($dbConfig[$conn])){
        if($this->isPost()){
            EXITJSON(0, "无效数据源: {$conn}");
        }
        __exit("无效数据源: {$conn}");
    }

    if($isPost) {
        if($loginCaptcha) {
            $captcha = $_POST['captcha'];
            $cc = import("CaptchaExt", [
                'imageH' => 36,
                'imageW' => 115,
                'length' => 4,
            ]);
            $status = $cc->check($captcha, $this->getCacheId("captcha"));
            if (!$status) EXITJSON(0, "验证码不正确");
        }

        $username = $_POST['username'];
        $password = $_POST['password'];

        list($status, $info) = $systemInit->userLogin($username, true, $password);
        if (!$status) {
            EXITJSON(0, $info);
        }


        $remember = $_POST['remember'];
        if($remember == 'on') {
            $this->setCookie($rememberId, $remember);
            $this->setCookie($usernameCacheId, $username);
        }else{
            $this->forgetCookie($rememberId);
            $this->forgetCookie($usernameCacheId);
        }

        EXITJSON(1, "登录成功", "", "/");
    }

    $this->setView("loginCaptcha", $loginCaptcha);
    $this->setView("remember", $this->getCookie($rememberId));
    $this->setView("userName", $this->getCookie($usernameCacheId));
};
