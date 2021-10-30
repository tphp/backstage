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
        $image = $_POST['image'];
        $btp = \Tphp\Register::$topPath;
        $userInfoId = $this->getCacheId("/{$btp}/sys/user/login/userinfo");
        $userInfo = Session::get($userInfoId);
        $userInfo['image'] = $image;
        Session::forget($userInfoId);
        Session::put($userInfoId, $userInfo, 24 * 60 * 60);
    }
};