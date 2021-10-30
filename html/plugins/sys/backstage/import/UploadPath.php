<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

class UploadPath
{
    /**
     * 获取上传路径
     * @return mixed|string
     */
    public function get()
    {
        $dc = \Tphp\Config::$domain;
        $dcUpload = $dc['upload'];
        if (empty($dcUpload) || !is_string($dcUpload)) {
            $dcUpload = '';
        } else {
            $dcUpload = str_replace("\\", "/", $dcUpload);
            $dcUpload = trim($dcUpload, " /");
        }
        return $dcUpload;
    }

}