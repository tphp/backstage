<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

use Tphp\Config as TphpConfig;

return new class
{
    /**
     * 设置搜索功能
     */
    protected function search($apiObj)
    {
        $vConfig = $apiObj->vConfig;

        //设置搜索功能
        $search = [];
        if (!empty($vConfig)) {
            $searchArr = [];
            $typeDef = 'text';
            foreach ($vConfig as $key => $val) {
                if (isset($val['search'])) {
                    $sList = $val['list'];
                    empty($sList) && $sList = [];
                    $type = $val['type'];
                    empty($type) && $type = $typeDef;
                    if ($val['status']) {
                        $type = "status";
                        list($l, $r) = explode("|", $val['text']);
                        empty($l) && $l = "启用";
                        empty($r) && $r = "禁用";
                        $sList = [
                            '1' => $l,
                            '0' => $r
                        ];
                    }
                    $s = $val['search'];
                    if (in_array($type, ['tree', 'trees'])) {
                        $width = 200;
                    } else {
                        $width = 100;
                    }
                    if (is_bool($s) && $s) {
                        $searchArr[0][$key] = [
                            'width' => $width,
                            'type' => $type,
                            'list' => $sList
                        ];
                    } elseif (is_array($s)) {
                        $s = $apiObj->keyToLower($s);
                        if (!isset($s['width'])) {
                            $s['width'] = $width;
                        }

                        if (!isset($s['type'])) {
                            $s['type'] = $type;
                        }

                        $index = 0;
                        if (isset($s['index'])) {
                            is_numeric($s['index']) && $index = $s['index'];
                            unset($s['index']);
                        }
                        $s['list'] = $sList;
                        $searchArr[$index][$key] = $s;
                    }
                }
            }
            if (!empty($searchArr)) {
                ksort($searchArr);
                foreach ($searchArr as $key => $val) {
                    foreach ($val as $k => $v) {
                        $search[$k] = $v;
                    }
                }
            }
        }
        $apiObj->search = $search;
    }

    /**
     * html 配置
     * @param $apiObj
     * @param $vimConfig
     * @return mixed
     */
    public function config($apiObj, $vimConfig)
    {
        $allField = $apiObj->allField;
        $export = $_GET['_@export@_'];
        $apiObj->isExport = false;
        if (in_array($export, ['all', 'checked', 'this', 'print'])) {
            $_POST['type'] = 'all';
            $apiObj->isExport = true;
        }
        $vConfigSrc = [];
        $vsHiden = [];
        $vsIsDisabled = false;

        // 去除树状列表编辑
        if (isset($vimConfig['tree'])) {
            foreach ($apiObj->vConfig as $key => $val) {
                if (!isset($val['title'])) {
                    continue;
                }
                if ($val['title'] && $val['edit'] === true) {
                    unset($apiObj->vConfig[$key]['edit']);
                }
            }
        }
        
        foreach ($apiObj->vConfig as $key => $val) {
            if ($val['hidden']) {
                $vsHiden[$key] = true;
            } else {
                if ($val['title']) {
                    $val['disabled'] = true;
                    $vsIsDisabled = true;
                }
                $vConfigSrc[$key] = $val;
            }
        }
        $userInfo = $apiObj->getUserInfo();
        $dc = TphpConfig::$domain;
        if (empty($userInfo) && $dc['backstage']) {
            $apiObj->__exitError("用户未登录");
        }
        $_mid_ = $_GET['_mid_'];
        $__type__ = $_GET['__type__'];
        if (!empty($__type__)) {
            $_mid_ = hexdec(substr(md5("{$_mid_}_{$__type__}"), 12, 6));
        }

        $retConfig = $apiObj->retConfig;
        $rWhere = $retConfig['config']['search'];
        empty($rWhere) && $rWhere = [];
        $isTree = false;
        if (isset($vimConfig['tree'])) {
            $tree = $vimConfig['tree'];
            if (isset($tree['parent']) && isset($tree['child']) && isset($tree['value'])) {
                $p = $tree['parent'];
                $c = $tree['child'];
                $v = $tree['value'];
                if (isset($allField[$p]) && isset($allField[$c])) {
                    $isTree = true;
                    $where = $rWhere;
                    $where[] = [$p, "=", $v];
                    $retConfig['config']['search'] = $where;
                    $retConfig['config']['tree'] = [
                        'parent' => $p,
                        'child' => $c,
                        'value' => $v,
                    ];
                }
            }
        }

        //字段筛选查询功能
        if (count($vConfigSrc) > 4) {
            $userId = $userInfo['id'];
            $menuField = $apiObj->tplInit->cache(function () use ($_mid_, $userId, $apiObj) {
                $mfInfo = import("SystemInit", $apiObj->tplInit)->tableMenuField()->where('menu_id', '=', $_mid_)->where('user_id', '=', $userId)->first();
                if (empty($mfInfo)) {
                    return false;
                }
                $mf = trim($mfInfo->field);
                if (empty($mf)) {
                    return [];
                }
                $mfArr = array_unique(explode(",", $mf));
                $ret = [];
                foreach ($mfArr as $m) {
                    $m = trim(strtolower($m));
                    if (!empty($m)) {
                        $ret[$m] = true;
                    }
                }
                return $ret;
            }, "info_{$_mid_}_{$userId}");
            if ($menuField === false) {
                foreach ($vConfigSrc as $key => $val) {
                    $vConfigSrc[$key]['selected'] = true;
                }
            } else {
                $delKeys = [];
                foreach ($apiObj->vConfig as $key => $val) {
                    if (isset($menuField[$key])) {
                        $vConfigSrc[$key]['selected'] = true;
                    } elseif (!$vsHiden[$key]) {
                        $delKeys[] = $key;
                    }
                }
                foreach ($delKeys as $dk) {
                    unset($apiObj->vConfig[$dk]);
                }
            }
            if ($isTree) {
                $p = $vimConfig['tree']['parent'];
                $c = $vimConfig['tree']['child'];
                if (isset($vConfigSrc[$p])) {
                    $vConfigSrc[$p]['disabled'] = true;
                    $vsIsDisabled = true;
                }
                if (isset($vConfigSrc[$c])) {
                    $vConfigSrc[$c]['disabled'] = true;
                    $vsIsDisabled = true;
                }
            }
            if (!$vsIsDisabled) {
                foreach ($vConfigSrc as $key => $val) {
                    $vConfigSrc[$key]['disabled'] = true;
                    break;
                }
            }
            $autoWidths = [];
            $vConfigSrcTop = [];
            $vConfigSrcDown = [];
            foreach ($vConfigSrc as $key => $val) {
                $vck = $apiObj->vConfig[$key];
                if (!$val['fixed'] && isset($vck) && !$vck['hidden']) {
                    $autoWidths[] = $key;
                }
                $vName = trim($val['name']);
                empty($vName) && $vName = $key;
                $val['name'] = $vName;
                if ($val['disabled']) {
                    $vConfigSrcTop[$key] = $val;
                } else {
                    $vConfigSrcDown[$key] = $val;
                }
            }

            if (empty($autoWidths)) {
                $widthAdd = 0;
                foreach ($apiObj->vConfig as $key => $val) {
                    unset($apiObj->vConfig[$key]['fixed']);
                    if ($val['width'] > 0) {
                        $widthAdd += $val['width'];
                    }
                }
                foreach ($apiObj->vConfig as $key => $val) {
                    $apiObj->vConfig[$key]['width'] = floor(($val['width'] * 100) / $widthAdd) . "%";
                }
            } else {
                $widthAdd = 0;
                $awkv = [];
                foreach ($autoWidths as $aw) {
                    $tw = $apiObj->vConfig[$aw]['width'];
                    if (empty($tw) || $tw <= 0) {
                        $tw = 20;
                    }
                    $awkv[$aw] = $tw;
                    $widthAdd += $tw;
                }
                foreach ($awkv as $k => $v) {
                    $apiObj->vConfig[$k]['width'] = floor(($v * 100) / $widthAdd) . "%";
                }
            }
            $vConfigSrcNew = [];
            foreach ($vConfigSrcTop as $key => $val) {
                $vConfigSrcNew[$key] = $val;
            }
            foreach ($vConfigSrcDown as $key => $val) {
                $vConfigSrcNew[$key] = $val;
            }
            $apiObj->vConfigSrc = $vConfigSrcNew;
        }

        //树状结构分析
        $apiObj->isTreeAll = false;
        if ($isTree) {
            $retConfig['ispage'] = false;
            if ($apiObj->tplInit->isPost()) {
                if ($_POST['type'] == 'all') {
                    unset($retConfig['config']['search']);
                    $apiObj->isTreeAll = true;
                } else {
                    !isset($_POST["value"]) && $apiObj->__exitError("没有数据传递");
                    $value = $_POST["value"];
                    $retConfig['config']['search'] = [$tree['parent'], "=", $value];
                }
            }
        } else {
            $fieldKv = $apiObj->vim->fieldKeyVal;
            !is_array($fieldKv) && $fieldKv = [];
            $this->search($apiObj);
            //设置配置数据查询功能
            $getData = $_GET;
            unset($getData['p']);
            unset($getData['psize']);
            unset($getData['_sort']);
            unset($getData['_order']);
            $vConfig = $apiObj->vConfig;
            if (!empty($getData)) {
                $where = $rWhere;
                $allField = $apiObj->allField;
                $vField = $vimConfig['field'];
                $findWheres = [];
                foreach ($getData as $key => $val) {
                    if (!isset($vField[$key])) {
                        //当搜索时不存在设置字段则不进行条件查找
                        continue;
                    }
                    $keyLen = strlen($key);
                    if (!isset($allField[$key]) && $keyLen > 2) {
                        $key_2 = substr($key, $keyLen - 2);
                        if ($key_2 == "__") {
                            $key = substr($key, 0, $keyLen - 2);
                        }
                    }
                    $aType = $allField[$key]['type'];
                    $tp = $vField[$key]['type'];
                    $oper = $vField[$key]['oper'];
                    if ($tp == 'time') {
                        $isInt = strpos($aType, 'int') === false ? false : true;
                        $sVal = $getData[$key];
                        $eVal = $getData[$key . "__"];
                        if (!empty($sVal)) {
                            $sVal = $sVal . " 00:00:00";
                            if ($isInt) {
                                $where[] = [$key, ">=", strtotime($sVal)];
                            } else {
                                $where[] = [$key, ">=", $sVal];
                            }
                        }
                        if (!empty($eVal)) {
                            $eVal = $eVal . " 23:59:59";
                            if ($isInt) {
                                $where[] = [$key, "<=", strtotime($eVal)];
                            } else {
                                $where[] = [$key, "<=", $eVal];
                            }
                        }
                    } elseif ($tp == 'tree') {
                        $vTree = $vField[$key]['tree'];
                        $vTable = strtolower($vTree['table']);
                        $val = str_replace("/", " ", $val);
                        if (is_array($vTable)) {
                            list($vTable, $vConn) = $vTable;
                        } else {
                            $vConn = "";
                        }
                        empty($vConn) && $vConn = $apiObj->defaultConn;
                        $vChild = strtolower($vTree['child']);
                        $vParent = strtolower($vTree['parent']);
                        $vName = strtolower($vTree['name']);
                        if (empty($vName)) {
                            $vName = $vChild;
                        }
                        $vArrTmp = explode(' ', $val);
                        $vArr = [];
                        foreach ($vArrTmp as $vt) {
                            !empty($vt) && $vArr[] = $vt;
                        }
                        if (empty($vArr)) {
                            $where = true;
                            break;
                        }
                        $vArr = array_unique($vArr);
                        $treeMod = $apiObj->tplInit->db($vTable, $vConn);
                        foreach ($vArr as $vr) {
                            $treeMod->orWhere($vName, 'like', "%{$vr}%");
                        }
                        $vList = $treeMod->get();
                        if (count($vList) <= 0) {
                            $where = true;
                            break;
                        }
                        $vIds = [];
                        $parents = [];
                        foreach ($vList as $vk => $vv) {
                            $vv = $apiObj->keyToLower($vv);
                            $vIds[] = $vv[$vChild];
                            $parents[$vv[$vChild]] = [
                                'name' => $vv[$vName],
                                'pid' => $vv[$vParent]
                            ];
                        }
                        $vIds = array_unique($vIds);

                        if (count($vIds) > 1) {
                            // 如果搜索组合大于1时需要进行拼合处理
                            $pids = [];
                            foreach ($vList as $vk => $vv) {
                                $vv = $apiObj->keyToLower($vv);
                                if (!isset($parents[$vv[$vParent]])) {
                                    $pids[] = $vv[$vParent];
                                }
                            }

                            while (!empty($pids)) {
                                $vList = $apiObj->tplInit->db($vTable, $vConn)->whereIn($vChild, $pids)->get();
                                $pids = [];
                                foreach ($vList as $vk => $vv) {
                                    $vv = $apiObj->keyToLower($vv);
                                    if (!isset($parents[$vv[$vChild]])) {
                                        $parents[$vv[$vChild]] = [
                                            'name' => $vv[$vName],
                                            'pid' => $vv[$vParent]
                                        ];
                                        $pids[] = $vv[$vParent];
                                    }
                                }
                            }

                            $vNames = [];
                            foreach ($vIds as $vd) {
                                $vNames[$vd] = [];
                                $ovd = $vd;
                                while (isset($parents[$ovd])) {
                                    $vNames[$vd][] = $parents[$ovd]['name'];
                                    $ovd = $parents[$ovd]['pid'];
                                }
                            }

                            $shIds = [];
                            foreach ($vNames as $vk => $vns) {
                                $bOut = true;
                                foreach ($vArr as $vr) {
                                    $b = false;
                                    foreach ($vns as $vn) {
                                        if (stripos($vn, $vr) !== false) {
                                            $b = true;
                                            break;
                                        }
                                    }
                                    if (!$b) {
                                        $bOut = false;
                                        break;
                                    }
                                }
                                $bOut && $shIds[] = $vk;
                            }

                            if (empty($shIds)) {
                                $where = true;
                                break;
                            }
                            $vIds = $shIds;
                        }

                        $idsKv = [];
                        foreach ($vIds as $vid) {
                            $idsKv[$vid] = true;
                        }
                        while (true) {
                            $vList = $apiObj->tplInit->db($vTable, $vConn)->whereIn($vParent, $vIds)->get();
                            $vIds = [];
                            foreach ($vList as $vk => $vv) {
                                $vv = $apiObj->keyToLower($vv);
                                if (!$idsKv[$vv[$vChild]]) {
                                    $idsKv[$vv[$vChild]] = true;
                                    $vIds[] = $vv[$vChild];
                                }
                            }
                            if (empty($vIds)) {
                                break;
                            }
                        }
                        if (!empty($idsKv)) {
                            $vIds = [];
                            foreach ($idsKv as $k => $v) {
                                $vIds[] = $k;
                            }
                            $where[] = [$key, "=", $vIds];
                        }
                    } elseif ($tp == 'between') {
                        $sVal = $getData[$key];
                        $eVal = $getData[$key . "__"];
                        !empty($sVal) && $where[] = [$key, ">=", $sVal];
                        !empty($eVal) && $where[] = [$key, "<=", $eVal];
                    } elseif ($tp == 'select' || $tp == 'selects' || $tp == 'checkbox') {
                        $vFrom = $vField[$key]['from'];
                        if (isset($vFrom[3]) && is_array($vFrom[3])) {
                            $gfc = $apiObj->callCommands("getFromCheck", $vFrom);
                            $defKey = strtolower($gfc['link']['default_key']);
                            $thisKey = $gfc['link']['this_key'];
                            $gfcIds = [];
                            $gfcList = $apiObj->tplInit->db($gfc['link']['table'], $gfc['link']['conn'])->whereIn($thisKey, explode(",", $val))->select($defKey, $thisKey)->get();
                            foreach ($gfcList as $gfcVal) {
                                $gfcVal = $apiObj->keyToLower($gfcVal);
                                $gfcIds[] = $gfcVal[$defKey];
                            }
                            $gfcIds = array_unique($gfcIds);
                            if (empty($gfcIds)) {
                                $where[] = [$gfc['default']['key'], '=', '_#NULL#_'];
                            } else {
                                $where[] = [$gfc['default']['key'], '=', $gfcIds];
                            }
                        } else {
                            if (is_array($vFrom[1]) && count($vFrom[1]) > 1 && is_string($vFrom[1][1])) {
                                $keyName = $vFrom[1][1];
                            } else {
                                $keyName = $key;
                            }
                            if (isset($allField[$keyName])) {
                                $wvArr = explode(",", $val);
                                $cWhere = [];
                                if (strpos($aType, 'int') === false) {
                                    $w = [];
                                    foreach ($wvArr as $wv) {
                                        if (!empty($wv) || $wv == 0) {
                                            $w[] = [$keyName, "like", $wv . ",%"];
                                            $w[] = 'or';
                                            $w[] = [$keyName, "like", "%," . $wv . ",%"];
                                            $w[] = 'or';
                                            $w[] = [$keyName, "like", "%," . $wv];
                                            $w[] = 'or';
                                        }
                                    }
                                    if (count($w) > 0) {
                                        $cWhere = $w;
                                    }
                                }
                                if (empty($cWhere)) {
                                    $where[] = [$keyName, "=", $wvArr];
                                } else {
                                    $where[] = [
                                        [$keyName, "=", $wvArr],
                                        'or',
                                        $cWhere
                                    ];
                                }
                            }
                        }
                    } elseif (isset($allField[$key])) {
                        if (!empty($oper) && $oper == '=') {
                            $where[] = [$key, "=", $val];
                        } else {
                            $where[] = [$key, "like", "%{$val}%"];
                        }
                    } elseif (is_array($vConfig[$key]) && isset($vConfig[$key]['find'])) {
                        $finds = $vConfig[$key]['find'];
                        $find_key = md5(json_encode($finds, true));
                        if (empty($findWheres[$find_key])) {
                            $findWheres[$find_key]['find'] = $finds;
                        }
                        if (isset($fieldKv[$key])) {
                            $fKey = $fieldKv[$key];
                        } else {
                            $fKey = $key;
                        }
                        $findWheres[$find_key]['list'][$fKey] = $val;
                    }
                }
                $isBreak = false;
                if (!empty($findWheres)) {
                    $comList = [];
                    foreach ($findWheres as $findW) {
                        $find = array_reverse($findW['find']);
                        $fList = $findW['list'];
                        if (count($find) > 0 && count($fList) > 0) {
                            $find0 = $find[0];
                            unset($find[0]);
                            $fTable = $find0[0];
                            $ffld = strtolower($find0[1]);
                            $fConn = "";
                            if (is_array($fTable)) {
                                list($fTable, $fConn) = $fTable;
                            }
                            empty($fConn) && $fConn = $apiObj->defaultConn;
                            $fMod = $apiObj->tplInit->db($fTable, $fConn)->select($ffld);
                            foreach ($fList as $fk => $fv) {
                                $fMod->where($fk, 'like', "%{$fv}%");
                            }
                            $fl = $fMod->get();
                            $fls = [];
                            foreach ($fl as $fv) {
                                $fv = $apiObj->keyToLower($fv);
                                $fls[] = $fv[$ffld];
                            }
                            if (empty($fls)) {
                                $isBreak = true;
                                break;
                            }
                            $fuField = $find0[2];
                            foreach ($find as $fk => $fv) {
                                $fTable = $fv[0];
                                $ffld = $fv[1];
                                $fConn = "";
                                if (is_array($fTable)) {
                                    list($fTable, $fConn) = $fTable;
                                }
                                empty($fConn) && $fConn = $apiObj->defaultConn;
                                $fl = $apiObj->tplInit->db($fTable, $fConn)->select($ffld)->whereIn($fv[2], $fls)->get();
                                $fls = [];
                                foreach ($fl as $fvv) {
                                    $fvv = $apiObj->keyToLower($fvv);
                                    $fls[] = $fvv[$ffld];
                                }
                                $fuField = $fv[2];
                            }
                            $comList[$fuField][] = $fls;
                        }
                    }
                    foreach ($comList as $key => $val) {
                        $vCots = [];
                        $vCot = count($val);
                        foreach ($val as $v) {
                            foreach ($v as $vv) {
                                if (isset($vCots[$vv])) {
                                    $vCots[$vv]++;
                                } else {
                                    $vCots[$vv] = 1;
                                }
                            }
                        }
                        $realV = [];
                        foreach ($vCots as $k => $v) {
                            if ($v >= $vCot) {
                                $realV[] = $k;
                            }
                        }
                        $where[] = [$key, '=', $realV];
                    }
                }
                if ($isBreak) {
                    $retConfig['config']['search'] = true;
                } else {
                    $retConfig['config']['search'] = $where;
                }
            }
        }
        $apiObj->isTree = $isTree;
        $apiObj->retConfig = $retConfig;
        return $retConfig;
    }

    /**
     * html 展示
     * @param $apiObj
     * @param $data
     * @return string
     */
    public function html($apiObj, $data)
    {
        if (is_string($data)) $apiObj->page404($data);

        $plu = TphpConfig::$domainPath->plu;
        if (empty($plu)) {
            return '';
        }

        if ($apiObj->isExport) {
            unset($_POST['type']);
        }
        $tpl = $apiObj->tplInit->tplInit;
        $config = $apiObj->tplInit->config;

        $operTitle = $apiObj->operTitle;
        if ($config['type'] == 'dir') {
            $pks = [
                'id'
            ];
        } else {
            $pks = [];
            if (!empty($data['field'])) {
                foreach ($data['field'] as $key => $val) {
                    if ($val["key"] == "PRI") {
                        $pks[] = $key;
                    }
                }
                if (empty($pks) && !empty($operTitle)) {
                    $pks = [$operTitle];
                }
            }
        }

        $pkList = [];
        if (!empty($pks)) {
            foreach ($data['src'] as $key => $val) {
                $tpk = [];
                foreach ($pks as $v) {
                    $tpk[$v] = $val[$v];
                }
                $pkList[$key] = json_encode($tpk, true);
            }
        }
        $isTree = $apiObj->isTree;
        if ($isTree) {
            $retConfig = $apiObj->retConfig;
            $tree = $retConfig['config']['tree'];
            $apiObj->callCommands("setTreeChildCount", $tree, $data['src']);
        }

        $cType = $apiObj->tplInit->config['type'];
        if (is_string($data['_'])) $apiObj->page404($data['_']);
        //关联查询功能
        foreach ($apiObj->vConfig as $key => $val) {
            $vFrom = $val['from'];
            $tp = $val['type'];
            $tLst = $val['list'];
            if (!is_array($tLst)) {
                $tLst = [];
            }
            if (is_array($vFrom)) {
                if (empty($vFrom[3]) && !is_array($vFrom[3])) {
                    if (is_array($vFrom[1]) && count($vFrom[1]) > 1) {
                        $vKey = $vFrom[1][1];
                    } elseif (is_string($vFrom[1])) {
                        $vKey = $key;
                    }
                    if (!empty($vKey)) {
                        foreach ($data['src'] as $k => $v) {
                            if (isset($v[$vKey])) {
                                $valx = $v[$vKey];
                                $data['src'][$k][$key] = $valx;
                                if ($tp == 'select' || $tp == 'selects' || $tp == 'checkbox') {
                                    $lst = explode(",", $valx);
                                    foreach ($lst as $kk => $vv) {
                                        if (isset($tLst[$vv])) {
                                            $lst[$kk] = $tLst[$vv];
                                        }
                                    }
                                    $data['_'][$k][$key] = implode(", ", $lst);
                                }
                            }
                        }
                    }
                } else {
                    $lInfo = $apiObj->callCommands("getFromCheck", $vFrom);
                    $lKeys = [];
                    $lKey = $lInfo['default']['key'];
                    foreach ($data['src'] as $k => $v) {
                        $lKeys[] = $v[$lKey];
                    }
                    $lKv = [];
                    $dk = $lInfo['default']['key'];
                    $lk = strtolower($lInfo['link']['default_key']);
                    $lv = strtolower($lInfo['link']['this_key']);
                    $lList = $apiObj->tplInit->db($lInfo['link']['table'], $lInfo['link']['conn'])->whereIn($lk, $lKeys)->select($lk, $lv)->get();
                    foreach ($lList as $lVal) {
                        $lVal = $apiObj->keyToLower($lVal);
                        $lKv[$lVal[$lk]][] = $lVal[$lv];
                    }
                    foreach ($data['src'] as $k => $v) {
                        $fVal = $lKv[$v[$dk]];
                        if (!empty($fVal)) {
                            $data['src'][$k][$key] = implode(", ", $fVal);
                            foreach ($fVal as $kk => $vv) {
                                if (isset($tLst[$vv])) {
                                    $fVal[$kk] = $tLst[$vv];
                                }
                            }
                            $data['_'][$k][$key] = implode(", ", $fVal);
                        }
                    }
                }
            } elseif ($tp == 'tree') {
                if ($cType == 'dir') {
                    continue;
                }

                unset($apiObj->vConfig[$key]['edit']);
                $treeIds = [];
                foreach ($data['src'] as $k => $v) {
                    $treeIds[] = $v[$key];
                }
                $treeIds = array_unique($treeIds);
                $vTree = $val['tree'];
                $vtName = $vTree['name'];
                if (empty($vtName)) {
                    $vtName = $vTree['child'];
                }
                $vtName = strtolower($vtName);
                $vTable = $vTree['table'];
                if (is_array($vTable)) {
                    list($vTable, $vConn) = $vTable;
                } else {
                    $vConn = "";
                }
                $defTable = $apiObj->defaultTable;
                $defConn = $apiObj->defaultConn;
                empty($vConn) && $vConn = $apiObj->defaultConn;

                $vTopValue = $vTree['value'];
                $vParentName = strtolower($vTree['parent']);
                $vChildName = strtolower($vTree['child']);
                $vList = $apiObj->tplInit->db($vTable, $vConn)->whereIn($vChildName, $treeIds)->get();
                $vKv = [];
                $parentIds = [];
                foreach ($vList as $vVal) {
                    $vVal = $apiObj->keyToLower($vVal);
                    $vKv[$vVal[$vChildName]] = [
                        'parent' => $vVal[$vParentName],
                        "name" => $vVal[$vtName] ?? $vVal[$vChildName]
                    ];
                    if ($vVal[$vParentName] != $vTopValue) {
                        $parentIds[] = $vVal[$vParentName];
                    }
                }
                if (!empty($parentIds)) {
                    $parentKvs = [];
                    while (true) {
                        $vList = $apiObj->tplInit->db($vTable, $vConn)->whereIn($vChildName, $parentIds)->get();
                        $parentIds = [];
                        foreach ($vList as $vVal) {
                            $vVal = $apiObj->keyToLower($vVal);
                            if (isset($parentKvs[$vVal[$vChildName]])) {
                                continue;
                            }
                            $parentKvs[$vVal[$vChildName]] = [
                                'parent' => $vVal[$vParentName],
                                "name" => $vVal[$vtName]
                            ];
                            if ($vVal[$vParentName] != $vTopValue) {
                                $parentIds[] = $vVal[$vParentName];
                            }
                        }

                        if (empty($parentIds)) {
                            break;
                        }
                    }
                }

                $vFlag = $vTree['flag'];
                if (empty($vFlag)) {
                    $vFlag = " / ";
                }

                foreach ($data['src'] as $k => $v) {
                    $srcVk = $v[$key];
                    $srcStr = $vKv[$srcVk]['name'];
                    if (isset($srcStr)) {
                        $srcParentId = $vKv[$srcVk]['parent'];
                        while (true) {
                            $pSrc = $parentKvs[$srcParentId];
                            if (!isset($pSrc)) {
                                break;
                            }
                            $srcStr = $pSrc['name'] . $vFlag . $srcStr;
                            $srcParentId = $pSrc['parent'];
                        }
                        $data['_'][$k][$key] = $srcStr;
                    } elseif (isset($v[$key])) {
                        $data['_'][$k][$key] = $v[$key];
                    } else {
                        $data['_'][$k][$key] = "";
                    }
                }
            }
        }
        if ($apiObj->tplInit->isPost()) {
            $src = $data['src'];
            $pkMd5s = $apiObj->callCommands("getPkMd5s", $src);
            $retData = [];
            $srcData = [];
            $pkMd5Kv = [];
            $sortList = [];
            foreach ($data['_'] as $key => $val) {
                foreach ($src[$key] as $k => $v) {
                    if (!isset($val[$k])) {
                        $val[$k] = $v;
                    }
                }
                $sortKey = $pkMd5s[$key]['md5'];
                $retData[$sortKey] = $val;
                $srcData[$sortKey] = $src[$key];
                $pkMd5Kv[$sortKey] = $pkMd5s[$key]['pk'];
                $sortList[] = $sortKey;
            }
            if (!($apiObj->isTreeAll && $isTree)) {
                EXITJSON(1, "操作成功", [
                    'show' => $retData,
                    'src' => $srcData,
                    'pks' => $pkMd5Kv,
                    'sort' => $sortList
                ]);
            }

            $pts = [];
            $md5KeyVal = [];
            foreach ($src as $key => $val) {
                $sortKey = $pkMd5s[$key]['md5'];
                $md5KeyVal[$val[$tree['child']]] = $sortKey;
                $pts[$val[$tree['parent']]][] = $sortKey;
            }

            $parents = [];
            foreach ($pts as $key => $val) {
                $_shows = [];
                $srcs = [];
                $_pks = [];
                foreach ($val as $vKey) {
                    $_shows[$vKey] = $retData[$vKey];
                    $srcs[$vKey] = $srcData[$vKey];
                    $_pks[$vKey] = $pkMd5Kv[$vKey];
                }
                $pKey = $md5KeyVal[$key];
                empty($pKey) && $pKey = 'top';
                $parents[$pKey] = [
                    'show' => $_shows,
                    'src' => $srcs,
                    'pks' => $_pks,
                    'sort' => $val
                ];
            }
            EXITJSON(1, "操作成功", $parents);
        }
        $types = [];
        foreach ($apiObj->vConfig as $key => $val) {
            isset($val['type']) && $types[$val['type']] = true;
        }

        $export = $_GET['_@export@_'];
        if (in_array($export, ['all', 'checked', 'this', 'print'])) {
            $tv = [];
            foreach ($apiObj->vConfig as $key => $val) {
                if (!$val['hidden']) {
                    $tv[$key] = empty($val['name']) ? $key : $val['name'];
                }
            }
            if ($export == 'checked') {
                $dt = [];
                $_chk_ = trim($_GET['_@check@_']);
                if (!empty($_chk_)) {
                    $checks = explode(",", $_chk_);
                    if (!empty($checks)) {
                        $chk = [];
                        foreach ($checks as $_c) {
                            $chk[trim($_c)] = true;
                        }
                        $src = $data['src'];
                        $pkMd5s = $apiObj->callCommands("getPkMd5s", $src);
                        foreach ($pkMd5s as $key => $val) {
                            if ($chk[$val['md5']]) {
                                $dt[] = $data['_'][$key];
                            }
                        }
                    }
                }
            } else {
                $dt = $data['_'];
            }
            $newDt = [];
            foreach ($dt as $dtk => $dtv) {
                foreach ($tv as $tvk => $tvv) {
                    $newDt[$dtk][$tvk] = $dtv[$tvk];
                }
            }

            $mid = $_GET['_mid_'];
            if (empty($mid)) {
                $title = '';
            } elseif ($mid > 0) {
                if (TphpConfig::$domain['folder']) {
                    $menuList = TphpConfig::$domainPath->plu->call('dir.menus');
                    foreach ($menuList as $ml) {
                        if ($ml['id'] == $mid) {
                            $title = $ml['name'];
                            break;
                        }
                    }
                } else {
                    $title = import("SystemInit", $apiObj->tplInit)->tableMenu()->db()->where("id", "=", $mid)->first()->name;
                }
            } else {
                $menuList = $plu->call('dir.menus:extMenus');
                foreach ($menuList as $key => $val) {
                    if ($mid == $val['id']) {
                        $title = $val['name'];
                        break;
                    }
                }
            }
            empty($title) && $title = "Default";

            if ($export == 'print') {
                $vConf = $apiObj->vConfig;
                $fKv = [];

                $cotW = 0;
                $cotP = 0;
                foreach ($vConf as $vk => $vc) {
                    $vcw = $vc['width'];
                    $fKv[$vk] = $vcw;
                    if (is_numeric($vcw)) {
                        $cotW += $vcw;
                    } else {
                        $cotP++;
                    }
                }
                if ($cotP > 0) {
                    $cotW = $cotW / $cotP;
                }

                return $plu->view("vim.list.print", [
                    'field' => $tv,
                    'fieldKv' => $fKv,
                    'list' => $newDt,
                    'cotWidth' => $cotW,
                    'title' => $title
                ]);
            }
            import('Excel')->export($tv, $newDt, $title);
            EXITJSON(1, "操作成功");
        }

        $sqlLimit = env('SQL_LIMIT', 10000);
        !is_numeric($sqlLimit) && $sqlLimit = 10000;
        $viewData = $apiObj->tplInit->viewData;
        empty($viewData) && $viewData = [];
        $viewData['tplBase'] = $apiObj->baseTplPath;
        $viewData['tplPath'] = $tpl;
        $viewData['tplType'] = $apiObj->type;
        $viewData['list'] = $data['_'];
        $viewData['srcList'] = $data['src'];
        $viewData['field'] = $apiObj->vConfig;
        $viewData['fieldSrc'] = $apiObj->vConfigSrc;
        $viewData['vim'] = $apiObj->vimConfig;
        $viewData['config'] = $config;
        $viewData['pkList'] = $pkList;
        $viewData['operTitle'] = $operTitle;
        $viewData['search'] = $apiObj->search;
        $viewData['isTree'] = $isTree;
        $viewData['types'] = $types;
        $viewData['_DC_'] = TphpConfig::$domain;
        $viewData['sqlLimit'] = $sqlLimit;

        if ($isTree) {
            $viewData['tree'] = $tree;
        }

        if (!empty($data['pageinfo'])) {
            $data['pageinfo']['sizedef'] = $apiObj->tplInit->page['pagesizedef'];
            $viewData['pageinfo'] = $data['pageinfo'];
        }
        $od = $config['config']['order'];
        if (isset($od) && is_array($od)) {
            foreach ($od as $key => $val) {
                $viewData['_sort'] = $key;
                $viewData['_order'] = $val;
                break;
            }
        }

        return $plu->view("vim.list", $viewData);
    }
};
