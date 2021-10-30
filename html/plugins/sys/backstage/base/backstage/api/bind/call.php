<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

use Tphp\Basic\Tpl\Init as TplInit;

return new class
{
    public static $plu;
    public function config($apiObj, $vimConfig)
    {
        if ($apiObj->type == 'bind' || $apiObj->type == 'extends') {
            if ($apiObj->tplInit->isPost()) {
                return self::$plu->call('backstage.api.edit:config', $apiObj, $vimConfig);
            }
        }

        return self::$plu->call('backstage.api.list:config', $apiObj, $vimConfig);
    }

    public function html($apiObj, $data)
    {
        if ($apiObj->type == 'bind') {
            return self::$plu->call('backstage.api.edit:html', $apiObj, $data);
        } elseif ($apiObj->type == 'extends') {
            if ($apiObj->tplInit->isPost()) {
                return self::$plu->call('backstage.api.edit:html', $apiObj, $data);
            }
        }

        return self::$plu->call('backstage.api.list:html', $apiObj, $data);
    }
};