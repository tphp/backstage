<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

return function () {
    if ($this->flushCache()) {
        EXITJSON(1, "清除成功");
    } else {
        EXITJSON(1, "清除成功");
    }
};