<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

return function (){
    if($this->isPost()) {
        $conn = $_POST['conn'];
        $type = $_POST['type'];
        if(empty($conn) || empty($type)){
            if(!in_array($type, ['save', 'rest'])) EXITJSON(0, "参数不正确");
        }
        $connections = config("database.connections");
        $dbList = [];
        foreach ($connections as $key => $val) {
            in_array($val['driver'], ['mysql', 'sqlsrv', 'pgsql', 'sqlite']) && $dbList[] = $key;
        }
        if (count($dbList) <= 0) EXITJSON(0, "数据库配置无效");
        if(!in_array($conn, $dbList)) EXITJSON(0, "数据库配置无效");
        $dbInfo = $connections[$conn];
        $driver = $dbInfo['driver'];
        if($driver == 'sqlsrv'){
            $importName = 'SqlSrv';
        }elseif($driver == 'pgsql'){
            $importName = 'PgSql';
        }elseif($driver == 'sqlite'){
            $importName = 'Sqlite';
        }else{
            $importName = 'MySql';
        }
        $msd = import("SqlDiff.{$importName}", $conn);
        if($type == 'save'){
            list($status, $msg) = $msd->save();
            if(!$status){
                EXITJSON(0, $msg);
            }
            EXITJSON(1, "字段保存成功");
        }else {
            list($status, $msg) = $msd->getDiff(true);
            if($status){
                list($status, $msg) = $msd->run();
                EXITJSON($status, $msg);
            }else{
                EXITJSON(0, $msg);
            }
        }
    }
    EXITJSON(0, "404");
};
