<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

return new class
{
    /**
     * JSON 模式
     * @return array
     */
    public function json()
    {
        $gets = [];
        foreach ($_GET as $key => $val) {
            $gets[strtolower(trim($key))] = $val;
        }
        $config = [];
        if (array_key_exists('p', $gets) && is_numeric($gets['p'])) {
            $config['ispage'] = true;
            if (array_key_exists('psize', $gets) && is_numeric($gets['psize'])) {
                $config['pagesize'] = $gets['psize'];
            }
        }

        return $config;
    }

    /**
     * HTML 模式
     * @return array
     */
    public function html()
    {
        $p = $_GET['p'];
        $config = [];
        if ($p > 0) {
            $config['ispage'] = true;
            $pSize = $_GET['psize'];
            if ($pSize > 0) {
                $config['pagesize'] = $pSize;
            }
        }
        !empty($_GET) && $config['get'] = $_GET;
        !empty($_POST) && $config['post'] = $_POST;

        return $config;
    }
};