<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

return function(){
    $tableUser = import("SystemInit", $this)->tableUser();
    if (!$tableUser->isDefault()) {
        __exit('不允许操作！');
    }

    if($this->isPost()){
        $oldPassword = $_POST['old_password'];
        unset($_POST['old_password']);
        $newPassword = $_POST['password'];
        if(empty($oldPassword)){
            EXITJSON(0, "旧密码不能为空！");
        }
        if(empty($newPassword)){
            EXITJSON(0, "新密码不能为空！");
        }
        
        $btp = \Tphp\Register::$topPath;
        $userInfoId = $this->getCacheId("/{$btp}/sys/user/login/userinfo");
        $userInfo = Session::get($userInfoId);
        $userId = $userInfo['id'];
        $info = $tableUser->db()->where($tableUser->getId(false), "=", $userId)->first();
        if(empty($info)){
            EXITJSON(0, "用户数据错误");
        }
        $salt = $info->salt;
        $pwd = $info->password;
        $saltPasswrod = md5($oldPassword.$salt);
        if($saltPasswrod != $pwd){
            EXITJSON(0, "旧密码不正确！");
        }

        if(md5($newPassword.$salt) == $pwd){
            EXITJSON(0, "新密码不能和旧密码相同！");
        }
    }
};