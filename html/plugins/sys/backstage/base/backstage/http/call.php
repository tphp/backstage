<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

use Tphp\Basic\Http;

return new class
{
    public static $plu;
    /**
     * 相关接口数据
     * @param $config
     * @param array $cPage
     * @param array $field
     * @param null $obj
     * @return array
     */
    public static function run($type, $config, $apiObj = null)
    {
        $field = $apiObj->allField;
        $obj = $apiObj->tplInit;
        $cPage = $obj->page;
        if ($type == 'api') {
            $cmd = "backstage.http.api:run";
            return self::$plu->call($cmd, $config, $cPage, $field, $obj);
        }

        if ($type == 'dir') {
            $cmd = "backstage.http.dir:run";
            return self::$plu->call($cmd, $config, $apiObj);
        }
    }
};