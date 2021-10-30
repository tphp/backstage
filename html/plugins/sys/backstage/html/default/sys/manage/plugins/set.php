<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

return function (){
    $pluginBasePath = \Tphp\Register::getHtmlPath(true) . "/plugins/";
    $xFile = import('XFile');
    $dirs = $xFile->getDirs($pluginBasePath);
    sort($dirs);
    $tops = [];
    $top = $_GET['top'];
    foreach ($dirs as $dir) {
        $dirName = $dir;
        $name = '';
        $fileName = $pluginBasePath . $dir . "/name";
        if (is_file($fileName)) {
            $name = trim($xFile->read($fileName));
        }
        if (!empty($name)) {
            $dirName .= " | {$name}";
        }
        $tops[$dir] = $dirName;
    }

    if (empty($top) || !isset($tops[$top])) {
        foreach ($tops as $key=>$val) {
            $top = $key;
            break;
        }
    }

    $fields = [
        [
            'title' => 'Base',
            'width' => 60
        ],
        [
            'title' => 'Html',
            'width' => 60
        ],
        [
            'title' => 'Static',
            'width' => 70
        ],
        [
            'title' => 'Config',
            'width' => 70
        ],
        [
            'title' => 'Import',
            'width' => 70
        ],
        [
            'title' => 'Src',
            'width' => 50
        ],
    ];

    $flags = [];
    foreach ($fields as $key => $val) {
        $name = strtolower($val['title']);
        $flags[] = $name;
        $fields[$key]['field'] = $name;
        $fields[$key]['align'] = 'center';
        $fields[$key]['templet'] = '#Tpl' . $val['title'];
    }
    $flags[] = 'help';

    $top = trim($top);
    $childs = [];
    if (!empty($top)) {
        $childSysPath = $pluginSysPath . "{$top}/";
        $childPath = $pluginBasePath . "{$top}/";
        $childDirs = array_merge($xFile->getDirs($childSysPath), $xFile->getDirs($childPath));
        sort($childDirs);
        foreach ($childDirs as $cDir) {
            $info = [
                'dir' => $cDir,
                'full' => $top ."/". $cDir
            ];
            $cPath = $childPath . "{$cDir}/";
            if (!is_dir($cPath)) {
                $cPath = $childSysPath . "{$cDir}/";
            }
            $cNameFile = $cPath . 'name';
            $name = "";
            if (is_file($cNameFile)) {
                $name = trim($xFile->read($cNameFile));
            }
            if (empty($name)) {
                $cHelpPath = $cPath . 'help';
                if (!is_dir($cHelpPath)) {
                    $cPath = $childPath . "{$cDir}/";
                    $cHelpPath = $cPath . 'help';
                }
                if (is_dir($cHelpPath)) {
                    $cHelpFiles = $xFile->getFiles($cHelpPath);
                    if (!empty($cHelpFiles)) {
                        foreach ($cHelpFiles as $chf) {
                            $key = strtolower(trim($chf));
                            if ($key == 'title') {
                                $name = trim($xFile->read($cHelpPath . "/" . $chf));
                                break;
                            }
                        }
                    }
                }
            }
            if (!empty($name)) {
                $info['name'] = $name;
            }
            foreach ($flags as $flag) {
                if (is_dir($cPath . $flag)) {
                    $info[$flag] = true;
                } else {
                    $info[$flag] = false;
                }
            }
            $childs[] = $info;
        }
    }

    $this->setView("top", $top);
    $this->setView("tops", $tops);
    $this->setView("childs", $childs);
    $this->setView("fields", $fields);
};
