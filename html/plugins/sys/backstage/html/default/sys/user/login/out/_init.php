<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

return function(){
    $userInfoId = $this->getCacheId("../userinfo");
    Session::forget($userInfoId);
    redirect("/sys/user/login")->send();
};