<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

require_once "SqlClass.php";

class Sqlite extends SqlClass
{
    protected function getDatabaseDetail($dbInfo)
    {
        // TODO: Implement getDatabaseDetail() method.

        $fileName = $dbInfo['database'];
        $ret = [];
        if (empty($fileName) || !is_file($fileName)) {
            return [1, '数据库不存在', $ret];
        }
        $conn = $this->conn;
        $db = DB::connection($conn);
        $tableList = $db->select("select name as 'table' from sqlite_master where type='table' and name<>'sqlite_sequence' order by name");
        if (empty($tableList)) {
            return [1, 'ok', $ret];
        }
        foreach ($tableList as $key => $val) {
            $table = $val->table;
            if (strpos($table, "'")) {
                continue;
            }
            $fieldList = $db->select("PRAGMA table_info('{$table}')");
            $ret[$table] = [];
            foreach ($fieldList as $fd) {
                $ret[$table][$fd->name] = [
                    'cid' => $fd->cid,
                    'type' => $fd->type,
                    'notnull' => $fd->notnull,
                    'dflt_value' => $fd->dflt_value,
                    'pk' => $fd->pk
                ];
            }
        }
        return [1, 'ok', $ret];
    }

    protected function compareDatabase($new, $old)
    {
        // TODO: Implement compareDatabase() method.

        $diff = [
            'table' => [],
            'change' => []
        ];
        foreach ($old as $tableName => $tableDetail) {
            if (!isset($new[$tableName])) {
                //删除表
                $diff['table']['drop'][$tableName] = $tableName;
            }
        }
        foreach ($new as $tableName => $newDetail) {
            $oldDetail = $old[$tableName];
            if (!isset($oldDetail)) {
                //创建表
                $diff['table']['create'][$tableName] = $newDetail;
                continue;
            }
            $field = [];
            $isDelete = false;
            foreach ($oldDetail as $fKey => $fVal) {
                $nd = $newDetail[$fKey];
                if (!isset($nd)) {
                    $isDelete = true;
                    continue;
                }
                if (!$this->arrayIsEqual($nd, $fVal, ['type', 'notnull', 'dflt_value', 'pk'])) {
                    $field[] = $fKey;
                }
            }
            if (!empty($field) || $isDelete) {
                $saveField = [];
                foreach ($oldDetail as $fKey => $fVal) {
                    if (isset($newDetail[$fKey])) {
                        $saveField[] = $fKey;
                    }
                }
                $diff['change'][$tableName] = [
                    $newDetail,
                    $saveField
                ];
            }
        }
        return $diff;
    }

    protected function buildQuery($diff)
    {
        // TODO: Implement buildQuery() method.

        if (empty($diff['table']) && empty($diff['change'])) {
            return [0, "数据相同，无需同步操作！"];
        }

        $sqls = [];
        $table = $diff['table'];
        if (!empty($table)) {
            $tableCreate = $table['create'];
            if (!empty($tableCreate)) {
                foreach ($tableCreate as $tableName => $tableDetail) {
                    $sqls[$tableName]['__CREATE__'][] = $this->getCreateTableSql($tableName, $tableDetail);
                }
            }
            $tableDrop = $table['drop'];
            if (!empty($tableDrop)) {
                foreach ($tableDrop as $tableName => $tableDetail) {
                    $tn = str_replace('"', '""', $tableName);
                    $sqls[$tableName]['__DROP__'][] = "DROP TABLE \"{$tn}\"";
                }
            }
        }

        $field = $diff['change'];
        if (!empty($field)) {
            foreach ($field as $tableName => list($create, $field)) {
                $createSql = $this->getCreateTableSql($tableName, $create);
                if (empty($field)) {
                    // 当同步字段为空时
                    // 删除原表
                    $sqls[$tableName]['__DROP__'][] = "DROP TABLE \"{$tableName}\"";
                    // 创建新表
                    $sqls[$tableName]['__CREATE__'][] = $createSql;
                } else {
                    $f = [];
                    foreach ($field as $fv) {
                        $f[] = '"' . str_replace('"', '""', $fv) . '"';
                    }
                    $fStr = implode(",", $f);
                    $rTableName = "__{$tableName}";
                    // 先重命名表
                    $sqls[$tableName]['__RENAME__'][] = "ALTER TABLE \"{$tableName}\" RENAME TO \"{$rTableName}\";";
                    // 创建新表
                    $sqls[$tableName]['__CREATE__'][] = $createSql;
                    // 把原有的列表数据复制到新表
                    $sqls[$tableName]['__INSERT__'][] = "INSERT INTO \"{$tableName}\" ({$fStr}) SELECT {$fStr} FROM \"{$rTableName}\";";
                    // 删除重命名表
                    $sqls[$tableName]['__DROP__'][] = "DROP TABLE \"{$rTableName}\";";
                }
            }
        }
        return [1, $sqls];
    }

    protected function updateDatabase()
    {
        // TODO: Implement updateDatabase() method.

        $dbInfo = $this->dbInfo;
        $dbFile = $dbInfo['database'];
        if (!is_file($dbFile)) {
            import('XSQLite', $dbFile)->select("select 'add file' as msg");
        }

        $cotOk = 0;
        $cotNo = 0;
        $sqls = $this->sqls;
        $errors = [];
        $oks = [];
        $db = DB::connection($this->conn);
        foreach ($sqls as $key => $val) {
            foreach ($val as $k => $v) {
                foreach ($v as $vv) {
                    try {
                        $ret = $db->statement($vv);
                        if ($ret > 0) {
                            $cotOk += $ret;
                            $oks[] = $vv;
                        } else {
                            $cotNo++;
                            $errors[] = $vv;
                        }
                    } catch (Exception $e) {
                        $cotNo++;
                        $errors[] = $e->getMessage();
                    }
                }
            }
        }

        $this->saveLogs($dbInfo['driver'] . "/" . $this->conn, $oks, $errors);

        if ($cotOk > 0) {
            $str = "操作成功数: {$cotOk}";
            if ($cotNo > 0) {
                $str .= "<BR>操作失败数: {$cotNo}";
                return [1, $str, $errors];
            }
            return [1, $str];
        }
        return [0, "操作失败数: {$cotNo}", $errors];
    }

    /**
     * 获取创建表语句
     * @param $tableName
     * @param $tableDetail
     * @return string
     */
    private function getCreateTableSql($tableName, $tableDetail)
    {
        $tn = str_replace('"', '""', $tableName);
        $lst = [];
        $pks = [];
        foreach ($tableDetail as $fk => $fv) {
            $fk = str_replace('"', '""', $fk);
            $s = "\"{$fk}\" {$fv['type']} ";
            if ($fv['notnull'] == '1') {
                $s .= "NOT NULL ";
            }
            if ($fv['dflt_value'] !== null) {
                $dv = $fv['dflt_value'];
                $s .= "DEFAULT {$dv} ";
            }
            if ($fv['pk'] == '1') {
                $pks[] = "\"{$fk}\"";
            }
            $lst[] = trim($s);
        }
        if (count($pks) > 0) {
            $pkStr = implode(",", $pks);
            $lst[] = "PRIMARY KEY({$pkStr})";
        }
        $lstStr = implode(",", $lst);
        return "CREATE TABLE \"{$tn}\" ({$lstStr});";
    }
}
