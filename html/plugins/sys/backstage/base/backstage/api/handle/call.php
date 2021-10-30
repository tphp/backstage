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
    
    public function config($apiObj)
    {
        // 当没有设置数据库信息时不执行表操作
        $ttc = $apiObj->tplInit->config;
        if (empty($ttc) || !in_array(strtolower($ttc['type']), TplInit::$dataTypeList) || empty($ttc['config']) || empty($ttc['config']['table'])) {
            return false;
        }
        $pks = $apiObj->getPks();
        if (empty($pks) || !is_array($pks)) $apiObj->__exitError("主键设置无效");
        $hInit = $apiObj->vimConfigHandleInit;
        if (empty($hInit)) $apiObj->__exitError("参数设置无效");
        $apiObj->tplInit->getDataForArgs($hInit);
        $unset = [];
        foreach ($hInit as $key => $val) {
            empty($val) && $unset[] = $key;
        }
        foreach ($unset as $val) {
            unset($hInit[$val]);
        }
        if (empty($hInit)) $apiObj->__exitError("参数设置无效");
        $list = $apiObj->tplInit->db("", $apiObj->defaultConn)->where($hInit)->get();
        $cot = count($list);
        if ($cot <= 0) {
            if ($apiObj->tplInit->db("", $apiObj->defaultConn)->insert($hInit)) {
                $data = $apiObj->tplInit->db("", $apiObj->defaultConn)->where($hInit)->first();
            }
        } else {
            $data = $list[0];
        }
        if (empty($data)) $apiObj->__exitError("数据设置出错");

        $pkArr = [];
        $data = $apiObj->keyToLower($data);
        foreach ($pks as $val) {
            $pkArr[$val] = $data[$val];
        }

        if (!empty($pkArr) && $cot > 1) {
            $delDb = $apiObj->tplInit->db("", $apiObj->defaultConn);
            foreach ($pkArr as $key => $val) {
                $delDb->where($key, "<>", $val);
            }
            $delDb->delete();
        }

        $_GET['pk'] = json_encode([json_encode($pkArr, true)], true);
        return self::$plu->call('backstage.api.edit:config', $apiObj);
    }

    public function selectTree($apiObj)
    {
        $key = $_POST['key'];
        $tree = $apiObj->vConfig[$key]['tree'];
        if (empty($tree)) {
            $vh = $apiObj->vimConfigHandle;
            if (isset($vh[$key]) && isset($vh[$key]['tree'])) {
                $tree = $vh[$key]['tree'];
            }
        }
        empty($tree) && $apiObj->__exitError("数据传递无效");
        (!isset($_POST['value'])) && $apiObj->__exitError("数据传递无效");
        $value = $_POST['value'];
        $notValues = $_POST['notValues'];
        empty($notValues) && $notValues = [];
        $isTree = false;
        $treeList = $apiObj->callCommands("getTreeList", $tree, $value, $notValues, $isTree);
        EXITJSON(1, "OK", $treeList);
    }
};