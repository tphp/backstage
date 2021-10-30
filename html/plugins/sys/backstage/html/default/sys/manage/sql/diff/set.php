<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

return function (){
    $connections = config("database.connections");
    unset($connections['user']);
    $dbList = [];
    foreach ($connections as $key=>$val){
        in_array($val['driver'], ['mysql', 'sqlsrv', 'pgsql', 'sqlite']) && $dbList[] = $key;
    }
    $this->setView("dbList", $dbList);
    if(count($dbList) <= 0) return false;
    $conn = $_GET["conn"];
    if(empty($conn)){
        $conn = config("database.default");
    }
    !in_array($conn, $dbList) && $conn = $dbList[0];
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
    list($status, $msg, $sqls) = $msd->getDiff(true);
    $sqlarr = [];
    if(!empty($sqls)) {
        foreach ($sqls as $key => $val) {
            foreach ($val as $k=>$v) {
                $sqlarr[] = [
                    'table' => $key,
                    'field' => $k,
                    'sql' => $v
                ];
            }
        }
    }
    $this->setView("status", $status);
    $this->setView("msg", $msg);
    $this->setView("sqls", $sqlarr);
    $this->setView("conn", $conn);
};
