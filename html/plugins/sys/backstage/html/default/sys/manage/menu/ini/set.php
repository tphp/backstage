<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

return function (){
    $dir = $_GET['dir'];
    $apcu = Tphp\Basic\Apcu::__init();
    $sysMenu = $apcu->apcuFetch('_sysmenu_');
    if(!empty($dir)){
        $dir = trim(trim($dir), '/');
        $dirArr = explode("/", $dir);
        foreach ($dirArr as $da){
            $sysMenu = $sysMenu[$da];
            if(!is_array($sysMenu) || empty($sysMenu['_next_'])){
                $sysMenu = [];
                break;
            }
            $sysMenu = $sysMenu['_next_'];
        }
    }
    $sysNote = $apcu->apcuFetch('_sysnote_');
    $this->setView("codeUrl", $this->url("code"));
    $this->setView("sysNote", $sysNote);
    return $sysMenu;
};