<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

require_once "SqlClass.php";

class PgSql extends SqlClass
{
    private $compareField = ['pk', 'type', 'isnull', 'default', 'remark'];

    protected function getDatabaseDetail($dbInfo)
    {
        // TODO: Implement getDatabaseDetail() method.

        if (empty($dbInfo) || !is_array($dbInfo)) return [0, "配置为空"];
        $host = $dbInfo['host'];
        $database = $dbInfo['database'];
        $username = $dbInfo['username'];
        if (empty($host) || empty($database) || empty($username)) return [0, "配置错误"];
        $conn = $this->conn;
        list($status, $info) = $this->linkedTest($conn);
        if (!$status) return [0, "{$database}:{$info}"];

        $db = DB::connection($conn);
        //遍历表
        $ret = $this->getListToKeyValue($db->select(<<<EOF
SELECT
c.relname AS table,
a.attname AS field,
CASE WHEN pi.indrelid IS null THEN 0 ELSE 1 END AS pk,
format_type(a.atttypid, a.atttypmod) AS type,
CASE WHEN a.attnotnull THEN 0 ELSE 1 END AS isnull,
col.column_default AS default,
col_description(a.attrelid, a.attnum) AS remark
FROM
pg_class AS c
INNER JOIN pg_attribute AS a ON a.attrelid = c.oid
LEFT JOIN pg_index AS pi ON pi.indrelid = c.oid AND a.attnum = ANY (pi.indkey)
LEFT JOIN information_schema.columns col ON col.table_name=c.relname AND col.ordinal_position = a.attnum
WHERE
c.relname IN (
	SELECT
	tablename as relname
	FROM
	pg_tables
	WHERE
	pg_tables.tablename NOT LIKE 'pg_%'
	AND pg_tables.tablename NOT LIKE 'sql_%'
) AND
a.attrelid = c.oid AND
a.attnum > 0 AND
format_type(a.atttypid, a.atttypmod) <> '-'
ORDER BY c.relname, a.attnum
EOF
        ), 'table', 'field', $this->compareField);
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
            $field = [
                'add' => [],
                'delete' => [],
                'change' => [],
                'equal' => []
            ];
            foreach ($oldDetail as $fKey => $fVal) {
                $nd = $newDetail[$fKey];
                if (!isset($nd)) {
                    $field['delete'][] = $fKey;
                    continue;
                }
                if ($this->arrayIsEqual($nd, $fVal, $this->compareField)) {
                    $field['equal'][$fKey] = $nd;
                } else {
                    $field['change'][$fKey] = [$nd, $fVal];
                }
            }
            foreach ($newDetail as $fKey => $fVal) {
                $od = $oldDetail[$fKey];
                if (!isset($od)) {
                    $field['add'][$fKey] = $fVal;
                }
            }
            if (empty($field['add'])) {
                unset($field['add']);
            }
            if (empty($field['delete'])) {
                unset($field['delete']);
            }
            if (empty($field['change'])) {
                unset($field['change']);
            }
            if (empty($field['equal']) || (empty($field['add']) && empty($field['delete']) && empty($field['change']))) {
                unset($field['equal']);
            }
            if (!empty($field)) {
                $diff['change'][$tableName] = $field;
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

        //主键约束
        $this->pkKvs = $this->getListToKeyValue(
            DB::connection($this->conn)->select("SELECT relname AS name, oid AS id FROM pg_class WHERE relkind='S'"),
            'name',
            'id'
        );

        //表约束
        $priKvs = $this->getListToKeyValue(
            DB::connection($this->conn)->select("SELECT conname AS name, oid AS id FROM pg_constraint WHERE contype='p'"),
            'name',
            'id'
        );

        $sqls = [];
        $table = $diff['table'];
        if (!empty($table)) {
            // 创建表
            $tableCreate = $table['create'];
            if (!empty($tableCreate)) {
                foreach ($tableCreate as $tableName => $tableDetail) {
                    $sqls[$tableName]['__CREATE__'][] = $this->getCreateTableSql($tableName, $tableDetail);
                }
            }

            // 删除表
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
            foreach ($field as $tableName => $fieldInfo) {
                $fAdd = $fieldInfo['add'];
                $fChange = $fieldInfo['change'];
                $fDelete = $fieldInfo['delete'];
                $fEqual = $fieldInfo['equal'];
                if (!empty($fDelete)) {
                    foreach ($fDelete as $fDel) {
                        $sqls[$tableName][$fDel][] = "alter table \"{$tableName}\" drop column \"{$fDel}\"";
                    }
                }

                $isKeySet = false;
                $pkList = [];
                if (!empty($fAdd)) {
                    foreach ($fAdd as $fk => $fa) {
                        if ($fa['pk'] == 1) {
                            !$isKeySet && $isKeySet = true;
                            $pkList[] = $fk;
                        }
                        list($pkStr, $addStr) = $this->getChangeSql($fa, $tableName, $fk);
                        if (!empty($pkStr)) {
                            $sqls[$tableName][$fk][] = $pkStr;
                        }
                        $sqls[$tableName][$fk][] = $addStr;
                    }
                }

                if (!empty($fChange)) {
                    foreach ($fChange as $fk => list($new, $old)) {
                        if ($new['pk'] != $old['pk']) {
                            !$isKeySet && $isKeySet = true;
                        }
                        if ($new['pk'] == 1) {
                            $pkList[] = $fk;
                        }
                        list($pkStr, $addStr) = $this->getChangeSql($new, $tableName, $fk, false, $old);
                        if (!empty($pkStr)) {
                            $sqls[$tableName][$fk][] = $pkStr;
                        }
                        $sqls[$tableName][$fk][] = $addStr;
                    }
                }

                if ($isKeySet) {
                    $pKey = $tableName . "_pkey";
                    if (isset($priKvs[$pKey])) {
                        $sqls[$tableName]['__PK__'][] = "alter table \"{$tableName}\" drop CONSTRAINT \"{$pKey}\"";
                    }
                    if (!empty($pkList)) {
                        $pkListRep = [];
                        foreach ($pkList as $pl) {
                            $pkListRep[] = str_replace('"', '""', $pl);
                        }
                        $fieldStr = implode(",", $pkListRep);
                        $sqls[$tableName]['__PK__'][] = "alter table \"{$tableName}\" add CONSTRAINT \"{$pKey}\" primary key({$fieldStr})";
                    }
                }
            }
        }
        $sqls = $this->getRealSqls($sqls);
        if (empty($sqls)) {
            return [0, "数据相同，无需同步操作！"];
        }
        return [1, $sqls];
    }

    protected function updateDatabase()
    {
        // TODO: Implement updateDatabase() method.

        $dbInfo = $this->dbInfo;
        $cotOk = 0;
        $cotNo = 0;
        $sqls = $this->sqls;
        $errors = [];
        $oks = [];
        $db = DB::connection($this->conn);
        foreach ($sqls as $key => $val) {
            foreach ($val as $k => $v) {
                try {
                    $vStrInit = trim(implode(";", $v), ";");
                    if (strpos($vStrInit, ";") === false) {
                        $vStr = $vStrInit;
                    } else {
                        $vStr = <<<EOF
DO $$
DECLARE
BEGIN
    {$vStrInit};
END
$$;
EOF;
                    }
                    $ret = $db->statement($vStr);
                    if ($ret > 0) {
                        $cotOk += $ret;
                        $oks[] = $vStrInit;
                    } else {
                        $cotNo++;
                        $errors[] = $vStrInit;
                    }
                } catch (Exception $e) {
                    $cotNo++;
                    $errors[] = $e->getMessage();
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
        $pkKvs = $this->pkKvs;
        $tn = str_replace('"', '""', $tableName);
        $lst = [];
        $pks = [];
        $hasFlag = false;
        $remarks = [];
        $lStr = "nextval('";
        $rStr = "'::";
        $lStrLen = strlen($lStr);
        $headPks = [];
        foreach ($tableDetail as $fk => $fv) {
            $fk = str_replace('"', '""', $fk);
            $s = "\"{$fk}\" {$fv['type']} ";
            if ($fv['isnull'] == '0') {
                $s .= "NOT NULL ";
            }
            if (!empty($fv['default'])) {
                $fvDef = $fv['default'];
                $lPos = strpos($fvDef, $lStr);
                $rPos = strpos($fvDef, $rStr);
                if ($lPos >= 0 && $rPos > $lPos) {
                    $pkName = substr($fvDef, $lPos + $lStrLen, $rPos - $lPos - $lStrLen);
                    if (!isset($pkKvs[$pkName])) {
                        $headPks[] = "CREATE SEQUENCE {$pkName} START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1";
                    }
                }
                $s .= "DEFAULT {$fv['default']} ";
            }
            if ($fv['pk'] == '1') {
                $pks[] = "\"{$fk}\"";
            }
            $lst[] = trim($s);

            $remark = $fv['remark'];
            if (!empty($remark)) {
                $remark = str_replace("'", "''", $remark);
                $remarks[] = "COMMENT ON COLUMN \"public\".\"{$tn}\".\"{$fk}\" IS '{$remark}'";
            }
        }
        if (count($pks) > 0 && !$hasFlag) {
            $pkStr = implode(",", $pks);
            $lst[] = "CONSTRAINT \"{$tn}_pkey\" PRIMARY KEY ({$pkStr})";
        }
        $lstStr = implode(",", $lst);
        $retStr = "CREATE TABLE \"public\".\"{$tn}\" ({$lstStr});";
        if (count($remarks) > 0) {
            $retStr .= implode(";", $remarks) . ";";
        }
        if (!empty($headPks)) {
            $retStr = implode(";", $headPks) . ";" . $retStr;
        }
        return $retStr;
    }

    /**
     * 字段类型转换
     * @param $type
     * @param $oType
     */
    private function getUsingStr($type, $oType)
    {
        $ret = "";
        if ($type == $oType) {
            return $ret;
        }

        if ($type == 'smallint') {
            $ret = "integer";
        } elseif ($type == 'integer') {
            if ($oType == 'money') {
                $ret = "numeric";
            } else {
                $ret = "integer";
            }
        } elseif ($type == 'money') {
            $ret = "numeric";
        }
        return $ret;
    }

    /**
     * 获取更改字段或新增字段语句
     * @param $info
     * @param bool $isAdd
     * @return string
     */
    private function getChangeSql($info, $tableName, $fieldName, $isAdd = true, $oldInfo = [])
    {
        $type = $info['type'];
        if ($isAdd) {
            $fChangeStr = "ALTER TABLE \"public\".\"{$tableName}\" ADD \"{$fieldName}\" {$type} ";
        } else {
            $oType = $oldInfo['type'];
            if ($type !== $oType) {
                $fChangeStr = "ALTER TABLE \"public\".\"{$tableName}\" ALTER COLUMN \"{$fieldName}\" TYPE {$type} ";
                $using = $this->getUsingStr($type, $oType);
                if (!empty($using)) {
                    $fChangeStr .= "USING  \"{$fieldName}\"::{$using} ";
                }
            }
        }
        $isNull = $info['isnull'];
        $isNullSql = '';
        if ($isAdd) {
            if ($isNull == '0') {
                $fChangeStr .= "NOT NULL ";
            }
        } elseif ($isNull !== $oldInfo['isnull']) {
            if ($isNull == '0') {
                $isNullSql = "ALTER TABLE \"public\".\"{$tableName}\" ALTER COLUMN \"{$fieldName}\" SET NOT NULL";
            } else {
                $isNullSql = "ALTER TABLE \"public\".\"{$tableName}\" ALTER COLUMN \"{$fieldName}\" DROP NOT NULL";
            }
        }
        $pkStr = "";
        if (empty($info['default'])) {
            if (!$isAdd && !empty($oldInfo)) {
                // 删除默认值
                $fChangeStr = trim($fChangeStr) . ";ALTER TABLE \"public\".\"{$tableName}\" ALTER COLUMN \"{$fieldName}\" DROP DEFAULT";
            }
        } else {
            $pkKvs = $this->pkKvs;
            $lStr = "nextval('";
            $rStr = "'::";
            $lStrLen = strlen($lStr);
            $infoDef = $info['default'];
            $lPos = strpos($infoDef, $lStr);
            $rPos = strpos($infoDef, $rStr);
            if ($lPos >= 0 && $rPos > $lPos) {
                $pkName = substr($infoDef, $lPos + $lStrLen, $rPos - $lPos - $lStrLen);
                if (!isset($pkKvs[$pkName])) {
                    $pkStr = "CREATE SEQUENCE {$pkName} START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1";
                }
            }
            if ($isAdd || $infoDef != $oldInfo['default']) {
                // 设置默认值
                $fChangeStr = trim($fChangeStr) . ";ALTER TABLE \"public\".\"{$tableName}\" ALTER COLUMN \"{$fieldName}\" SET DEFAULT {$infoDef}";
            }
        }
        $remark = $info['remark'];
        !empty($remark) && $remark = trim($remark);
        $isSetRemark = false;
        if ($isAdd) {
            if (!empty($remark)) {
                $isSetRemark = true;
            }
        } else {
            $oRemark = $oldInfo['remark'];
            !empty($oRemark) && $oRemark = trim($oRemark);
            empty($remark) && $remark = '';
            empty($oRemark) && $oRemark = '';
            if ($remark !== $oRemark) {
                $isSetRemark = true;
            }
        }
        if ($isSetRemark) {
            $remark = str_replace("'", "''", $remark);
            $fChangeStr = trim($fChangeStr) . ";COMMENT ON COLUMN \"public\".\"{$tableName}\".\"{$fieldName}\" IS '{$remark}'";
        }
        if (!empty($isNullSql)) {
            $fChangeStr = trim($fChangeStr) . ";" . $isNullSql;
        }

        return [$pkStr, trim(trim($fChangeStr), ";")];
    }

    /**
     * 获取真实数据库语句
     * @param $sqls
     * @return array
     */
    private function getRealSqls($sqls)
    {
        $retSqls = [];
        if (empty($sqls)) {
            return $retSqls;
        }
        foreach ($sqls as $key => $val) {
            foreach ($val as $k => $v) {
                if (count($v) > 0) {
                    $retSqls[$key][$k][] = implode(";", $v);
                }
            }
        }
        return $retSqls;
    }
}
