<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

return function(){
    $cc = import("CaptchaExt", [
        'imageH'   => 36,
        'imageW'   => 115,
        'length'   => 4,
    ]);
    $cc->entry($this->getCacheId());
    return false;
};