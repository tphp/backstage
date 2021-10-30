<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

return function ($data){
    $id = $_GET['id'];
    $php = "<?php";
    $url = $data['url'];
    $urlShow = $url;
    $info = [
        'init' => [
            'name' => '初始化',
            'file' => '_init.php',
            'type' => 'php'
        ],
        'data' => [
            'name' => '数据',
            'file' => 'data.php',
            'type' => 'php'
        ],
        'ini' => [
            'name' => '配置',
            'file' => 'ini.php',
            'type' => 'php'
        ],
        'set' => [
            'name' => '数据重设',
            'file' => 'set.php',
            'type' => 'php'
        ],
        'vim' => [
            'name' => '列表',
            'file' => 'vim.php',
            'type' => 'php'
        ],
        'vimh' => [
            'name' => '编辑',
            'file' => 'vimh.blade.php',
            'type' => 'html'
        ],
        'tpl' => [
            'name' => 'HTML',
            'file' => 'tpl.blade.php',
            'type' => 'html'
        ],
        'css' => [
            'name' => 'CSS',
            'file' => 'tpl.css',
            'type' => 'css'
        ],
        'js' => [
            'name' => 'JS',
            'file' => 'tpl.js',
            'type' => 'javascript'
        ]
    ];
    $bPath = base_path($this->configTplBase.$this->getRealTplTop(true));
    $proDir = $bPath.$url."/";
    $xFile = import('XFile');
    if($this->isPost()){
        foreach($_POST as $key=>$val){
            $valTrim = strtolower(trim($val));
            if($valTrim == $php || empty($valTrim)){
                $val = "";
            }
            $file = $proDir.$info[$key]['file'];
            if(empty($val)){
                $xFile->delete($file);
            }else{
                $xFile->write($file, $val);
            }
        }

        //删除空目录
        $urlArr = explode("/", trim(trim($url, "/")));
        $urlX = [];
        $urlStr = "";
        foreach ($urlArr as $val){
            $urlStr .= "/{$val}";
            $urlX[] = $urlStr;
        }
        $urlX = array_reverse($urlX);
        foreach($urlX as $val){
            $tPath = $bPath.$val;
            //目录为空,=2是因为.和..存在
            if(count(scandir($tPath)) == 2){
                rmdir($tPath);
            }
        }
        if(count($_POST) > 1){
            EXITJSON(1, "保存成功（所有）！");
        }else{
            EXITJSON(1, "保存成功！");
        }
    }
    foreach ($info as $key=>$val){
        $value = $xFile->read($proDir.$val['file']);
        if(empty($value) && $val['type'] == 'php'){
            $info[$key]['value'] = $php."\n";
        }else{
            $info[$key]['value'] = $value;
        }
    }
    seo([
        'title' => "TPL: {$url}"
    ]);
    $this->setView('urlShow', $urlShow);
    $this->setView('info', $info);
    $this->setView('id', $id);
    $this->setView('iniUrl', $this->url("../ini"));
    $this->setView("sysFunctionPath", "/". \Tphp\Register::getHtmlPath() . "function");
    return $data;
};