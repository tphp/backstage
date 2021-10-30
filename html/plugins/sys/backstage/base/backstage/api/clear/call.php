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
     * 清空关联表内容
     * @param $apiObj
     * @param $dbInfo
     */
    private function clearConfigLinkTable($apiObj, $dbInfo)
    {
        if (empty($dbInfo)) return;
        $tableName = "";
        $connName = "";
        if (is_string($dbInfo)) {
            $tableName = $dbInfo;
        } elseif (is_array($dbInfo)) {
            list($table, $conn) = $dbInfo;
            if (!is_string($table)) return;
            if (empty($conn) || !is_string($conn)) {
                $tableName = $table;
            } else {
                $connName = $conn;
            }
        } else {
            return;
        }

        empty($connName) && $connName = $apiObj->defaultConn;
        $db = $apiObj->tplInit->db($tableName, $connName);

        try {
            $db->truncate();
        } catch (\Exception $e) {
            $apiObj->__exitError($e->getMessage());
        }
    }

    /**
     * 清空表中的所有数据
     * @param $apiObj
     */
    public function html($apiObj)
    {
        if ($apiObj->tplInit->isPost()) {
            $vim = $apiObj->vimConfig;
            if (!$vim['is']['clear'] || $_POST['bool'] != 'true') {
                $apiObj->__exitError("不允许清空操作！");
            }
            $apiObj->tplInit->db("", $apiObj->defaultConn)->truncate();

            $delete = $vim['delete'];
            //删除关联数据库
            if (!empty($delete) && is_array($delete)) {
                foreach ($delete as $key => $val) {
                    if (empty($val) || !is_array($val) || count($val) < 2 || !is_array($val[1])) continue;
                    $this->clearConfigLinkTable($apiObj, $val[0]);
                }
            }
            EXITJSON(1, "数据清空成功！");
        }
        $apiObj->page404();
    }
};