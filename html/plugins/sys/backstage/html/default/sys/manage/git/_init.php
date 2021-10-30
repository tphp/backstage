<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

return function (){
    $pluUrl = "";
    $dc = \Tphp\Config::$domain;
    $pluDir = "";
    if (is_array($dc['plu']) && is_string($dc['plu']['dir'])) {
        $pluDir = trim($dc['plu']['dir']);
        $pluGit = \Tphp\Register::getHtmlPath(true) . "/plugins/{$pluDir}";
        if (is_dir($pluGit . "/.git")) {
            $pluUrl = $pluGit;
        }
    }
    $baseUrl = "";
    $baseGit = base_path();
    if(is_dir($baseGit."/.git")){
        $baseUrl = $baseGit;
    }
    if(empty($baseUrl) && empty($pluUrl)){
        __exit("未生成GIT版本控制！");
    }
    $type = "";
    $isPost = false;
    if($this->isPost()){
        $isPost = true;
        $type = $_POST['type'];
        if(!in_array($type, ['pull', 'push', 'reset'])){
            $type = "";
        }
    }

    $list = [];
    if(empty($type) || $type == 'status') {
        if (empty($baseUrl)) {
            $baseList = [];
        } else {
            exec("cd {$baseUrl} && git status", $baseList);
        }
        if (empty($pluUrl)) {
            $pluList = [];
        } else {
            exec("cd {$pluUrl} && git status", $pluList);
        }
    }elseif($type == 'pull'){
        if (empty($baseUrl)) {
            $baseList = [];
        } else {
            exec("cd {$baseUrl} && git pull", $baseList);
        }
        if (empty($pluUrl)) {
            $pluList = [];
        } else {
            exec("cd {$pluUrl} && git pull", $pluList);
        }
    }
    
    if (!empty($baseList)) {
        $list['根目录'] = $baseList;
    }
    
    if (!empty($pluList)) {
        $list["插件目录 | {$pluDir}"] = $pluList;
    }
    $this->setView("list", $list);
    $this->setView("isPost", $isPost);
};