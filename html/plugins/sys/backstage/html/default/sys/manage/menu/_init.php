<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

return function (){
    if($this->isPost()) {
        $url = trim($_POST['url']);
        $isHttp = strtolower(substr($url, 0, 7)) == 'http://' || strtolower(substr($url, 0, 8)) == 'https://';
        if(!empty($url)) {
            if(!$isHttp) {
                $tmpStr = $url;
                $url = trim($url, " .");
                $posRDot = strrpos($url, ".");
                $ext = '';
                if ($posRDot !== false) {
                    $ext = substr($url, $posRDot);
                    $url = substr($url, 0, $posRDot);
                }
                $posArr = ['.', '?', '#'];
                $posI = -1;
                foreach ($posArr as $val) {
                    $pos = strpos($url, $val);
                    if ($pos !== false) {
                        if ($posI < 0) {
                            $posI = $pos;
                        } elseif ($pos < $posI) {
                            $posI = $pos;
                        }
                    }
                }
                if ($posI > 0) {
                    $url = substr($url, 0, $posI);
                }
                $url = str_replace("\\", "/", $url);
                $url = strtolower(trim(trim($url), '/'));
                $url .= $ext;
            }
            if(empty($url)){
                $this->setPostValue("url", "");
            } else {
                if(!$isHttp){
                    $urlArr = explode("/", $url);
                    if ($urlArr[0] == 'sys') {
                        EXITJSON(0, '不能使用 sys 系统默认目录！');
                    }
                    $url = "/" . $url;
                }
                $tmpStr != $url && $this->setPostValue("url", $url);
            }
        }
        if(!empty($_POST['params'])) {
            $params = $_POST['params'];
            $tmpStr = $params;
            $params = trim(trim($params), "?&");
            $params = trim($params);
            if($params[0] != '#'){
                if($isHttp){
                    if(strpos($url, "?") > 0){
                        $params = "&" . $params;
                    }else{
                        $params = "?" . $params;
                    }
                } else {
                    $params = "?" . $params;
                }
            }
            $params != $tmpStr && $this->setPostValue("params", $params);
        }
    }
};