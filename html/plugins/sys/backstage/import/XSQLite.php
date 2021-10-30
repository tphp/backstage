<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

class XSQLite extends SQLite3
{
    function __construct($fileName = '')
    {
        $this->fileName = $fileName;
    }

    public function execute($sql)
    {
        try {
            $this->open($this->fileName);
            $ret = $this->exec($sql);
            if (!$ret) {
                $ret = [0, $this->lastErrorMsg()];
            } else {
                $ret = [1, $ret];
            }
        } catch (Exception $e) {
            $ret = [0, $this->lastErrorMsg()];
        }
        $this->close();
        return $ret;
    }

    public function select($sql)
    {
        try {
            $this->open($this->fileName);
            $ret = $this->query($sql);
            if (!$ret) {
                $retList = [0, $this->lastErrorMsg()];
            } else {
                $list = [];
                while ($row = $ret->fetchArray(SQLITE3_ASSOC)) {
                    $lst = [];
                    foreach ($row as $key => $val) {
                        $lst[strtolower($key)] = $val;
                    }
                    $list[] = $lst;
                }
                $retList = [1, $list];
            }
        } catch (Exception $e) {
            $retList = [0, $this->lastErrorMsg()];
        }
        $this->close();
        return $retList;
    }
}