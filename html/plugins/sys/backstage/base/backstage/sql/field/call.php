<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

use \Tphp\Basic\Sql\SqlCache;

return new class
{
    public function reset($apiObj, $config = null, $conn = null, $table = null)
    {

        $vConfig = [];
        if (!empty($apiObj->vConfig)) {
            $vConfig[] = &$apiObj->vConfig;
        }

        if (!empty($apiObj->vConfigSrc)) {
            $vConfig[] = &$apiObj->vConfigSrc;
        }

        if (!empty($apiObj->vimConfig['handle'])) {
            $vConfig[] = &$apiObj->vimConfig['handle'];
        }

        if (empty($vConfig)) {
            return;
        }

        $fieldNames = [];
        if (is_string($config)) {
            $fieldNames[] = $config;
        } elseif (is_array($config)) {
            $fieldNames = $config;
        }
        if (empty($fieldNames)) {
            return;
        }

        $tableInfo = SqlCache::getTableInfo($conn, $table);
        foreach ($fieldNames as $fk => $fn) {
            if (!is_string($fn)) {
                continue;
            }
            $fn = trim(strtolower($fn));
            if (is_int($fk)) {
                $fk = $fn;
            } else {
                $fk = trim(strtolower($fk));
            }
            if (!isset($tableInfo[$fk])) {
                continue;
            }
            $fkName = $tableInfo[$fk]['name'];
            if (empty($fkName)) {
                continue;
            }

            foreach ($vConfig as &$val) {
                if (!is_array($val[$fn])) {
                    continue;
                }

                if (!isset($val[$fn]['name'])) {
                    $val[$fn]['name'] = $fkName;
                    continue;
                }
                if ($val[$fn]['system'] === true) {
                    $val[$fn]['name'] = $fkName;
                }
            }
        }
    }
};