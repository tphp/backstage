<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

return function(){
    $userInfo = $this->userInfo;
    $menuIds = $userInfo['menuIds'];
    $menuIdsGt = [];
    $menuIdsLt = [];
    foreach ($menuIds as $val){
        if($val >= 0){
            $menuIdsGt[] = $val;
        }else{
            $menuIdsLt[] = $val;
        }
    }

    $folder = \Tphp\Config::$domain['folder'];
    $admin = import("SystemInit", $obj)->tableUser()->getAdmin();
    $isAdmin = $admin[strtolower($userInfo['username'])] ?? false;
    if (empty($menuIdsGt) && !$isAdmin) {
        $menuList = [];
    } else {
        if ($folder) {
            $menuListSrc = $this->plu->call('dir.menus');
            if ($isAdmin) {
                $menuList = $menuListSrc;
            } else {
                $menuList = [];
                $menuGtKv = [];
                foreach ($menuIdsGt as $mig) {
                    $menuGtKv[$mig] = true;
                }

                foreach ($menuListSrc as $mls) {
                    if ($menuGtKv[$mls['id']]) {
                        $menuList[] = $mls;
                    }
                }
            }
        } else {
            try {
                $dbConfig = config("database.connections");
                $conn = trim(\Tphp\Config::$domain['conn']);
                $rDriver = $dbConfig[$conn]['driver'];
                if ($rDriver == 'sqlite' && $conn == 'sqlite') {
                    $conn = 'xsqlite';
                }
                $db = import('SystemInit', $this)->tableMenu($conn)->db()->where("status", "=", "1")->select("id", "parent_id", "name", "icon", "url", "params", "sort", "description")->orderBy('sort', 'asc')->orderBy('id', 'desc');
                if (!$isAdmin) {
                    $db->whereIn('id', $menuIdsGt);
                }
                $menuList = $db->get();
            } catch (Exception $e) {
                $menuList = [
                    [
                        'id' => -10000,
                        "parent_id" => 0,
                        "name" => $e->getMessage(),
                        "icon" => "exclamation-triangle",
                        "target" => "expand"
                    ]
                ];
            }
        }
    }

    $menuArr = json_decode(json_encode($menuList, true), true);

    if(empty($menuIds) || !empty($menuIdsLt)) {
        $rData = $this->pluMain()->call('dir.menus:extMenus');
        if (!empty($rData) && is_array($rData)) {
            if(empty($menuIds)){
                foreach ($rData as $val) {
                    $menuArr[] = $val;
                }
            }elseif(!empty($menuIdsLt)){
                foreach ($rData as $val) {
                    if (in_array($val['id'], $menuIdsLt)) {
                        $menuArr[] = $val;
                    }
                }
            }
        }
    }

    $menuKv = [];
    $menuCp = [];
    foreach ($menuArr as $key=>$val){
        $pid = $val['parent_id'];
        $menuKv[$pid] ++;
        $menuCp[$val['id']] = $pid;
        $url = $val['url'];
        $type = $val['type'];
        unset($menuArr[$key]['type']);
        $params = $val['params'];
        unset($menuArr[$key]['params']);
        if(!empty($url)){
            if(!empty($type) && $type != 'html'){
                $url .= ".{$type}";
            }
            if(empty($params)){
                $params = "?_mid_=".$val['id'];
            }else{
                $params .= "&_mid_=".$val['id'];
            }
            if(strpos($url, "?") > 0){
                $url .= "&" . ltrim($params, "?");
            } else {
                $url .= $params;
            }
            $menuArr[$key]['url'] = $url;
        }
    }

    $menuLevel = [];
    foreach ($menuCp as $key=>$val){
        $i = 0;
        $k = $val;
        while(isset($menuCp[$k])){
            $i ++;
            $k = $menuCp[$k];
        }
        $menuLevel[$key] = $i;
    }

    $data = [];
    foreach ($menuArr as $key=>$val){
        if($menuLevel[$val['id']] > 2) continue;
        if($menuLevel[$val['id']] >= 2 || !isset($menuKv[$val['id']])){
            $val['target'] = "iframe";
        }else{
            $val['target'] = "expand";
        }
        $data[] = $val;
    }

    EXITJSON([
        "code" => 200,
        "info" => "响应成功",
        "data" => $data
    ]);
};
