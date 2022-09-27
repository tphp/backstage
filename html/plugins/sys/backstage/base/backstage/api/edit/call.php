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
     * 设置数据
     * @param $apiObj
     */
    protected function editConfigSetData($apiObj)
    {
        $type = $apiObj->tplInit->tplType;
        $vch = $apiObj->vimConfigHandle;
        $vcf = $apiObj->vimConfigField;
        $afd = $apiObj->allField;
        $isPost = $apiObj->tplInit->isPost();
        $isPost && $data = &$_POST;
        $fieldUnsets = [];

        $isDir = $apiObj->vim->config['type'] == 'dir';
        $dateVch = $vch;
        if (!is_array($dateVch)) {
            $dateVch = [];
        }
        if (is_array($vcf)) {
            foreach ($vcf as $key => $val) {
                if (in_array($val['type'], ['create_time', 'update_time', 'time'])) {
                    if (!isset($dateVch[$key])) {
                        $dateVch[$key] = $val;
                    }
                } elseif (empty($val['type']) && in_array($key, ['create_time', 'update_time'])) {
                    if (!isset($dateVch[$key])) {
                        $dateVch[$key] = $val;
                    }
                }
            }
        }

        $increment = null;
        if (!empty($dateVch) && is_array($dateVch)) {
            foreach ($dateVch as $key => $val) {
                $keyName = $key;
                if (!is_string($keyName)) {
                    is_string($val) && $keyName = $val;
                }
                if (isset($val['type'])) {
                    $t = $val['type'];
                } elseif (isset($vcf[$keyName]['type'])) {
                    $t = $vcf[$keyName]['type'];
                } elseif (in_array($keyName, ['create_time', 'update_time', 'time'])) {
                    $t = $keyName;
                } else {
                    $t = 'text';
                }

                if ($val['increment'] && empty($increment)) {
                    $increment = $keyName;
                }

                if (strpos($afd[$keyName]['type'], 'int') === false) {
                    $isInt = false;
                } else {
                    $isInt = true;
                }

                if (in_array($t, ['create_time', 'update_time'])) {
                    $fieldUnsets[] = $keyName;
                    if ($isPost && !$isDir) {
                        if ($isInt) {
                            $time = time();
                        } else {
                            $time = date("Y-m-d H:i:s");
                        }
                        if ($type == 'add') {
                            $data[$keyName] = $time;
                        } else {
                            $t == 'update_time' && $data[$keyName] = $time;
                        }
                    }
                } elseif ($t == 'time') {
                    if ($isInt) {
                        $dk = $data[$keyName];
                        if (isset($dk)) {
                            if (empty($dk)) {
                                $data[$keyName] = 0;
                            } else {
                                $data[$keyName] = strtotime($dk);
                            }
                        }
                    }
                } elseif ($t == 'password') {
                    if ($isPost && empty($data[$keyName]) && $val['md5']) {
                        unset($data[$keyName]);
                    }
                } elseif ($val['view'] || $isInt) {
                    if (empty($data[$keyName]) && $data[$keyName] . "" != '0') {
                        unset($data[$keyName]);
                    }
                } elseif (in_array($t, ['bind', 'extends'])) {
                    unset($data["_#{$keyName}#_"]);
                }
            }
        }

        if ($type == 'add') {
            if (!empty($increment) && isset($afd[$increment])) {
                $info = $apiObj->tplInit->db()->select($increment)->orderBy($increment, 'desc')->first();
                if (empty($info)) {
                    $data[$increment] = 1;
                } else {
                    $data[$increment] = $info->$increment + 1;
                }
            }
        }
        $apiObj->fieldUnsets = $fieldUnsets;
    }


    /**
     * 获取随机字符串
     * @param int $length
     * @param string $char
     * @return bool|string
     */
    private function strRand($length = 5, $char = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
    {
        $string = '';
        for ($i = $length; $i > 0; $i--) {
            $string .= $char[mt_rand(0, strlen($char) - 1)];
        }
        return $string;
    }

    /**
     * 获取提交数据
     * @param $apiObj
     * @return array
     */
    protected function getEditPostData($apiObj)
    {
        $pData = $_POST;
        $vh = $apiObj->vimConfigHandle;
        if (!is_array($vh)) return $pData;
        $dt = [];
        $data = [];
        $vConf = $apiObj->vConfig;
        $afd = $apiObj->allField;
        foreach ($pData as $key => $val) {
            $vFrom = $vConf[$key]['from'];
            !isset($vFrom) && isset($vh[$key]) && isset($vh[$key]['from']) && $vFrom = $vh[$key]['from'];
            if (isset($vFrom[3])) {
                unset($afd[$key]);
                $gfc = $apiObj->callCommands("getFromCheck", $vFrom);
                if (!empty($gfc)) {
                    $data[$key] = [
                        'info' => $gfc,
                        'value' => $val
                    ];
                }
            } else {
                $dKey = "";
                if (is_array($vFrom[1]) && count($vFrom[1]) > 1) {
                    $dKey = $vFrom[1][1];
                } elseif (!empty($vFrom[1]) && is_string($vFrom[1])) {
                    $dKey = $vFrom[1];
                }
                if (!empty($dKey)) {
                    $dv = $data[$dKey];
                    if (!empty($dv)) {
                        if (empty($val)) {
                            $val = $dv;
                        } else {
                            $val = $dv . "," . $val;
                        }
                        $valArr = explode(",", $val);
                        $val = implode(",", array_unique($valArr));
                    }
                }
                $data[$key] = $val;
            }
        }
        foreach ($vh as $key => $val) {
            $keyName = "";
            if (is_string($key)) {
                $keyName = $key;
                if (is_array($val)) {
                    foreach ($val as $k => $v) {
                        !isset($vConf[$key][$k]) && $vConf[$key][$k] = $v;
                    }
                }
            } elseif (is_string($val)) {
                $keyName = $val;
                !isset($dt[$keyName]) && $dt[$keyName] = $data[$keyName];
                continue;
            }
            if (empty($keyName)) continue;
            $json = $val['json'];
            if (empty($json) || !is_string($json)) {
                !isset($dt[$keyName]) && $dt[$keyName] = $data[$keyName];
                continue;
            }
            if (isset($dt[$json]) && !is_array($dt[$json])) {
                unset($dt[$json]);
                $dt[$json] = [];
            }
            if ($data[$keyName] == 0 || !empty($data[$keyName])) $dt[$json][$keyName] = $data[$keyName];
        }
        $realData = [];
        $extData = []; //配置中其他数据
        foreach ($data as $key => $val) {
            if (isset($afd[$key])) {
                if (!isset($dt[$key])) {
                    $dt[$key] = $val;
                }
            } elseif (isset($vConf[$key])) {
                $extData[$key] = $val;
            }
        }
        foreach ($dt as $key => $val) {
            if (isset($val)) {
                if (isset($afd[$key])) {
                    $tp = $vConf[$key]['type'];
                    if (is_array($val)) {
                        if (in_array($tp, ['select', 'selects', 'checkbox', 'trees'])) {
                            $v = implode(",", $val);
                        } else {
                            $v = json_encode($val, true);
                        }
                    } else {
                        empty($val) && in_array($tp, ['select', 'selects', 'checkbox', 'trees']) && $val = "";
                        $v = $val;
                    }

                    $isset = true;
                    if (isset($vh[$key])) {
                        $vhv = $vh[$key];
                        if (is_array($vhv)) {
                            if (empty($v)) {
                                !isset($v) && $isset = false;
                            } else {
                                if ($vhv['md5']) {
                                    // 优先md5加密
                                    if (empty($vhv['salt']) || !isset($afd[$vhv['salt']])) {
                                        $v = md5($v);
                                    } else {
                                        $salt = $this->strRand();
                                        $realData[$vhv['salt']] = $salt;
                                        $v = md5($v . $salt);
                                    }
                                } elseif ($vhv['aes']) {
                                    // aes加密
                                    if (!isset($xcrypto)) {
                                        $xcrypto = import('XCrypto');
                                    }
                                    $v = $xcrypto->aesEncrypt($v);
                                } elseif ($vhv['des']) {
                                    // des加密
                                    if (!isset($xcrypto)) {
                                        $xcrypto = import('XCrypto');
                                    }
                                    $v = $xcrypto->desEncrypt($v);
                                }
                            }
                        }
                    }
                    $isset && $realData[$key] = $v;
                } elseif (is_array($val) && isset($val['info']) && isset($val['value'])) {
                    $realData[$key] = $val;
                }
            }
        }
        return [$realData, $extData];
    }

    /**
     * html 配置
     * @param $apiObj
     * @return mixed
     */
    public function config($apiObj)
    {
        $config = $apiObj->retConfig;
        $ttc = $apiObj->tplInit->config;
        $ttcType = strtolower($ttc['type']);
        if (!in_array($ttcType, TplInit::$dataTypeList) || !isset($ttc['config'])) {
            if ($ttcType == 'dir') {
                if (empty($ttc['config']['dir'])) {
                    return $config;
                }
            } else if (empty($ttc['config']['table'])) {
                return $config;
            }
        }

        $type = $apiObj->tplInit->tplType;
        if ($type === 'handle') {
            $type = 'edit';
        }
        $this->editConfigSetData($apiObj);
        $tFroms = [];
        if ($apiObj->tplInit->isPost()) {
            list($geData, $extData) = $this->getEditPostData($apiObj);
            if (empty($geData) && empty($extData)) {
                if ($ttcType == 'dir') {
                    return $config;
                }
                $apiObj->__exitError("没有数据传递");
            }
            if (!empty($geData) && in_array($type, ['add', 'edit'])) {
                $data = [];
                foreach ($geData as $key => $val) {
                    if (is_array($val) && isset($val['info']) && isset($val['value'])) {
                        $tFroms[$key] = $val;
                    } else {
                        $data[$key] = $val;
                    }
                }
                $config['config'][$type] = $data;
            }
        }
        if ($ttcType == 'dir') {
            return $config;
        }
        $apiObj->froms = $tFroms;
        $isEditBatch = false;
        if (!empty($_GET['pk'])) { //单列编辑
            $pkStr = $_GET['pk'];
        } elseif (!empty($_GET['pks'])) { //多列编辑
            $pkStr = $_GET['pks'];
            $isEditBatch = true;
        }

        $vhi = $apiObj->vimConfigHandleInit;
        if (!empty($vhi)) {
            if (!empty($pkStr)) {
                try {
                    $_pks = json_decode(json_decode($pkStr, true)[0], true);
                    if (!is_array($_pks)) {
                        $_pks = [];
                    }
                } catch (\Exception $e) {
                    $_pks = [];
                }
                foreach ($_pks as $key => $val) {
                    $vhi[$key] = $val;
                }
            }
            $pkStr = json_encode([json_encode($vhi, true)], true);
        }
        $pk = [];
        if (empty($pkStr)) {
            if ($type != 'add') $apiObj->__exitError("参数传递错误");
        } else {
            $pkTmp = json_decode($pkStr, true);
            if (empty($pkTmp) || !is_array($pkTmp)) {
                if ($type != 'add') $apiObj->__exitError("参数传递错误");
            }
            foreach ($pkTmp as $pkVal) {
                !empty($pkVal) && $pk[] = $pkVal;
            }
        }
        $this->pk = $pk;
        $wheres = [];

        // 编辑时排除当前路径
        $notWheres = [];
        if (!empty($pk) && $type !== 'add') {
            foreach ($pk as $key => $val) {
                if (!is_string($val)) continue;
                $vArr = json_decode($val, true);
                if (empty($vArr) && is_array($vArr)) continue;
                foreach ($vArr as $k => $v) {
                    $wheres[] = [$k, "=", $v];
                    $notWheres[$k] = $v;
                }
            }
        }

        $apiObj->whereNull = true;
        if (!empty($wheres)) {
            !($type == 'add' && $apiObj->tplInit->isPost()) && $config['config']['where'] = $wheres;
            $apiObj->whereNull = false;
            $config['pagesize'] = 10000;
        }
        $apiObj->isEditBatch = $isEditBatch;
        $vch = $apiObj->vimConfigHandle;
        $c = $config['config']['field'];
        empty($c) && $c = [];
        $keyWheres = [];
        $keyFieldNames = [];
        $vConfig = $apiObj->vConfig;
        if (!empty($vch) && is_array($vch)) {
            foreach ($vch as $key => $val) {
                if (is_string($key)) {
                    if (is_array($val)) {
                        if (in_array($val['type'], ['bind', 'extends'])) {
                            continue;
                        }
                        $c[] = $key;
                        if (!empty($val['json']) && is_string($val['json'])) {
                            $c[] = $val['json'];
                        }
                        if (isset($val['key']) && $val['key']) {
                            if (isset($data[$key])) {
                                $keyWheres[] = [$key, '=', $data[$key]];
                            } else {
                                $keyWheres[] = [$key, '='];
                            }

                            $kfName = $key;
                            if (is_array($vConfig[$key]) && !empty($vConfig[$key]['name'])) {
                                $kfName = $vConfig[$key]['name'];
                            }
                            $keyFieldNames[] = $kfName;
                        }
                    } else {
                        $c[] = $key;
                    }
                } elseif (is_string($val)) {
                    $c[] = $val;
                }
            }
        }

        if ($apiObj->tplInit->isPost()) {
            if (!empty($keyWheres)) {
                $keyDb = $apiObj->tplInit->db("", $apiObj->defaultConn);
                $kInfo = [];
                foreach ($keyWheres as $key => $val) {
                    if (count($val) <= 2) {
                        $k = strtolower($val[0]);
                        empty($kInfo) && $kInfo = $apiObj->tplInit->db("", $apiObj->defaultConn)->where($wheres)->first();
                        $kInfo = $apiObj->keyToLower($kInfo);
                        $keyWheres[$key][] = $kInfo[$k];
                    }
                }
                if (!empty($notWheres)) {
                    $nwKeys = [];
                    $nwValues = [];
                    $flag = $apiObj->getSqlFlag($keyDb);
                    foreach ($notWheres as $nwK => $nwV) {
                        $nwK = str_replace($flag, "", $nwK);
                        $nwKeys[] = "{$nwK} = ?";
                        $nwValues[] = $nwV;
                    }
                    $nwQuery = implode(" and ", $nwKeys);
                    $keyDb->whereRaw("not ($nwQuery)", $nwValues);
                }
                $keyDb->where($keyWheres);
                $fst = $keyDb->first();
                if (!empty($fst)) {
                    $str = "";
                    $fst = $apiObj->keyToLower($fst);
                    if (!empty($pk)) {
                        foreach ($pk as $val) {
                            $js = json_decode($val, true);
                            foreach ($js as $k => $v) {
                                $kName = $k;
                                if (is_array($vConfig[$k]) && !empty($vConfig[$k]['name'])) {
                                    $kName = $vConfig[$k]['name'];
                                }
                                $str .= " {$kName}=" . $fst[$k] . " ";
                            }
                        }
                    }
                    if (count($keyFieldNames) > 1) {
                        $apiObj->__exitError("字段组合 (" . implode("、", $keyFieldNames) . ") 不能重复，在" . $str . "中");
                    } else {
                        $apiObj->__exitError("字段 (" . $keyFieldNames[0] . ") 不能重复，在" . $str . "中");
                    }
                }
            }
        }

        $c = array_unique($c);
        $config['config']['field'] = $c;
        return $config;
    }

    /**
     * 设置操作功能配置
     * @param $apiObj
     * @param $handle
     * @param $vConfig
     * @param $fieldKey
     */
    protected function setHandleConfig($apiObj, &$handle, $vConfig, $fieldKey)
    {
        $editType = $apiObj->tplInit->tplType;
        $type = "";
        if (!is_array($vConfig)) {
            $vConfig = $handle;
        } elseif (is_array($handle)) {
            foreach ($handle as $key => $val) {
                !isset($vConfig[$key]) && $vConfig[$key] = $val;
            }
        }
        if ($vConfig['status']) {
            $type = "status";
            $handle['text'] = $vConfig['text'];
        } elseif (isset($vConfig['type'])) {
            $type = $vConfig['type'];
            if ($type == 'tree') {
                $data = $apiObj->data['_'];
                if (is_string($data)) $apiObj->__exitError($data);
                if (count($data) > 0 || $editType == 'add') {
                    $tree = $vConfig['tree'];
                    $notValues = [];
                    $isThisTable = false;
                    if (isset($tree['table'])) {
                        if (is_array($tree['table'])) {
                            list($treeTable, $conn) = $tree['table'];
                        } else {
                            $treeTable = $tree['table'];
                            $conn = "";
                        }
                        empty($conn) && $conn = $apiObj->defaultConn;

                        if ($apiObj->defaultTable == $treeTable) {
                            if (empty($conn)) {
                                $isThisTable = true;
                            } elseif ($conn == $apiObj->defaultConn) {
                                $isThisTable = true;
                            }
                        }
                    } else {
                        $isThisTable = true;
                    }
                    if ($editType == 'add') {
                        $parentValue = $tree['value'];
                        $pk = $_GET['pk'];
                        if (!empty($pk)) {
                            $pks = json_decode($pk, true);
                            if (count($pks) > 0) {
                                $pkArr = [];
                                foreach ($pks as $pkStr) {
                                    $pkArr[] = json_decode($pkStr, true);
                                }
                                if (!empty($pkArr)) {
                                    $tChild = $tree['child'];
                                    $pkArr = $apiObj->callCommands("getTreePks", $tree, $pkArr);
                                    if (!empty($pkArr) && isset($pkArr[0][$tChild])) {
                                        $parentValue = $pkArr[0][$tChild];
                                    }
                                }
                            }
                        }
                    } elseif ($isThisTable) {
                        $parentValue = $data[0][$tree['parent']];
                        foreach ($data as $key => $val) {
                            $notValues[] = $val[$tree['child']];
                        }
                    } else {
                        $parentValue = $apiObj->data['src'][0][$fieldKey];
                    }
                    $treeList = $apiObj->callCommands("getTreeList", $tree, $parentValue, $notValues);
                    $next = "";
                    foreach ($treeList as $key => $val) {
                        if ($next != "") $treeList[$key]['next'] = $next;
                        $next = $key;
                    }
                    $treeList = array_reverse($treeList, true);
                    $newList = [];
                    $i = 0;
                    foreach ($treeList as $key => $val) {
                        if ($i > 0) {
                            if (!empty($val['list'])) {
                                $newList[] = [
                                    'key' => $key,
                                    'list' => $val
                                ];
                            }
                        } else {
                            $newList[] = [
                                'key' => $key,
                                'list' => $val
                            ];
                        }
                        $i++;
                    }
                    $handle['text'] = $newList;
                    $handle['notValues'] = $notValues;
                }
            }
        }
        !empty($type) && $handle['type'] = $type;
    }

    /**
     * 设置操作功能
     * @param $apiObj
     */
    protected function setHandle($apiObj)
    {
        $vConfig = $apiObj->vConfig;
        if (!in_array(strtolower($apiObj->vim->config['type']), TplInit::$dataTypeList) && is_array($apiObj->dataExt)) {
            $apiObj->data = $apiObj->dataExt;
        }
        $handle = &$apiObj->vimConfig['handle'];
        if (empty($handle)) return;

        if (!empty($handle) && is_array($handle)) {
            foreach ($handle as $key => $val) {
                $this->setHandleConfig($apiObj, $handle[$key], $vConfig[$key], $key);
            }
        }
        $handles = &$apiObj->vimConfig['handles'];
        if (empty($handles)) return;

        foreach ($handles as $key => $val) {
            foreach ($val['field'] as $k => $v) {
                if (isset($handle[$k])) {
                    $handles[$key]['field'][$k] = $handle[$k];
                } else {
                    $this->setHandleConfig($apiObj, $handles[$key]['field'][$k], $vConfig[$k], $k);
                }
            }
        }
    }
    /**
     * 获取所有层级数据列表
     * @param $apiObj
     * @param $table
     * @param $parent
     * @param $child
     * @param $value
     * @param $name
     * @param $sort
     * @param $where
     * @param array $ret
     * @return array
     */
    private function getTreeListNext($apiObj, $table, $parent, $child, $value, $name, $sort, $where, &$ret = [])
    {
        $db = $apiObj->tplInit->db($table)->select($child, $parent, $name)->whereIn($parent, $value);
        if (!empty($sort)) {
            $db->orderBy($sort[0], $sort[1]);
        }
        if (!empty($where)) {
            $apiObj->tplInit->setWhere($db, $where, $apiObj->allField);
        }
        $list = $db->get();
        $values = [];
        foreach ($list as $key => $val) {
            $val = $apiObj->keyToLower($val);
            $ret[$val[$parent]][$val[$child]] = [
                'name' => $val[$name],
            ];
            $values[] = $val[$child];
        }
        if (empty($values)) return $ret;
        return $this->getTreeListNext($apiObj, $table, $parent, $child, $values, $name, $sort, $where, $ret);
    }

    /**
     * 处理层级数据
     * @param $list
     * @param $value
     * @param $arr
     * @param array $retList
     * @return array
     */
    private function getTreeListNextDeal(&$list, $value, $arr, &$retList = [])
    {
        if (empty($list[$value])) return $retList;
        $retList['list'] = $list[$value];
        unset($list[$value]);
        foreach ($retList['list'] as $key => $val) {
            if ($arr[$key]) {
                $retList['list'][$key]['checked'] = true;
            }
            $this->getTreeListNextDeal($list, $key, $arr, $retList['list'][$key]);
        }
        return $retList;
    }

    /**
     * 使JavaScript保持顺序不变
     * @param $odList
     * @param array $retList
     * @return array
     */
    private function getTreeListNextReturn($odList, &$retList = [])
    {
        $i = 0;
        if (!empty($odList) && is_array($odList)) {
            foreach ($odList as $key => $val) {
                $retList[$i]['key'] = $key;
                !empty($val['name']) && $retList[$i]['name'] = $val['name'];
                !empty($val['checked']) && $retList[$i]['checked'] = $val['checked'];
                if (!empty($val['list']) && is_array($val['list'])) {
                    $this->getTreeListNextReturn($val['list'], $retList[$i]['list']);
                }
                $i++;
            }
        }
        return $retList;
    }

    /**
     * 获取所有层级数据
     * @param $apiObj
     * @param $tree
     * @param array $data
     * @return array
     */
    private function getTreeListAll($apiObj, $tree, $data = [])
    {
        $child = strtolower($tree['child']);
        $parent = strtolower($tree['parent']);
        $table = strtolower($tree['table']);
        $name = strtolower($tree['name']);
        $value = $tree['value'];
        $sort = $tree['sort'];
        $retList = [];
        $endList = $tree['end'];
        empty($endList) && $endList = [];
        if (empty($parent) || empty($child) || empty($name)) return $retList;

        if (empty($table)) {
            if (empty($endList)) {
                return $retList;
            }
            $list = [];
        } else {
            if (is_array($table)) {
                list($table, $conn) = $table;
            } else {
                $conn = "";
            }
            empty($conn) && $conn = $apiObj->defaultConn;
            $tableInfo = $apiObj->tplInit->tableInfo($conn, $table);
            if (empty($tableInfo) || empty($tableInfo[$parent]) || empty($tableInfo[$child]) || empty($tableInfo[$name])) return $retList;
            if (empty($sort) || !is_array($sort)) {
                $sort = [];
            } elseif (is_string($sort)) {
                $sort = trim(strtolower($sort));
                if (empty($tableInfo[$sort])) {
                    $sort = [];
                } else {
                    $sort = [$sort, 'asc'];
                }
            } else {
                list($fd, $tp) = $sort;
                if (is_string($fd)) {
                    $fd = trim(strtolower($fd));
                    if (empty($fd) || empty($tableInfo[$fd])) {
                        $sort = [];
                    } else {
                        !is_string($tp) && $tp = "asc";
                        $tp != 'asc' && $tp = 'desc';
                        $sort = [$fd, $tp];
                    }
                } else {
                    $sort = [];
                }
            }
            $tWhere = $tree['where'];
            $where = $apiObj->vim->getWhereRealList($tWhere, $tableInfo);
            $list = $this->getTreeListNext($apiObj, $table, $parent, $child, [$value], $name, $sort, $where);
        }

        if (!empty($endList) && is_array($endList)) {
            foreach ($endList as $key => $val) {
                if (is_array($val)) {
                    if (isset($val[$parent]) && isset($val[$child]) && isset($val[$name])) {
                        $list[$val[$parent]][$val[$child]]['name'] = $val[$name];
                    }
                }
            }
        }

        if (empty($data)) $data = "";
        $arrTmp = explode(",", $data);
        $arr = [];
        foreach ($arrTmp as $key => $val) {
            $arr[$val] = true;
        }

        $odList = $this->getTreeListNextDeal($list, $value, $arr)['list'];
        $retList = $this->getTreeListNextReturn($odList);
        return $retList;
    }

    /**
     * 最后的配置
     * @param $apiObj
     */
    protected function editSetHandle($apiObj)
    {
        $type = $apiObj->tplInit->tplType;
        $this->setHandle($apiObj);
        if ($type == 'add') {
            $handle = $apiObj->vimConfig['handle'];
        } elseif ($type == 'copy') {
            $handle = $apiObj->vimConfig['handle'];
            if (empty($handle)) {
                $handle = [];
            } else {
                $srcId = $handle['id'];
                if (empty($srcId) || !is_array($srcId)) {
                    $handle = [];
                } else {
                    $srcId['name'] = '新文件夹';
                    $srcId['tree']['name'] = '新文件夹';
                }

                $handle = [
                    'src' => [
                        'name' => '原文件夹',
                        'view' => true
                    ],
                    'new' => $srcId
                ];
            }
        } else {
            if ($apiObj->isEditBatch) {
                if (isset($_GET['handle_id'])) {
                    $hid = $_GET['handle_id'];
                    empty($hid) && $hid = 0;
                    $vHandles = $apiObj->vimConfig['handles'][$hid]['field'];
                    !empty($vHandles) && $handle = $vHandles;
                }
            } else {
                $handle = $apiObj->vimConfig['handle'];
            }
        }

        if (!empty($handle) && is_array($handle)) {
            $vch = $apiObj->vimConfigHandle;
            $vcf = $apiObj->vimConfigField;
            $allField = $apiObj->allField;
            $data = $apiObj->data['_'][0];
            foreach ($handle as $key => $val) {
                $fd = [];
                $isOk = false;
                if (empty($val['type'])) unset($val['type']);
                if (is_array($vch) && is_array($vch[$key]) && !empty($vch[$key]['type'])) {
                    $val['type'] = $vch[$key]['type'];
                }
                if (isset($vch[$key])) {
                    if (is_string($vch[$key]) || isset($vch[$key]['name'])) {
                        $fd = $vch[$key];
                        $isOk = true;
                    }
                    if (is_array($vch[$key])) {
                        foreach ($vch[$key] as $k => $v) {
                            !isset($val[$k]) && $val[$k] = $v;
                        }
                    }
                }

                if (!$isOk) {
                    if (isset($vcf[$key])) {
                        unset($vcf[$key]['hidden']);
                        if (is_string($vcf[$key]) || isset($vcf[$key]['name'])) {
                            $fd = $vcf[$key];
                            $isOk = true;
                        }

                        if (is_array($vcf[$key])) {
                            foreach ($vcf[$key] as $k => $v) {
                                !isset($val[$k]) && $val[$k] = $v;
                            }
                        }
                    }
                }

                $inArray = [
                    'hidden', //隐藏文本
                    'label', //标签类型
                    'textarea', //文本域
                    'checkbox', //多选框
                    'radio', //单选框
                    'select', //下拉
                    'selects', //下拉组合
                    'sql', //数据库
                    'json', //JSON数据
                    'field', //字段类型数据
                    'tree', //树状结构
                    'trees', //树状结构多选框
                    'dir', //目录树状结构
                    'status', //状态
                    'article', //文章类型
                    'markdown', //MarkDown文档
                    'date', //日期控件
                    'time', //时间控件
                    'password', //密码
                    'segment', //分割线
                    'image', //上传图片
                    'file', //上传文件
                    'tpl', //模板
                    'html', //html源码
                    'bind', //绑定关系
                    'extends', //列表扩展
                ];
                if (empty($val['type']) || !in_array($val['type'], $inArray)) {
                    $val['type'] = 'text';
                }
                $handle[$key] = $val;

                if (!$isOk) {
                    if (!empty($allField[$key]['name'])) {
                        $fd = $allField[$key];
                        $isOk = true;
                    }
                }

                if (!$isOk) continue;
                if (isset($fd['name'])) {
                    $handle[$key]['name'] = $fd['name'];
                } elseif (is_string($fd)) {
                    $handle[$key]['name'] = $fd;
                }

                $tp = $val['type'];
                if ($tp == 'trees') {
                    if ($type == 'add') {
                        $handle[$key]['list'] = $this->getTreeListAll($apiObj, $val['tree']);
                    } else {
                        $handle[$key]['list'] = $this->getTreeListAll($apiObj, $val['tree'], $data[$key]);
                    }
                }
            }
        }
        $apiObj->showHandle = $handle;
    }

    /**
     * 获取表单实际值（存储在JSON中的转化）
     * @param $apiObj
     * @param $handle
     * @param $field
     * @return array
     */
    protected function getEditData($apiObj, $handle, $field)
    {
        $fd = [];
        $fdJson = [];
        if (!is_array($handle)) return $field;
        foreach ($handle as $key => $val) {
            $json = $val['json'];
            if (empty($json)) {
                if (isset($field[$key])) $fd[$key] = $field[$key];
            } else {
                $fdJson[$json][$key] = true;
            }
        }

        foreach ($fdJson as $key => $val) {
            if (isset($fd[$key])) unset($fd[$key]);
            if (isset($field[$key])) {
                $jsonArr = json_decode($field[$key], true);
                if (!empty($jsonArr) && is_array($jsonArr)) {
                    foreach ($jsonArr as $k => $v) {
                        $fd[$k] = $v;
                    }
                }
                unset($field[$key]);
            }
        }

        if (is_array($field)) {
            foreach ($field as $key => $val) {
                !isset($fd[$key]) && $fd[$key] = $val;
            }
        }

        $afd = $apiObj->allField;
        foreach ($fd as $key => $val) {
            if (isset($handle[$key]['set_value'])) {
                $fd[$key] = $handle[$key]['set_value'];
            } else {
                $tp = $handle[$key]['type'];
                if ($tp == 'time') {
                    $aType = $afd[$key]['type'];
                    if (empty($aType)) {
                        $isInt = false;
                    } else {
                        $isInt = strpos($aType, 'int') === false ? false : true;
                    }
                    if ($isInt) {
                        if (empty($val)) {
                            $val = time();
                        }
                        $fd[$key] = date("Y-m-d H:i:s", $val);
                    }
                } elseif ($tp == 'password') {
                    if ($handle[$key]['md5']) {
                        $fd[$key] = "";
                    } elseif ($handle[$key]['aes']) {
                        // aes解密
                        if (!isset($xcrypto)) {
                            $xcrypto = import('XCrypto');
                        }
                        $fd[$key] = $xcrypto->aesDecrypt($fd[$key]);
                    } elseif ($handle[$key]['des']) {
                        // des解密
                        if (!isset($xcrypto)) {
                            $xcrypto = import('XCrypto');
                        }
                        $fd[$key] = $xcrypto->desDecrypt($fd[$key]);
                    }
                } elseif (in_array($tp, ['select', 'selects', 'checkbox'])) {
                    if (empty($val)) {
                        $fKv = "";
                        if ($apiObj->tplInit->methodType == 'add' && in_array($tp, ['select', 'selects']) && strpos($afd[$key]['type'], 'int') !== false) {
                            $hk_list = $handle[$key]['list'];
                            if (is_array($hk_list)) {
                                foreach ($hk_list as $k => $v) {
                                    $fKv = $k;
                                    break;
                                }
                            }
                        }
                        $fd[$key] = $fKv;
                    }
                }
            }
        }
        // 强制转化的值
        $lastFd = $apiObj->tplInit->handleField;
        if (!empty($lastFd) && is_array($lastFd)) {
            foreach ($lastFd as $key => $val) {
                $fd[$key] = $val;
            }
        }

        $type = $apiObj->tplInit->tplType;
        if ($type == 'copy') {
            $fdId = $fd['id'];
            if (!empty($fdId)) {
                $fd['src'] = $fdId;
                $fd['new'] = $fdId;
            }
        }
        return $fd;
    }

    /**
     * 重设数据，筛选主键数据
     * @param $data
     * @param $src
     */
    private function resetData($data, $src)
    {
        $pk = $this->pk;
        if (empty($pk)) {
            return [$data, $src];
        }

        $pkArr = [];

        foreach ($pk as $_pk) {
            $pa = json_decode($_pk, true);
            if (!empty($pa)) {
                $pkArr[] = $pa;
            }
        }

        if (empty($pkArr)) {
            return [$data, $src];
        }

        $keys = [];

        foreach ($src as $key => $val) {
            foreach ($pkArr as $pa) {
                $isOk = true;
                foreach ($pa as $paKey => $paVal) {
                    if ($val[$paKey] != $paVal) {
                        $isOk = false;
                        break;
                    }
                }
                if ($isOk) {
                    $keys[] = $key;
                }
            }
        }

        $newData = [];
        $newSrc = [];

        foreach ($keys as $key) {
            $newData[] = $data[$key];
            $newSrc[] = $src[$key];
        }

        return [$newData, $newSrc];
    }

    /**
     * 获取变化的MD5键值
     * @param array $srcData
     * @return array
     */
    private function getReplacePks($srcData = [], $pks = [])
    {
        $ret = [];
        if (empty($srcData)) {
            return $ret;
        }

        if (!empty($_GET['pk'])) { //单列编辑
            $pkStr = $_GET['pk'];
        } elseif (!empty($_GET['pks'])) { //多列编辑
            $pkStr = $_GET['pks'];
            $isEditBatch = true;
        } else {
            return $ret;
        }

        $pkList = json_decode($pkStr, true);
        if (empty($pkList)) {
            return $ret;
        }

        $pkListMd5 = [];
        foreach ($pkList as $pkL) {
            $pkListMd5[] = substr(md5($pkL), 8, 8);
        }

        foreach ($srcData as $md5Key => $val) {
            $md5Info = [];
            $isBreak = false;
            foreach ($pks as $pk) {
                if (!isset($val[$pk])) {
                    $isBreak = true;
                    break;
                }
                $md5Info[$pk] = $val[$pk];
            }

            if ($isBreak || empty($md5Info)) {
                continue;
            }

            $md5Str = json_encode($md5Info, true);

            foreach ($pkListMd5 as $pklm) {
                if ($md5Key !== $pklm) {
                    $ret[$md5Key] = [
                        'md5' => $pklm,
                        'value' => $md5Str
                    ];
                }
            }
        }

        return $ret;
    }

    /**
     * 数据编辑功能
     * @param $apiObj
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function html($apiObj)
    {
        $plu = TphpConfig::$domainPath->plu;
        if (empty($plu)) {
            return '';
        }

        $type = $apiObj->tplInit->tplType;
        $this->editSetHandle($apiObj);
        $data = $apiObj->data['_'];
        is_string($data) && $apiObj->__exitError($data);
        $src = $apiObj->data['src'];
        empty($src) && $src = [];
        $pkMd5s = $apiObj->callCommands("getPkMd5s", $src);
        $retData = [];
        $srcData = [];
        if (is_array($data)) {
            foreach ($data as $key => $val) {
                if (is_array($src[$key])) {
                    foreach ($src[$key] as $k => $v) {
                        if (!isset($val[$k])) {
                            $val[$k] = $v;
                        }
                    }
                }
                $retData[$pkMd5s[$key]['md5']] = $val;
                $srcData[$pkMd5s[$key]['md5']] = $src[$key];
            }
        }

        //from关联数据-开始
        $afd = $apiObj->allField;
        $vConf = $apiObj->vimConfig['handle'];
        $vcReps = [];
        $links = [];
        if (is_array($vConf)) {
            foreach ($vConf as $key => $val) {
                if (is_array($val)) {
                    $vFrom = $val['from'];
                    if (is_array($vFrom)) {
                        if (is_array($vFrom[3])) {
                            if (is_string($vFrom[1])) {
                                $vKey = $vFrom[1];
                            } elseif (is_array($vFrom[1]) && count($vFrom[1]) > 1) {
                                $vKey = $vFrom[1][1];
                            }
                            if (!empty($vKey) && is_string($vKey)) {
                                if (!is_array($vFrom[3]) && empty($vFrom[3])) {
                                    if (isset($afd[$vKey])) {
                                        $vcReps[$key] = $vKey;
                                    }
                                } else {
                                    $links[$key] = $val;
                                }
                            }
                        }
                    }
                }
            }
        }
        //from关联数据-结束

        if ($apiObj->tplInit->isPost()) {
            if (empty($retData)) {
                $apiObj->__exitError("操作失败！");
            } else {
                $tree = $apiObj->vimConfig['tree'];
                if (!empty($tree) && !empty($srcData)) {
                    $apiObj->callCommands("setTreeChildCount", $tree, $srcData);
                }

                if (count($vcReps) > 0) {
                    foreach ($srcData as $key => $val) {
                        foreach ($vcReps as $k => $v) {
                            $srcData[$key][$k] = $val[$v];
                        }
                    }
                    foreach ($retData as $key => $val) {
                        foreach ($vcReps as $k => $v) {
                            $retData[$key][$k] = $val[$v];
                        }
                    }
                }

                $froms = $apiObj->froms;
                empty($froms) && $froms = [];
                foreach ($retData as $key => $val) {
                    foreach ($froms as $k => $v) {
                        $fVal = $v['value'];
                        $retData[$key][$k] = $fVal;
                        $fInfo = $v['info'];
                        $fKey = $val[$fInfo['default']['key']];
                        if (empty($fVal) && $fVal !== '0') {
                            $fVals = [];
                        } elseif (is_string($fVal)) {
                            $fVals = explode(",", $fVal);
                        } elseif (is_array($fVal)) {
                            $fVals = [];
                            foreach ($fVal as $fv) {
                                $fVals[] = $fv;
                            }
                        }
                        $fMod = $apiObj->tplInit->db($fInfo['link']['table'], $fInfo['link']['conn']);
                        $fdKey = $fInfo['link']['default_key'];
                        $ftKey = $fInfo['link']['this_key'];
                        $fList = $fMod->where($fdKey, '=', $fKey)->get();
                        $fKv = [];
                        foreach ($fList as $f) {
                            $fKv[$f->$ftKey] = true;
                        }
                        $tLst = $vConf[$k]['list'];
                        $vLst = [];
                        $fDelets = [];
                        $fInsert = [];
                        foreach ($fVals as $fv) {
                            if (isset($fKv[$fv])) {
                                $fKv[$fv] = false;
                            } else {
                                $fInsert[] = [
                                    $fdKey => $fKey,
                                    $ftKey => $fv
                                ];
                            }
                            $vLst[] = $tLst[$fv];
                        }
                        foreach ($fKv as $fk => $fv) {
                            $fv && $fDelets[] = $fk;
                        }
                        $srcData[$key][$k] = implode(", ", $fVals);
                        $retData[$key][$k] = implode(", ", $vLst);
                        if (count($fDelets) > 0) {
                            $fMod->where($fdKey, '=', $fKey)->whereIn($ftKey, $fDelets)->delete();
                        }
                        if (count($fInsert) > 0) {
                            $fMod->insert($fInsert);
                        }
                        unset($val[$k]);

                    }

                    foreach ($val as $k => $v) {
                        $tp = $vConf[$k]['type'];
                        $tLst = $vConf[$k]['list'];
                        if (in_array($tp, ['select', 'selects', 'checkbox', 'trees'])) {
                            $tpafd = $afd[$k]['type'];
                            if (strpos($tpafd, "int") === false) {
                                $vLst = explode(",", $v);
                                foreach ($vLst as $kk => $vv) {
                                    isset($tLst[$vv]) && $vLst[$kk] = $tLst[$vv];
                                }
                                $retData[$key][$k] = implode(", ", $vLst);
                            }
                        } elseif ($tp == 'tree') {
                            $vTree = $vConf[$k]['tree'];
                            if (is_array($vTree)) {
                                $vChild = strtolower($vTree['child']);
                                $vParent = strtolower($vTree['parent']);
                                $vTable = strtolower($vTree['table']);
                                $vValue = $vTree['value'];
                                $vName = strtolower($vTree['name']);
                                $vSort = $vTree['sort'];
                                empty($name) && $name = $vChild;
                                if (is_array($vTable)) {
                                    list($vTable, $vConn) = $vTable;
                                } else {
                                    $vConn = "";
                                }
                                empty($vConn) && $vConn = $apiObj->defaultConn;
                                $vIds = [$v];

                                $vOrder = [];
                                if (!empty($vSort) && is_array($vSort)) {
                                    if (is_string($vSort[0]) && is_string($vSort[1])) {
                                        $vOrder[$vSort[0]] = $vSort[1];
                                    } else {
                                        foreach ($vSort as $key => $val) {
                                            if (is_string($key) && is_string($val)) {
                                                $vOrder[$key] = $val;
                                            }
                                        }
                                    }
                                }

                                $vFlag = $vTree['flag'];
                                if (empty($vFlag)) {
                                    $vFlag = " / ";
                                }
                                
                                $names = [];
                                while (true) {
                                    $vmod = $apiObj->tplInit->db($vTable, $vConn)->whereIn($vChild, $vIds);
                                    foreach ($vOrder as $vo) {
                                        list($voK, $voV) = $vo;
                                        if (is_string($voK) && is_string($voV)) {
                                            $vmod->orderBy($voK, $voV);
                                        }
                                    }
                                    $vList = $vmod->get();
                                    $vIds = [];
                                    foreach ($vList as $kk => $vv) {
                                        $vv = $apiObj->keyToLower($vv);
                                        if ($vv[$vParent] != $vValue) {
                                            $vIds[] = $vv[$vParent];
                                        }
                                        $names[] = $vv[$vName] ?? $vv[$vChild];
                                    }
                                    if (empty($vIds)) {
                                        break;
                                    }
                                }
                                if (empty($names)) {
                                    $retData[$key][$k] = $vValue ?? '';
                                } else {
                                    $retData[$key][$k] = implode($vFlag, array_reverse($names));
                                }
                            }
                        }
                    }
                }

                $pksReplace = $this->getReplacePks($srcData, $apiObj->getPks());

                $retData = [
                    'pks' => $pkMd5s,
                    'src' => $srcData,
                    'show' => $retData
                ];

                if (!empty($pksReplace)) {
                    $retData['replace'] = $pksReplace;
                }

                if ($type == 'add') {
                    $bindUpdate = $apiObj->bindUpdate;
                    if (is_function($bindUpdate)) {
                        $bindUpdate($pkMd5s);
                    }
                }

                EXITJSON(1, "操作成功！", $retData);
            }
        } else {
            if (count($vcReps) > 0) {
                foreach ($src as $key => $val) {
                    foreach ($vcReps as $k => $v) {
                        $src[$key][$k] = $val[$v];
                    }
                }
            }

            if (count($links) > 0) {
                foreach ($links as $key => $val) {
                    $gfc = $apiObj->callCommands("getFromCheck", $val['from']);
                    $defKey = $gfc['default']['key'];
                    foreach ($src as $k => $v) {
                        $defV = $apiObj->tplInit->db($gfc['link']['table'], $gfc['link']['conn'])->where($gfc['link']['default_key'], "=", $v[$defKey])->get();
                        $defIds = [];
                        foreach ($defV as $dv) {
                            $dv = $apiObj->keyToLower($dv);
                            $dvk = strtolower($gfc['link']['this_key']);
                            $defIds[] = $dv[$dvk];
                        }
                        $src[$k][$key] = implode(",", $defIds);
                    }
                }
            }
            $plu = TphpConfig::$domainPath->plu;

            $viewData = $apiObj->tplInit->viewData;
            empty($viewData) && $viewData = [];
            if (count($src) > 1) {
                list($data, $src) = $this->resetData($data, $src);
            }
            $viewData['data'] = $data;
            $viewData['src'] = $src;
            if ($type == 'add') {
                $fd = [];
                isset($src[0]) && $fd = $src[0];
                $field = [];
                if (!empty($apiObj->vimConfig['handle']) && is_array($apiObj->vimConfig['handle'])) {
                    foreach ($apiObj->vimConfig['handle'] as $key => $val) {
                        $v = $val['value'];
                        $tp = $val['type'];
                        if ($tp == 'tree') {
                            $tree = $apiObj->vConfig[$key]['tree'];
                            !$apiObj->whereNull && $v = $fd[$tree['child']];
                            if (empty($v)) $v = $tree['value'];
                        } else {
                            if (empty($v)) $v = "";
                        }
                        $field[$key] = $v;
                    }
                }
                $viewData['field'] = $field;
            } else {
                if (count($src) > 0) {
                    $viewData['field'] = $src[0];
                }
            }
            if ($type == 'view') {
                $isView = true;
            } else {
                $isView = false;
            }

            $showHandle = $apiObj->showHandle;
            $fieldUnsets = $apiObj->fieldUnsets;
            if (is_array($fieldUnsets)) {
                foreach ($fieldUnsets as $val) {
                    unset($showHandle[$val]);
                }
            }
            $fFmt = $apiObj->fileFormats;
            $types = [];
            if (!empty($showHandle) && is_array($showHandle)) {
                $i = 0;
                foreach ($showHandle as $key => $val) {
                    $vtp = $val['type'];
                    isset($val['type']) && $types[$vtp] = true;
                    $showHandle[$key]['src_name'] = $val['name'];
                    if (in_array($vtp, ['file', 'image'])) {
                        if ($vtp == 'file') {
                            $fmt = $val['format'];
                            if (empty($fmt)) {
                                $fmt = $fFmt[$vtp];
                            }
                        } else {
                            $fmt = $fFmt[$vtp];
                        }
                        $fmtStr = implode(", ", $fmt);
                        $val['remark'] = "支持的格式：" . $fmtStr;
                    }
                    if ($i <= 0) {
                        $cls = 'layui-layer-tips-first';
                    } else {
                        $cls = '';
                    }
                    if (!empty($val['remark'])) {
                        $showHandle[$key]['name'] = '<span class="js_name_remark">' . $val['name'] . '</span><div class="layui-layer layui-layer-tips ' . $cls . '"><div class="layui-layer-content"><div class="in">' . $val['remark'] . '</div><i class="layui-layer-TipsG layui-layer-TipsT"></i><i class="layui-layer-TipsG layui-layer-TipsT layui-layer-TipsTOut"></i></div><span class="layui-layer-setwin"></span></div>';
                    }
                    $i++;
                }
            }
            $viewData['allField'] = $apiObj->allField;
            $viewData['field'] = $this->getEditData($apiObj, $showHandle, $viewData['field']);
            $viewData['isView'] = $isView;
            $viewData['tplBase'] = $apiObj->baseTplPath;
            $viewData['tplPath'] = $apiObj->tplInit->tplInit;
            $viewData['tplType'] = $apiObj->tplInit->tplType;
            $viewData['types'] = $types;
            $viewData['_DC_'] = TphpConfig::$domain;
            $handleGroup = [];
            $handleGroupChecked = -1;
            if (is_array($showHandle)) {
                foreach ($showHandle as $key => $val) {
                    $gKey = $val['group'];
                    empty($gKey) && $gKey = "";
                    $handleGroup[$gKey][$key] = $val;
                    if ($handleGroupChecked == -1 || $val['checked']) {
                        $handleGroupChecked = $gKey;
                    }
                }
            }
            $viewData['handleGroup'] = $handleGroup;
            $viewData['handleGroupChecked'] = $handleGroupChecked;
            return $plu->view("vim.handle", $viewData);
        }
    }
};
