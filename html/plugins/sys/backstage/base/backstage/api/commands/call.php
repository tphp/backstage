<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

use Tphp\Basic\Tpl\Init as TplInit;

use Tphp\Config as TphpConfig;

return new class
{
    /**
     * 获取用户登录信息
     * @param $apiObj
     * @param $vim
     * @return mixed
     */
    public function getUserInfo($apiObj, $vim)
    {
        $tplInit = $apiObj->tplInit;
        $userInfo = $vim->userInfo;
        if (empty($userInfo)) {
            $userInfo = $tplInit->userInfo;
            if (empty($userInfo) && TphpConfig::$domain['backstage'] && TplInit::$isRoot) {
                $backstageArrow = TphpConfig::$domain['backstagearrow'];
                if (empty($backstageArrow) || !is_array($backstageArrow)) {
                    $backstageArrow = [];
                }
                $backstageArrow[] = 'sys/user/login';
                $backstageArrow[] = 'sys/user/login/captcha';
                if (!in_array($tplInit->tplInit, $backstageArrow)) {
                    if (count($_POST) > 0) {
                        EXITJSON(0, "登录超时， 请重新登录！");
                    } else {
                        redirect("/sys/user/login")->send();
                    }
                }
            }
        }
        return $userInfo;
    }

    /**
     * 获取主键 md5
     * @param $apiObj
     * @param $list
     * @return array
     */
    public function getPkMd5s($apiObj, $list)
    {
        if (!is_array($list)) return [];
        if ($apiObj->tplInit->config['type'] == 'dir') {
            $pks = [
                'id'
            ];
        } else {
            $pks = $apiObj->getPks();
        }

        if (empty($pks)) {
            return [];
        }
        
        $retList = [];
        foreach ($list as $key => $val) {
            $tpks = [];
            foreach ($pks as $v) {
                $tpks[$v] = $val[$v];
            }
            $tpkstr = json_encode($tpks, true);
            $retList[$key] = [
                'pk' => $tpkstr,
                'md5' => substr(md5($tpkstr), 8, 8)
            ];
        }
        return $retList;
    }


    /**
     * 设置子节点数量
     * @param $apiObj
     * @param $tree
     * @param $srcData
     */
    public function setTreeChildCount($apiObj, $tree, &$srcData)
    {
        if ($apiObj->tplInit->config['type'] == 'dir') {
            return;
        }

        $cIds = [];
        $c = strtolower($tree['child']);
        $p = strtolower($tree['parent']);
        if ($srcData === null) {
            return;
        }
        foreach ($srcData as $key => $val) {
            $cIds[] = $val[$c];
        }
        $countList = $apiObj->tplInit->db("", $apiObj->defaultConn)->select(\DB::connection($apiObj->defaultConn)->raw("count(*) as count, {$p}"))->whereIn($p, $cIds)->groupBy($p)->get();
        $countKv = [];
        foreach ($countList as $key => $val) {
            $val = $apiObj->tplInit->keyToLower($val);
            $countKv[$val[$p]] = $val['count'];
        }
        foreach ($srcData as $key => $val) {
            if (isset($countKv[$val[$c]])) {
                $child = $countKv[$val[$c]];
            } else {
                $child = 0;
            }
            $srcData[$key]['@child'] = $child;
        }
    }

    /**
     * 验证from关联
     * @param $apiObj
     * @param $from
     * @return array|null
     */
    public function getFromCheck($apiObj, $from)
    {
        list($vTable, $vThis, $vlink) = $from[3];
        if (is_array($vThis) && count($vThis) > 1 && is_array($vlink) && count($vlink) > 1) {
            $defConn = $apiObj->defaultConn;
            $defTable = $apiObj->vim->config['config']['table'];
            if (is_function($defTable)) {
                $defTable = $defTable();
            }
            $allField = $apiObj->allField;
            if (is_array($vTable)) {
                list($vTable, $vConn) = $vTable;
            }
            if (empty($vConn)) {
                $vConn = $defConn;
            }
            list($vTable, $vConn) = $apiObj->sqlInit->getPluginTable($vTable, $vConn, $apiObj->tplInit);

            //判断关联表是否有效
            $vTableinfo = $apiObj->tplInit->tableInfo($vConn, $vTable);
            if (empty($vTableinfo)) {
                $apiObj->__exitError("{$vConn}->{$vTable}不存在！");
            }

            //关联表与本表字段对应关系
            list($vThisKey, $vThisField) = $vThis;
            $vThisKey = strtolower(trim($vThisKey));
            $vThisField = strtolower(trim($vThisField));
            if (!isset($allField[$vThisKey])) {
                $apiObj->__exitError("{$defConn}->{$defTable}字段{$vThisKey}不存在！");
            }

            if (!isset($vTableinfo[$vThisField])) {
                $apiObj->__exitError("{$vConn}->{$vTable}字段{$vThisField}不存在！");
            }

            //关联表与属性表对应关系
            list($vLinkKey, $vLinkField) = $vlink;
            $vLinkKey = strtolower(trim($vLinkKey));
            $vLinkField = strtolower(trim($vLinkField));

            $bTable = $from[0];
            if (is_array($bTable)) {
                list($bTable, $bConn) = $bTable;
            }
            if (empty($bConn)) {
                $bConn = $defConn;
            }
            list($bTable, $bConn) = $apiObj->sqlInit->getPluginTable($bTable, $bConn, $apiObj->tplInit);

            $bTableinfo = $apiObj->tplInit->tableInfo($bConn, $bTable);
            if (empty($bTableinfo)) {
                $apiObj->__exitError("{$bConn}->{$bTable}不存在！");
            }

            if (!isset($bTableinfo[$vLinkKey])) {
                $apiObj->__exitError("{$bConn}->{$bTable}字段{$vLinkKey}不存在！");
            }

            if (!isset($vTableinfo[$vLinkField])) {
                $apiObj->__exitError("{$vConn}->{$vTable}字段{$vLinkField}不存在！");
            }

            return [
                'default' => [
                    'table' => $defTable,
                    'conn' => $defConn,
                    'key' => $vThisKey,
                ],
                'this' => [
                    'table' => $bTable,
                    'conn' => $bConn,
                    'name' => $from[2],
                    'key' => $vLinkKey,
                ],
                'link' => [
                    'table' => $vTable,
                    'conn' => $vConn,
                    'default_key' => $vThisField,
                    'this_key' => $vLinkField
                ]
            ];
            //查询关联表数据
//            $vArr = explode(",", $value);
//            $vList = $apiObj->tplInit->db($vTable, $vConn)->get();
        } else {
            return null;
        }
    }

    /**
     * 获取关联层级数据
     * @param $apiObj
     * @param $tree
     * @param $values
     * @param array $notValues
     * @param bool $isTree
     * @param array $retList
     * @return array
     */
    private function getTreeListForDatabase($apiObj, $tree, $values, $notValues = [], $isTree = true, &$retList = [])
    {
        if (empty($tree['name'])) {
            $operTitle = $apiObj->operTitle;
        } else {
            $operTitle = $tree['name'];
        }
        $operTitle = strtolower($operTitle);
        $child = strtolower($tree['child']);
        $parent = strtolower($tree['parent']);
        $table = $tree['table'];
        if (is_array($table)) {
            list($table, $conn) = $table;
        } else {
            $conn = "";
        }
        empty($conn) && $conn = $apiObj->defaultConn;

        $table = strtolower($table);
        empty($table) && $table = "";
        if (is_null($values)) {
            $values = $tree['value'] ?? '0';
        }
        $db = $apiObj->tplInit->db($table, $conn)->select($child, $parent, $operTitle)->where($parent, $values);
        if (!empty($notValues)) $db->whereNotIn($child, $notValues);

        if ((empty($conn) || $conn == $apiObj->defaultConn) && (empty($table) || $table == $apiObj->defaultTable)) {
            $order = $apiObj->retConfig['config']['order'];
            if (!empty($order)) {
                foreach ($order as $key => $val) {
                    $db->orderBy($key, $val);
                }
            }
        }

        $treeSort = $tree['sort'];
        if (!empty($treeSort) && is_array($treeSort)) {
            if (is_string($treeSort[0]) && is_string($treeSort[1])) {
                $db->orderBy($treeSort[0], $treeSort[1]);
            } else {
                foreach ($treeSort as $key => $val) {
                    if (is_string($key) && is_string($val)) {
                        $db->orderBy($key, $val);
                    }
                }
            }
        }

        $childList = $db->get();
        $childs = [];
        foreach ($childList as $key => $val) {
            $val = $apiObj->keyToLower($val);
            $childs[] = $val[$child];
            $retList[$values]['list'][] = [
                'key' => $val[$child],
                'value' => $val[$operTitle] ?? $val[$child]
            ];
        }

        $db2 = $apiObj->tplInit->db($table, $conn)->select(\DB::connection($apiObj->defaultConn)->raw("count(*) as count, {$parent}"))
            ->whereIn($parent, $childs);
        if (!empty($notValues)) $db2->whereNotIn($child, $notValues);
        $grouplist = $db2->groupBy($parent)->get();
        foreach ($grouplist as $key => $val) {
            $val = $apiObj->keyToLower($val);
            $retList[$values]['listmore'][$val[$parent]] = $val['count'];
        }

        if ($values == $tree['value']) {
            $retList[$values]['name'] = "顶级";
        } else {
            $parentinfo = $apiObj->tplInit->db($table, $conn)->select($child, $parent, $operTitle)->where($child, $values)->first();
            if (empty($parentinfo)) {
                $retList[$values]['name'] = "顶级";
            } else {
                $parentinfo = $apiObj->keyToLower($parentinfo);
                $nextvalues = trim($parentinfo[$parent]);
                $retList[$values]['name'] = "请选择";
                if ($isTree && $nextvalues != "") return $this->getTreeListForDatabase($apiObj, $tree, $nextvalues, $notValues, $isTree, $retList);
            }
        }
        return $retList;
    }

    /**
     * 获取关联层级数据
     * @param $apiObj
     * @param $tree
     * @param $values
     * @param array $notValues
     * @param bool $isTree
     * @param array $retList
     * @return array
     */
    private function getTreeListForDir($apiObj, $tree, $values, $notValues = [], $isTree = true, &$retList = [])
    {
        $treeDir = $tree['dir'];
        if (empty($tree['name'])) {
            $operTitle = $apiObj->operTitle;
        } else {
            $operTitle = $tree['name'];
        }
        $operTitle = strtolower($operTitle);

        $list = TphpConfig::$domainPath->plu->call('backstage.http.dir:getDirList', $treeDir, $values, [$operTitle], $notValues, $isTree);

        $retList = [];
        foreach ($list as $lst) {
            $parentId = $lst['parent_id'];
            if (!isset($retList[$parentId])) {
                if (empty($parentId)) {
                    $name = "顶级";
                } else {
                    $name = "请选择";
                }

                $retList[$parentId] = [
                    'name' => $name,
                    'listmore' => [],
                    'list' => []
                ];
            }

            $retList[$parentId]['list'][] = [
                'key' => $lst['id'],
                'value' => $lst[$operTitle] ?? $lst['id']
            ];

            if ($lst['@child'] > 0) {
                $retList[$parentId]['listmore'][$lst['id']] = $lst['@child'];
            }
        }

        return $retList;
    }

    /**
     * 获取关联层级数据
     * @param $apiObj
     * @param $tree
     * @param $values
     * @param array $notValues
     * @param bool $isTree
     * @param array $retList
     * @return array
     */
    public function getTreeList($apiObj, $tree, $values, $notValues = [], $isTree = true, &$retList = [])
    {
        $typType = $apiObj->tplInit->config['type'];
        $isDir = false;
        $treeDir = $tree['dir'];
        if (isset($treeDir) && is_string($treeDir)) {
            $isDir = true;
            if (!is_dir($treeDir)) {
                return [];
            }
        }

        if ($isDir) {
            return $this->getTreeListForDir($apiObj, $tree, $values, $notValues, $isTree, $retList);
        }

        $retList = $this->getTreeListForDatabase($apiObj, $tree, $values, $notValues, $isTree, $retList);
        if (count($retList) == 1 && isset($tree['value'])) {
            $info = array_values($retList)[0];
            if (count($info) == 1) {
                $retList = [];
                $retList = $this->getTreeListForDatabase($apiObj, $tree, $tree['value'], $notValues, $isTree, $retList);
            }
        }
        return $retList;
    }

    /**
     * 获取关联父值
     * @param $apiObj
     * @param $tree
     * @param $pkArr
     * @return string
     */
    public function getTreePks($apiObj, $tree, $pkArr)
    {

        if (empty($pkArr) || !is_array($pkArr)) {
            return [];
        }

        $child = strtolower($tree['child']);
        $pv = $pkArr[0][$child];
        if (isset($pv)) {
            return $pkArr;
        }

        $table = $tree['table'];
        if (isset($table)) {
            if (is_array($table)) {
                list($table, $conn) = $table;
            } else {
                $conn = "";
            }
        } else {
            $tConfig = $apiObj->tplInit->config['config'];
            if (!isset($tConfig)) {
                return [];
            }

            $table = $tConfig['table'];
            if (!is_string($table)) {
                $table = '';
            }
            $conn = $tConfig['conn'];
            if (!is_string($conn)) {
                $conn = '';
            }
        }
        empty($conn) && $conn = $apiObj->defaultConn;

        $table = strtolower($table);
        empty($table) && $table = "";

        $tableInfo = $apiObj->tplInit->tableInfo($conn, $table);

        $wheres = [];
        foreach ($pkArr as $key => $val) {
            foreach ($val as $k => $v) {
                $k = strtolower(trim($k));
                if (!isset($tableInfo[$k])) {
                    continue;
                }

                if (!isset($wheres[$k])) {
                    $wheres[$k] = [];
                }

                $wheres[$k][] = $v;
            }
        }

        if (empty($wheres)) {
            return [];
        }

        $db = $apiObj->tplInit->db($table, $conn)->select($child);
        foreach ($wheres as $k => $v) {
            $db->whereIn($k, $v);
        }

        $list = $db->get();

        $ret = [];
        foreach ($list as $val) {
            $ret[] = [
                $child => $val->$child
            ];
        }

        return $ret;
    }

};
