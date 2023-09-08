<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

require_once "SqlClass.php";

class MySql extends SqlClass
{
    private  function is_charrater() {
        $cr = env('CHARACTER');
        if ($cr === 'false' || $cr === false) {
            return false;
        }
        return true;
    }

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
        $version = $db->select("select version() as v")[0]->v;
        $serverSet = 'character_set_connection=utf8, character_set_results=utf8, character_set_client=binary';
        $serverSet .= $version > '5.0.1' ? ', sql_mode=\'\'' : '';
        $db->statement("SET {$serverSet}");

        $detail = array('table' => array(), 'field' => array(), 'index' => array());
        $tables = (array)$db->select("show table status");
        if ($tables) {
            foreach ($tables as $keyTable => $table) {
                $table = (array)$table;
                $detail['table'][$table['Name']] = $table;
                //字段
                $fields = (array)$db->select("show full fields from `" . $table['Name'] . "`");
                if ($fields) {
                    foreach ($fields as $keyField => $field) {
                        $field = (array)$field;
                        $fields[$field['Field']] = $field;
                        unset($fields[$keyField]);
                    }
                    $detail['field'][$table['Name']] = $fields;
                } else {
                    return [0, '无法获得表的字段:' . $database . ':' . $table['Name']];
                }
                //索引
                $indexes = (array)$db->select("show index from `" . $table['Name'] . "`");
                if ($indexes) {
                    foreach ($indexes as $keyIndex => $index) {
                        $index = (array)$index;
                        $indexes[$keyIndex] = $index;
                        if (!isset($indexes[$index['Key_name']])) {
                            $index['Column_name'] = array($index['Seq_in_index'] => $index['Column_name']);
                            $indexes[$index['Key_name']] = $index;
                        } else
                            $indexes[$index['Key_name']]['Column_name'][$index['Seq_in_index']] = $index['Column_name'];
                        unset($indexes[$keyIndex]);
                    }
                    $detail['index'][$table['Name']] = $indexes;
                } else {
                    //$errors[]='无法获得表的索引信息:'.$database.':'.$table['Name'];
                    $detail['index'][$table['Name']] = array();
                }
            }
            return [1, "ok", $detail];
        } else {
            return [1, '无法获得数据库的表详情' . []];
        }
    }

    private function getTypeName($type)
	{
		$pos = strpos($type, "(");
		if ($pos === false) {
			return $type;
		}
		return substr($type, 0, $pos);
	}

	private function differ($new, $src, $isCut=false)
	{
		if (is_null($new)) {
			$new = "";
		}
		if (is_null($src)) {
			$src = "";
		}
		if ($isCut) {
			$new = $this->getTypeName($new);
			$src = $this->getTypeName($src);
		}
		return $new != $src;
	}

    protected function compareDatabase($new, $old)
    {
        // TODO: Implement compareDatabase() method.

        $diff = array('table' => array(), 'field' => array(), 'index' => array());
        //table
        if (!empty($old['table'])) {
            foreach ($old['table'] as $tableName => $tableDetail) {
                if (!isset($new['table'][$tableName]))
                    $diff['table']['drop'][$tableName] = $tableName; //删除表
            }
        }
        if (!empty($new['table'])) {
            $is_charrater = $this->is_charrater();
            foreach ($new['table'] as $tableName => $tableDetail) {
                if (!isset($old['table'][$tableName])) {
                    //新建表
                    $diff['table']['create'][$tableName] = $tableDetail;
                    $diff['field']['create'][$tableName] = $new['field'][$tableName];
                    $diff['index']['create'][$tableName] = $new['index'][$tableName];
                } else {
                    //对比表
                    $oldDetail = $old['table'][$tableName];
                    $change = array();
                    if ($tableDetail['Engine'] !== $oldDetail['Engine'])
                        $change['Engine'] = $tableDetail['Engine'];
                    if ($tableDetail['Row_format'] !== $oldDetail['Row_format'])
                        $change['Row_format'] = $tableDetail['Row_format'];
                    if ($is_charrater) {
                        if ($tableDetail['Collation'] !== $oldDetail['Collation'])
                            $change['Collation'] = $tableDetail['Collation'];
                    }
                    //if($tableDetail['Create_options']!=$oldDetail['Create_options'])
                    //	$change['Create_options']=$tableDetail['Create_options'];
                    if ($tableDetail['Comment'] !== $oldDetail['Comment'])
                        $change['Comment'] = $tableDetail['Comment'];
                    if (!empty($change)) {
                        $diff['table']['change'][$tableName] = $change;
                    }
                }
            }
        }

        //index
        if (!empty($old['index'])) {
            foreach ($old['index'] as $table => $indexs) {
                if (isset($new['index'][$table])) {
                    $newIndexs = $new['index'][$table];
                    foreach ($indexs as $indexName => $indexDetail) {
                        if (!isset($newIndexs[$indexName])) {
                            //索引不存在，删除索引
                            $diff['index']['drop'][$table][$indexName] = $indexName;
                        }
                    }
                } else {
                    if (!isset($diff['table']['drop'][$table])) {
                        foreach ($indexs as $indexName => $indexDetail) {
                            $diff['index']['drop'][$table][$indexName] = $indexName;
                        }
                    }
                }
            }
        }
        if (!empty($new['index'])) {
            $is_charrater = $this->is_charrater();
            foreach ($new['index'] as $table => $indexs) {
                if (isset($old['index'][$table])) {
                    $oldIndexs = $old['index'][$table];
                    foreach ($indexs as $indexName => $indexDetail) {
                        if (isset($oldIndexs[$indexName])) {
                            //存在，对比内容
                            if (
                                $indexDetail['Non_unique'] !== $oldIndexs[$indexName]['Non_unique'] ||
                                $indexDetail['Column_name'] !== $oldIndexs[$indexName]['Column_name'] ||
                                ($is_charrater && $indexDetail['Collation'] !== $oldIndexs[$indexName]['Collation']) ||
                                $indexDetail['Index_type'] !== $oldIndexs[$indexName]['Index_type']
                            ) {
                                $diff['index']['drop'][$table][$indexName] = $indexName;
                                $diff['index']['add'][$table][$indexName] = $indexDetail;
                            }
                        } else {
                            //不存在，新建索引
                            $diff['index']['add'][$table][$indexName] = $indexDetail;
                        }
                    }
                } else {
                    if (!isset($diff['table']['create'][$table])) {
                        foreach ($indexs as $indexName => $indexDetail) {
                            $diff['index']['add'][$table][$indexName] = $indexDetail;
                        }
                    }
                }
            }
        }

        //fields
        if (!empty($old['field'])) {
            foreach ($old['field'] as $table => $fields) {
                if (isset($new['field'][$table])) {
                    $newFields = $new['field'][$table];
                    foreach ($fields as $fieldName => $fieldDetail) {
                        if (!isset($newFields[$fieldName])) {
                            //字段不存在，删除字段
                            $diff['field']['drop'][$table][$fieldName] = $fieldDetail;
                        }
                    }
                } else {
                    //旧数据库中的表在新数据库中不存在，需要删除
                }
            }
        }
        if (!empty($new['field'])) {
            $is_charrater = $this->is_charrater();
            foreach ($new['field'] as $table => $fields) {
                if (isset($old['field'][$table])) {
                    $oldFields = $old['field'][$table];
                    $lastField = '';
                    foreach ($fields as $fieldName => $fieldDetail) {
                        if (isset($oldFields[$fieldName])) {
                        	$oldField = $oldFields[$fieldName];
                            //字段存在，对比内容
                            if (
								$this->differ($fieldDetail['Type'], $oldField['Type'], true) ||
                                ($is_charrater && $this->differ($fieldDetail['Collation'], $oldField['Collation'])) ||
								$this->differ($fieldDetail['Null'], $oldField['Null']) ||
								$this->differ($fieldDetail['Default'], $oldField['Default']) ||
								$this->differ($fieldDetail['Extra'], $oldField['Extra']) ||
								$this->differ($fieldDetail['Comment'], $oldField['Comment'])
                            ) {
                                $diff['field']['change'][$table][$fieldName] = $fieldDetail;
                            }
                        } else {
                            //字段不存在，添加字段
                            $fieldDetail['After'] = $lastField;
                            $diff['field']['add'][$table][$fieldName] = $fieldDetail;
                        }
                        $lastField = $fieldName;
                    }
                } else {
                    //新数据库中的表在旧数据库中不存在，需要新建
                }
            }
        }

        return $diff;
    }

    protected function buildQuery($diff)
    {
        // TODO: Implement buildQuery() method.

        if (empty($diff['table']) && empty($diff['field']) && empty($diff['index'])) {
            return [0, "数据相同，无需同步操作！"];
        }

        $sqls = array();
        if ($diff) {
            if (isset($diff['table']['drop'])) {
                foreach ($diff['table']['drop'] as $tableName => $tableDetail) {
                    $sqls[$tableName]['__DROP__'][] = "DROP TABLE `{$tableName}`";
                }
            }
            if (isset($diff['table']['create'])) {
                foreach ($diff['table']['create'] as $tableName => $tableDetail) {
                    $fields = $diff['field']['create'][$tableName];
                    $sql = "CREATE TABLE `$tableName` (";
                    $t = array();
                    $k = array();
                    foreach ($fields as $field) {
                        $t[] = "`{$field['Field']}` " . strtoupper($field['Type']) . $this->sqlNull($field['Null']) . $this->sqlDefault($field['Default']) . $this->sqlExtra($field['Extra']) . $this->sqlComment($field['Comment']);
                    }
                    if (isset($diff['index']['create'][$tableName]) && !empty($diff['index']['create'][$tableName])) {
                        $indexs = $diff['index']['create'][$tableName];
                        foreach ($indexs as $indexName => $indexDetail) {
                            if ($indexName == 'PRIMARY')
                                $k[] = "PRIMARY KEY (`" . implode('`,`', $indexDetail['Column_name']) . "`)";
                            else
                                $k[] = ($indexDetail['Non_unique'] == 0 ? "UNIQUE" : "INDEX") . "`$indexName`" . " (`" . implode('`,`', $indexDetail['Column_name']) . "`)";
                        }
                    }
                    list($charset) = explode('_', $tableDetail['Collation']);
                    $sql .= implode(', ', $t) . (!empty($k) ? ',' . implode(', ', $k) : '') . ') ENGINE = ' . $tableDetail['Engine'] . ' DEFAULT CHARSET = ' . $charset;
                    $sqls[$tableName]['__CREATE__'][] = $sql;
                }
            }
            if (isset($diff['table']['change'])) {
                foreach ($diff['table']['change'] as $tableName => $table_changes) {
                    if (!empty($table_changes)) {
                        $sql = "ALTER TABLE `$tableName`";
                        $inc = 0;
                        foreach ($table_changes as $option => $value) {
                            if ($option == 'Collation') {
                                if ($this->is_charrater()) {
                                    list($charset) = explode('_', $value);
                                    $sql .= " DEFAULT CHARACTER SET $charset COLLATE $value";
                                    $inc ++;
                                }
                            } else {
                                $sql .= " " . strtoupper($option) . " = '$value' ";
                                $inc ++;
                            }
                        }
                        if ($inc > 0) {
                            $sqls[$tableName]['__CHANGE__'][] = $sql;
                        }
                    }
                }
            }
            if (isset($diff['index']['drop'])) {
                foreach ($diff['index']['drop'] as $tableName => $indexs) {
                    foreach ($indexs as $indexName => $indexDetail) {
                        if ($indexName == 'PRIMARY')
                            $sqls[$tableName]['__DROP__'][] = "ALTER TABLE `$tableName` DROP PRIMARY KEY";
                        else
                            $sqls[$tableName]['__DROP__'][] = "ALTER TABLE `$tableName` DROP INDEX `$indexName`";
                    }
                }
            }
            if (isset($diff['field']['drop'])) {
                foreach ($diff['field']['drop'] as $tableName => $fields) {
                    foreach ($fields as $fieldName => $fieldDetail) {
                        $sqls[$tableName]['__DROP__'][] = "ALTER TABLE `$tableName` DROP `$fieldName`";
                    }
                }
            }
            if (isset($diff['field']['add'])) {
                foreach ($diff['field']['add'] as $tableName => $fields) {
                    foreach ($fields as $fieldName => $fieldDetail) {
                        $sqls[$tableName][$fieldName][] = "ALTER TABLE `$tableName` ADD `{$fieldName}` " . strtoupper($fieldDetail['Type']) . $this->sqlCol($fieldDetail['Collation']) . $this->sqlNull($fieldDetail['Null']) . $this->sqlDefault($fieldDetail['Default']) . $this->sqlExtra($fieldDetail['Extra']) . $this->sqlComment($fieldDetail['Comment']) . " AFTER `{$fieldDetail['After']}`";
                    }
                }
            }
            if (isset($diff['index']['add'])) {
                foreach ($diff['index']['add'] as $tableName => $indexs) {
                    foreach ($indexs as $indexName => $indexDetail) {
                        $fieldName = implode('`,`', $indexDetail['Column_name']);
                        if ($indexName == 'PRIMARY')
                            $sqls[$tableName][$fieldName][] = "ALTER TABLE `$tableName` ADD PRIMARY KEY (`" . $fieldName . "`)";
                        else
                            $sqls[$tableName][$fieldName][] = "ALTER TABLE `$tableName` ADD" . ($indexDetail['Non_unique'] == 0 ? " UNIQUE " : " INDEX ") . "`$indexName`" . " (`" . $fieldName . "`)";
                    }
                }
            }
            if (isset($diff['field']['change'])) {
                foreach ($diff['field']['change'] as $tableName => $fields) {
                    foreach ($fields as $fieldName => $fieldDetail) {
                        $sqls[$tableName]['__CHANGE__'][] = "ALTER TABLE `$tableName` CHANGE `{$fieldName}` `{$fieldName}` " . strtoupper($fieldDetail['Type']) . $this->sqlCol($fieldDetail['Collation']) . $this->sqlNull($fieldDetail['Null']) . $this->sqlDefault($fieldDetail['Default']) . $this->sqlExtra($fieldDetail['Extra']) . $this->sqlComment($fieldDetail['Comment']);
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
        if (empty($dbInfo) || !is_array($dbInfo)) return [0, "配置为空"];
        $host = $dbInfo['host'];
        $database = $dbInfo['database'];
        $username = $dbInfo['username'];
        if (empty($host) || empty($database) || empty($username)) return [0, "配置错误"];
        $conn = $this->conn;
        list($status, $info) = $this->linkedTest($conn);
        if (!$status) return [0, "{$database}:{$info}"];
        $db = DB::connection($conn);
        $version = $db->select("select version() as v")[0]->v;
        $serverSet = 'character_set_connection=utf8, character_set_results=utf8, character_set_client=binary';
        $serverSet .= $version > '5.0.1' ? ', sql_mode=\'\'' : '';
        $db->statement("SET {$serverSet}");

        $cotOk = 0;
        $cotNo = 0;
        $sqls = $this->sqls;
        $errors = [];
        $oks = [];
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

        $this->saveLogs($database . "/" . $conn, $oks, $errors);

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
                $vLen = count($v);
                if ($vLen > 1) { //处理主键合并代码
                    $sql = $v[0];
                    $sql = str_replace("AFTER ``", "", $sql);
                    $sql2 = $v[1];
                    if (!empty($sql2)) {
                        $add = "ADD";
                        $pos = strpos($sql2, $add);
                        if ($pos > 0) {
                            $sql2 = substr($sql2, $pos);
                            if (!empty($sql2)) {
                                $sql = str_replace("ADD", "ADD COLUMN", $sql);
                                $sql .= "FIRST, {$sql2}";
                            }
                        }
                    }
                } elseif ($vLen > 0) {
                    $sql = $v[0];
                }
                if (!empty($sql)) {
                    $retSqls[$key][$k] = $sql;
                }
            }
        }
        return $retSqls;
    }

    private function sqlCol($val)
    {
        if (!$this->is_charrater()) {
            return '';
        }
        switch ($val) {
            case null:
                return '';
            default:
                list($charset) = explode('_', $val);
                return ' CHARACTER SET ' . $charset . ' COLLATE ' . $val;
        }
    }

    private function sqlDefault($val)
    {
        if ($val === null) {
            return '';
        } else {
            return " DEFAULT '" . stripslashes($val) . "'";
        }
    }

    private function sqlNull($val)
    {
        switch ($val) {
            case 'NO':
                return ' NOT NULL';
            case 'YES':
                return ' NULL';
            default:
                return '';
        }
    }

    private function sqlExtra($val)
    {
        switch ($val) {
            case '':
                return '';
            default:
                return ' ' . strtoupper($val);
        }
    }

    private function sqlComment($val)
    {
        switch ($val) {
            case '':
                return '';
            default:
                return " COMMENT '" . stripslashes($val) . "'";
        }
    }
}
