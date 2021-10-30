<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

require_once "SqlClass.php";

class SqlSrv extends SqlClass
{
    private $compareField = ['flag', 'pk', 'type', 'length', 'point', 'isnull', 'default', 'remark'];
    private $isLength = ["binary", "char", "datetime2", "datetimeoffset", "decimal", "nchar", "numeric", "nvarchar", "time", "varbinary", "varchar"];

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
    d.name as 'table',
    a.name as field,
    COLUMNPROPERTY( a.id,a.name,'IsIdentity') as flag,
    (
        SELECT count(*) FROM sysobjects
        WHERE (
            name in (
                SELECT name FROM sysindexes
                WHERE (id = a.id)  AND (
                    indid in (
                        SELECT indid FROM sysindexkeys
                        WHERE (id = a.id) AND (
                            colid in (
                                SELECT colid FROM syscolumns
                                WHERE (id = a.id) AND (name = a.name)
                            )
                        )
                    )
                )
            )
        )
        AND (xtype = 'PK')
    ) as pk,
    b.name as type,
    COLUMNPROPERTY(a.id,a.name,'PRECISION') as length,
    isnull(COLUMNPROPERTY(a.id,a.name,'Scale'),0) as point,
    a.isnullable as isnull,
    isnull(e.text,'') as 'default',
    isnull(g.[value],'') AS remark
FROM syscolumns a left join systypes b
    on a.xtype=b.xusertype
    inner join sysobjects d
    on a.id=d.id and d.xtype='U' and d.name<>'dtproperties'
    left join syscomments e
    on a.cdefault=e.id
    left join sys.extended_properties g
    on a.id=g.major_id AND a.colid = g.minor_id
order by a.id, a.colorder
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

        // 查询表数量统计
        $tableCounts = $this->getListToKeyValue(DB::connection($this->conn)->select(<<<EOF
SELECT
	a.name,
	b.rows
FROM
	sysobjects AS a
	INNER JOIN sysindexes AS b ON a.id = b.id
WHERE
	a.type = 'u'
	AND b.indid IN (0, 1)
	AND b.rows > 0
EOF
        ), 'name', 'rows');

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
                if (isset($tableCounts[$tableName])) {
                    $diff['change'][$tableName] = $field;
                } else {
                    $diff['table']['recreate'][$tableName] = $newDetail;
                }
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
            // 创建表
            $tableCreate = $table['create'];
            if (!empty($tableCreate)) {
                foreach ($tableCreate as $tableName => $tableDetail) {
                    $sqls[$tableName]['__CREATE__'][] = $this->getCreateTableSql($tableName, $tableDetail);
                }
            }

            // 当列表数据为空时先删除原表再创建
            $tableReCreate = $table['recreate'];
            if (!empty($tableReCreate)) {
                foreach ($tableReCreate as $tableName => $tableDetail) {
                    $sqls[$tableName]['__DROP__'][] = "DROP TABLE [{$tableName}]";
                    $sqls[$tableName]['__CREATE__'][] = $this->getCreateTableSql($tableName, $tableDetail);
                }
            }

            // 删除表
            $tableDrop = $table['drop'];
            if (!empty($tableDrop)) {
                foreach ($tableDrop as $tableName => $tableDetail) {
                    $tn = str_replace('"', '""', $tableName);
                    $sqls[$tableName]['__DROP__'][] = "DROP TABLE [{$tn}]";
                }
            }
        }

        $field = $diff['change'];
        if (!empty($field)) {
            $delTables = [];
            foreach ($field as $tableName => $f) {
                if (!empty($f['change']) || !empty($f['delete'])) {
                    $delTables[] = "'" . str_replace("'", "''", $tableName) . "'";
                }
            }
            if (empty($delTables)) {
                $binds = [];
            } else {
                // 获取约束字段：主键或约束
                $delTableStr = implode(",", $delTables);
                $binds = $this->getListToKeyValue(DB::connection($this->conn)->select(<<<EOF
SELECT
	TAB.NAME AS [table],
	IDX.NAME AS [name],
	COL.NAME AS [field]
FROM
	SYS.INDEXES IDX
	JOIN SYS.INDEX_COLUMNS IDXCOL ON (
		IDX.OBJECT_ID = IDXCOL.OBJECT_ID
		AND IDX.INDEX_ID = IDXCOL.INDEX_ID
		AND ( IDX.IS_PRIMARY_KEY = 1 OR IDX.IS_UNIQUE_CONSTRAINT = 1 OR ( IDX.IS_UNIQUE_CONSTRAINT = 0 AND IDX.IS_PRIMARY_KEY = 0 ) )
	)
	JOIN SYS.TABLES TAB ON ( IDX.OBJECT_ID = TAB.OBJECT_ID )
	JOIN SYS.COLUMNS COL ON ( IDX.OBJECT_ID = COL.OBJECT_ID AND IDXCOL.COLUMN_ID = COL.COLUMN_ID )
WHERE
	TAB.NAME IN({$delTableStr})

UNION
SELECT
	TAB.NAME AS [table],
	DCS.NAME AS [name],
	COL.NAME AS [field]
FROM
	SYS.DEFAULT_CONSTRAINTS DCS
	JOIN SYS.TABLES TAB ON ( DCS.PARENT_OBJECT_ID = TAB.OBJECT_ID )
	JOIN SYS.COLUMNS COL ON ( DCS.PARENT_OBJECT_ID = COL.OBJECT_ID AND DCS.PARENT_COLUMN_ID = COL.COLUMN_ID )
WHERE
	TAB.NAME IN({$delTableStr})
EOF
                ), 'table', 'field', 'name');
            }
            foreach ($field as $tableName => $fieldInfo) {
                $fAdd = $fieldInfo['add'];
                $fChange = $fieldInfo['change'];
                $fDelete = $fieldInfo['delete'];
                $fEqual = $fieldInfo['equal'];

                // 如果没有相同的字段则先删除表然后再创建
                if (empty($fChange) && empty($fEqual)) {
                    $sqls[$tableName]['__DROP__'][] = "DROP TABLE [{$tableName}]";
                    if (!empty($fAdd)) {
                        $sqls[$tableName]['__CREATE__'][] = $this->getCreateTableSql($tableName, $fAdd);
                    }
                    continue;
                }

                // 如果存在要删除的字段，先删除字段约束，再删除字段
                if (!empty($fDelete)) {
                    $fDelArr = [];
                    $fBindArr = [];
                    foreach ($fDelete as $fDelKey) {
                        $fDelArr[] = "[{$fDelKey}]";
                        if (is_array($binds[$tableName]) && isset($binds[$tableName][$fDelKey])) {
                            $fBindArr[] = "[{$binds[$tableName][$fDelKey]}]";
                        }
                    }
                    if (count($fBindArr) > 0) {
                        $fBindStr = implode(",", $fBindArr);
                        $sqls[$tableName]['__CHANGE__'][] = "ALTER TABLE [{$tableName}] DROP CONSTRAINT {$fBindStr}";
                    }
                    $fDelStr = implode(",", $fDelArr);
                    $sqls[$tableName]['__CHANGE__'][] = "ALTER TABLE [{$tableName}] DROP COLUMN {$fDelStr}";
                }

                // 如果存在修改的字段
                $addPks = [];
                $deletePks = [];
                $isUpdatePk = false;
                if (!empty($fChange)) {
                    foreach ($fChange as $fieldName => list($new, $old)) {
                        empty($sqls[$tableName][$fieldName]) && $sqls[$tableName][$fieldName] = [];
                        if (!$this->arrayIsEqual($new, $old, ['type', 'length', 'point', 'isnull'])) {
                            $sqls[$tableName][$fieldName][] = $this->getChangeSql($new, $tableName, $fieldName);
                        }
                        if ($new['default'] != $old['default']) {
                            if (isset($binds[$fieldName])) {
                                $sqls[$tableName][$fieldName][] = "ALTER TABLE [{$tableName}] DROP CONSTRAINT [{$fieldName}]";
                            }
                            if (!empty($new['default'])) {
                                $sqls[$tableName][$fieldName][] = "ALTER TABLE [{$tableName}] ADD DEFAULT {$new['default']} FOR [{$fieldName}] WITH VALUES";
                            }
                        }
                        if (!$isUpdatePk && $new['pk'] != $old['pk']) {
                            $isUpdatePk = true;
                        }
                        $remark = $new['remark'];
                        empty($remark) && $remark = '';
                        if ($new['remark'] != $old['remark']) {
                            $sqls[$tableName][$fieldName][] = "execute sp_addextendedproperty 'MS_Description','{$remark}','user','dbo','table','{$tableName}','column','{$fieldName}'";
                        }
                    }
                }

                // 如果存在新增字段
                if (!empty($fAdd)) {
                    foreach ($fAdd as $fieldName => $fVal) {
                        $sqls[$tableName][$fieldName][] = $this->getChangeSql($fVal, $tableName, $fieldName, true);
                        if ($fVal['pk'] == '1') {
                            if (!$isUpdatePk) {
                                $isUpdatePk = true;
                            }
                            $addPks[] = "[{$fieldName}]";
                        }
                        if (!empty($fVal['default'])) {
                            $sqls[$tableName][$fieldName][] = "ALTER TABLE [{$tableName}] ADD DEFAULT {$fVal['default']} FOR [{$fieldName}] WITH VALUES";
                        }
                        $remark = $fVal['remark'];
                        if (!empty($remark)) {
                            $sqls[$tableName][$fieldName][] = "execute sp_addextendedproperty 'MS_Description','{$remark}','user','dbo','table','{$tableName}','column','{$fieldName}'";
                        }
                    }
                }

                if ($isUpdatePk) {
                    if (!empty($fChange)) {
                        foreach ($fChange as $fieldName => list($new, $old)) {
                            if ($new['pk'] != $old['pk']) {
                                if ($new['pk'] == '1') {
                                    $addPks[] = "[{$fieldName}] ASC";
                                }
                                if (isset($binds[$fieldName])) {
                                    $deletePks[] = "[{$fieldName}]";
                                }
                            }
                        }
                        if (!empty($deletePks)) {
                            $deletePkStr = implode(",", $deletePks);
                            $sqls[$tableName][$fieldName][] = "ALTER TABLE [{$tableName}] DROP CONSTRAINT [{$deletePkStr}]";
                        }
                        if (!empty($addPks)) {
                            $addPkStr = implode(",", $addPks);
                            $sqls[$tableName][$fieldName][] = "ALTER TABLE [{$tableName}] ADD PRIMARY KEY ({$addPkStr})";
                        }
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
                    $ret = $db->statement($v);
                    if ($ret > 0) {
                        $cotOk += $ret;
                        $oks[] = $v;
                    } else {
                        $cotNo++;
                        $errors[] = $v;
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
        $tn = str_replace('"', '""', $tableName);
        $lst = [];
        $pks = [];
        $hasFlag = false;
        $remarks = [];
        foreach ($tableDetail as $fk => $fv) {
            $fk = str_replace('"', '""', $fk);
            $len = $fv['length'];
            if (in_array($fv['type'], $this->isLength)) {
                if ($len >= 0) {
                    if ($fv['point'] > 0) {
                        $len .= ", " . $fv['point'];
                    }
                } else {
                    $len = 'max';
                }
                $len = "({$len})";
            } else {
                $len = '';
            }
            $s = "[{$fk}] [{$fv['type']}]{$len} ";
            if ($fv['flag'] == '0') {
                if ($fv['isnull'] == '1') {
                    $s .= "NULL ";
                } else {
                    $s .= "NOT NULL ";
                }
                if (!empty($fv['default'])) {
                    $s .= "DEFAULT {$fv['default']} ";
                }
            } else {
                $s .= "IDENTITY(1,1) ";
                if ($fv['pk'] == '1') {
                    $s .= "PRIMARY KEY ";
                }
                $hasFlag = true;
            }
            if ($fv['pk'] == '1') {
                $pks[] = "[{$fk}] ASC";
            }
            $lst[] = trim($s);

            $remark = $fv['remark'];
            if (!empty($remark)) {
                $remarks[] = "execute sp_addextendedproperty 'MS_Description','{$remark}','user','dbo','table','{$tn}','column','{$fk}'";
            }
        }
        if (count($pks) > 0 && !$hasFlag) {
            $pkStr = implode(",", $pks);
            $lst[] = "PRIMARY KEY CLUSTERED({$pkStr})";
        }
        $lstStr = implode(",", $lst);
        $retStr = "CREATE TABLE [dbo].[{$tn}] ({$lstStr});";
        if (count($remarks) > 0) {
            $retStr .= implode(";", $remarks) . ";";
        }
        return $retStr;
    }

    /**
     * 获取更改字段或新增字段语句
     * @param $info
     * @param bool $isAdd
     * @return string
     */
    private function getChangeSql($info, $tableName, $fieldName, $isAdd = false)
    {
        $len = $info['length'];
        if (in_array($info['type'], $this->isLength)) {
            if ($len >= 0) {
                if ($info['point'] > 0) {
                    $len .= ", " . $info['point'];
                }
            } else {
                $len = 'max';
            }
            $len = "({$len})";
        } else {
            $len = '';
        }
        if ($isAdd) {
            $fChangeStr = "ALTER TABLE [{$tableName}] ADD ";
        } else {
            $fChangeStr = "ALTER TABLE [{$tableName}] ALTER COLUMN ";
        }
        $fChangeStr .= "[{$fieldName}] [{$info['type']}]{$len} ";
        if ($info['isnull'] == '1') {
            $fChangeStr .= "NULL ";
        } else {
            $fChangeStr .= "NOT NULL ";
        }
        return trim($fChangeStr);
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
                    $retSqls[$key][$k] = implode(";", $v);
                }
            }
        }
        return $retSqls;
    }
}
