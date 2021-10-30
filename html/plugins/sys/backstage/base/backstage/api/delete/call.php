<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

use Tphp\Basic\Tpl\Init as TplInit;

return new class
{
    public static $plu;

    /**
     * @param $apiObj
     * @param $info
     * @param $values
     * @param array $retKvs
     * @return array
     */
    private function getTreePks($apiObj, $info, $values, &$retKvs = [])
    {
        if (!empty($values) && !empty($info)) {
            $db = $apiObj->tplInit->db("", $apiObj->defaultConn);
            $parent = $info['parent'];
            $child = $info['child'];
            $tmpKvs = $db->whereIn($parent, $values)->get();
            if (!empty($tmpKvs)) {
                $tmpVals = [];
                foreach ($tmpKvs as $key => $val) {
                    $ch = $apiObj->keyToLower($val)[$child];
                    $retKvs[] = $ch;
                    $tmpVals[] = $ch;
                }
                if (!empty($tmpVals)) {
                    return $this->getTreePks($apiObj, $info, $tmpVals, $retKvs);
                }
            }
        }
        return $retKvs;
    }

    /**
     * 删除关联表内容
    /**
     * @param $apiObj
     * @param $dbInfo
     * @param $pkList
     */
    private function deletesConfigLinkTable($apiObj, $dbInfo, $pkList)
    {
        if (empty($dbInfo) || empty($pkList)) return;
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
        $dbDel = $apiObj->tplInit->db($tableName, $connName);

        foreach ($pkList as $key => $val) {
            $dbDel->orWhereIn($key, $val);
        }
        try {
            $dbDel->delete();
            if ($db->count() <= 0) {
                $db->truncate();
            }
        } catch (\Exception $e) {
            $apiObj->__exitError($e->getMessage());
        }
    }

    private function config($apiObj, $type = "one")
    {
        $ttc = $apiObj->tplInit->config;
        $ttcType = strtolower($ttc['type']);
        if ($ttcType == 'api') {
            return false;
        }
        if (!in_array($ttcType, TplInit::$dataTypeList) || !isset($ttc['config']) || empty($ttc['config']['table'])) {
            return false;
        }

        if (!$apiObj->tplInit->isPost()) $apiObj->__exitError("500 ERROR");
        $data = $_POST['data'];
        if (empty($data) && !is_array($data)) $apiObj->__exitError("无数据传递！");
        $db = $apiObj->tplInit->db("", $apiObj->defaultConn);
        $wi = 0;
        $pkArr = [];
        $pkKv = [];
        $delete = $apiObj->vimConfigDelete;
        foreach ($data as $key => $val) {
            $vArr = json_decode($val, true);
            if (is_array($vArr)) {
                foreach ($vArr as $k => $v) {
                    if (!empty($k)) {
                        $db->orWhere($k, $v);
                        $wi++;
                        $pkArr[] = [
                            $k => $v
                        ];
                        $pkKv[$k][] = $v;
                    }
                }
            }
        }

        $tree = $apiObj->vimConfigTree;
        if (is_array($tree) && !empty($tree)) {
            $parent = strtolower(trim($tree['parent']));
            $child = strtolower(trim($tree['child']));
            $tree['parent'] = $parent;
            $tree['child'] = $child;
            $pkArr = $apiObj->callCommands("getTreePks", $tree, $pkArr);
            $values = [];
            foreach ($pkArr as $pa) {
                $values[] = $pa[$child];
            }

            if (!empty($values)) {
                $kvs = $this->getTreePks($apiObj, $tree, $values);
                if (!empty($kvs)) {
                    foreach ($kvs as $val) {
                        $db->orWhere($child, $val);
                        $wi++;
                    }
                }
            }
        }

        $dbList = json_decode(json_encode($db->get(), true), true);
        $deletePk = [];
        foreach ($dbList as $key => $val) {
            foreach ($delete as $k => $v) {
                foreach ($v[1] as $kk => $vv) {
                    $deletePk[$v[0]][$vv][] = $val[$kk];
                }
            }
        }
        //删除关联数据库
        if (!empty($delete) && is_array($delete) && !empty($pkKv)) {
            foreach ($delete as $key => $val) {
                if (empty($val) || !is_array($val) || count($val) < 2 || !is_array($val[1])) continue;
                $this->deletesConfigLinkTable($apiObj, $val[0], $deletePk[$val[0]]);
            }
        }

        if ($wi <= 0) $apiObj->__exitError("数据操作失败！");

        try {
            $status = $db->delete();
            if ($status > 0) {
                $dbCot = $apiObj->tplInit->db("", $apiObj->defaultConn);
                $message = "";
                if ($dbCot->count() <= 0) {
                    try {
                        $dbCot->truncate();
                    } catch (Exception $e) {
                        $message = "<br>" . $e->getMessage();
                    }
                }
                if ($type == "all" || !empty($tree)) {
                    EXITJSON(1, "删除成功，删除数据数量({$status})！{$message}");
                } else {
                    EXITJSON(1, "删除成功！{$message}");
                }
            } else {
                $apiObj->__exitError("删除失败！");
            }
        } catch (Exception $e) {
            EXITJSON(1, $e->getMessage());
        }
    }

    /**
     * 单个删除
     * @param $apiObj
     */
    public function delete($apiObj)
    {
        $this->config($apiObj, 'one');
    }

    /**
     * 多个删除
     * @param $apiObj
     */
    public function deletes($apiObj)
    {
        $this->config($apiObj, 'all');
    }

};