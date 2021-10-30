<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

return function ($data){
    foreach ($data as $key=>$val){
        $url = $val['url'];
        $params = $val['params'];
        if(!empty($url)) {
            if (!empty($url)) {
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
            }
        }
        if(empty($url)) {
            $data[$key]['name'] = "&nbsp;{$val['name']}";
        }else{
            $data[$key]['name'] = "&nbsp;<a href='{$url}' target='_blank'>{$val['name']}</a>";
        }
    }
    return $data;
};
