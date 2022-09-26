<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

use Tphp\Basic\Http;

return new class
{
    private static $xFile;
    private static $ini;
    private static $extDefault = 'default';
    private static $order;

    function __construct()
    {
        self::$xFile = import('XFile');
        self::$ini = import('Ini');
        $_sort = strtolower(trim($_GET['_sort']));
        $_order = strtolower(trim($_GET['_order']));
        if (!empty($_sort) && !empty($_sort)) {
            self::$order = [$_sort, $_order];
        }
    }

    /**
     * 获取目录列表，过滤 "." 开头的文件夹
     * @param string $dir
     * @return array
     */
    private static function __getDirList($dir = '')
    {
        $dirList = self::$xFile->getDirs($dir);
        $retList = [];
        foreach ($dirList as $dl) {
            if ($dl[0] == '.') {
                continue;
            }
            $retList[] = $dl;
        }
        
        return $retList;
    }

    /**
     * 获取目录
     * @param string $dir
     * @return array
     */
    private static function getList($dir = '', $subDir = '', $notValues = [])
    {
        $subDir = trim($subDir, "/ ");
        if (empty($subDir)) {
            $baseDir = $dir;
        } elseif (empty($dir)) {
            $baseDir = $subDir;
        } else {
            $baseDir = "{$dir}/{$subDir}";
        }

        $nValues = [];
        foreach ($notValues as $nv) {
            if (is_string($nv)) {
                $nValues[$nv] = true;
            }
        }

        $dirList = self::__getDirList($baseDir);
        $retList = [];
        foreach ($dirList as $dl) {
            if (empty($subDir)) {
                $dirFull = $dl;
            } else {
                $dirFull = "{$subDir}/{$dl}";
            }

            if ($nValues[$dirFull]) {
                continue;
            }

            $retList[] = [
                'id' => $dirFull,
                'parent_id' => $subDir,
                'dir' => $dl,
                '@child' => count(self::__getDirList("{$baseDir}/{$dl}"))
            ];
        }

        return $retList;
    }

    /**
     * 获取所有列表
     * @param string $dir
     * @param array $retList
     */
    private static function getAllList($dir = '', $subDir = '', &$retList = [], $baseDir = '')
    {
        if (empty($dir)) {
            $tDir = "";
        } else {
            $tDir = "/{$dir}";
        }
        $list = self::getList($baseDir, $subDir . $tDir);
        foreach ($list as $lst) {
            $retList[] = $lst;
            if ($lst['@child'] > 0) {
                self::getAllList($lst['dir'], $lst['parent_id'], $retList, $baseDir);
            }
        }
    }
    
    private static function getFieldExt($field = [])
    {
        $retInfo = [];
        $files = [];
        foreach ($field as $key => $val) {
            if (!is_string($key)) {
                continue;
            }

            $key = str_replace(" ", "", $key);

            if ($val['file'] == true) {
                $files[$key] = $val;
                continue;
            }

            $pos = strpos($key, ":");
            if ($pos !== false) {
                $vField = trim(substr($key, 0, $pos));
                $key = trim(substr($key, $pos + 1));
            }

            if (empty($key)) {
                continue;
            }

            if (empty($vField)) {
                $vField = self::$extDefault; 
            }
            
            if (!isset($retInfo[$vField])) {
                $retInfo[$vField] = [];
            }

            $retInfo[$vField][$key] = $val;
        }
        
        $defaultInfo = $retInfo[self::$extDefault];
        if (isset($defaultInfo)) {
            unset($retInfo[self::$extDefault]);
        } else {
            $defaultInfo = [];
        }
        
        return [$defaultInfo, $retInfo, $files];
    }

    /**
     * 获取详细信息
     * @param $dirPath
     * @param $dField
     * @param $eField
     * @param $fField
     * @param $oldValue
     * @return array
     */
    private static function getDirInfo($dirPath, $dField, $eField, $fField, $oldValue)
    {
        $tDir = $dirPath;
        $filename = "{$tDir}/_.ini";
        $info = self::$ini->setPath($filename)->readAll();

        if(file_exists($filename)) {
            $updateTime = filemtime($filename);
        }else{
            $updateTime = 0;
        }
        if($updateTime > 0){
            $time = date("Y-m-d H:i:s", $updateTime);
        }else{
            $time = "-";
        }

        $defInfo = $info[self::$extDefault];
        if (empty($defInfo)) {
            $defInfo = [];
        } else {
            unset($info[self::$extDefault]);
        }

        $rInfo = [
            'id' => null,
            'parent_id' => null,
            'dir' => null,
            '@child' => null,
            'update_time' => null
        ];

        foreach ($dField as $dfKey => $dfVal) {
            if (isset($defInfo[$dfKey])) {
                $rInfo[$dfKey] = $defInfo[$dfKey];
            } else {
                $rInfo[$dfKey] = "";
            }
        }

        foreach ($eField as $eKey => $eVal) {
            $efInfo = $info[$eKey];
            if (!isset($efInfo)) {
                $efInfo = [];
            }

            foreach ($eVal as $k => $v) {
                $kKey = "{$eKey}:{$k}";
                if (isset($efInfo[$k])) {
                    $rInfo[$kKey] = $efInfo[$k];
                } else {
                    $rInfo[$kKey] = "";
                }
            }
        }

        foreach ($fField as $fKey => $fVal) {
            if ($fKey == '_.ini') {
                continue;
            }
            $rfKey = str_replace(":", ".", $fKey);
            $rFile = "{$tDir}/{$rfKey}";
            if ($fVal['type'] == 'status') {
                if (is_file($rFile)) {
                    $rInfo[$fKey] = 1;
                } else {
                    $rInfo[$fKey] = 0;
                }
            } else {
                $rInfo[$fKey] = self::$xFile->read($rFile);
            }
        }

        $rInfo['id'] = $oldValue['id'];
        $rInfo['parent_id'] = $oldValue['parent_id'];
        $rInfo['dir'] = $oldValue['dir'];
        $rInfo['@child'] = $oldValue['@child'];
        $rInfo['update_time'] = $time;
        return $rInfo;
    }

    /**
     * 数组排序
     * @param array $list
     * @param array $config
     * @return array
     */
    private static function getOrderList($list = [], $config = [])
    {
        $order = self::$order;
        
        if (empty($order)) {
            $cOrder = $config['order'];
            if (is_array($cOrder)) {
                foreach ($cOrder as $cKey => $cVal) {
                    if (is_string($cKey) && is_string($cVal)) {
                        $order = [$cKey, $cVal];
                    }
                }
            }
        }
        
        if (empty($order)) {
            return $list;
        }

        list($_sort, $_order) = $order;

        $orderList = [];
        foreach ($list as $rl) {
            $rlVal = $rl[$_sort];
            if (isset($rlVal)) {
                if (is_array($rlVal) || is_object($rlVal)) {
                    $rlVal = '';
                } else {
                    $rlVal = "{$rlVal}";
                }
            } else {
                $rlVal = '';
            }

            if (!isset($orderList[$rlVal])) {
                $orderList[$rlVal] = [];
            }

            $orderList[$rlVal][] = $rl;
        }

        if ($_order == 'asc') {
            ksort($orderList);
        } else {
            krsort($orderList);
        }

        $list = [];

        foreach ($orderList as $val) {
            foreach ($val as $v) {
                $list[] = $v;
            }
        }

        return $list;
    }

    /**
     * 获取列表详情
     * @param string $dir
     * @param array $list
     * @param array $fields
     * @return array
     */
    private static function getDirInfoList($dir = '', $list = [], $fields = [])
    {
        $newFields = [];
        if (empty($fields)) {
            foreach ($fields as $key => $val) {
                if (is_integer($key)) {
                    if (is_string($val)) {
                        $newFields[strtolower(trim($val))] = [];
                    }
                } else {
                    if (!is_array($val)) {
                        $val = [];
                    }
                    $newFields[strtolower(trim($key))] = $val;
                }
            }

            unset($newFields['id']);
            unset($newFields['parend_id']);
            unset($newFields['dir']);
            unset($newFields['@child']);
        }

        if (!empty($newFields)) {
            list($dField, $eField, $fField) = self::getFieldExt($newFields);

            foreach ($list as $key => $val) {
                $list[$key] = self::getDirInfo("{$dir}/{$val['id']}", $dField, $eField, $fField, $val);
            }

            foreach ($list as $key => $val) {
                foreach ($newFields as $k => $v) {
                    if (!isset($val[$k])) {
                        $list[$key][$k] = '';
                    }
                }
            }
        }

        return $list;
    }

    /**
     * 获取目录数据
     * @param string $dir
     * @param string $subDir
     * @param array $fields
     * @return mixed
     */
    public static function getDirList($dir = '', $subDir = '', $fields = [], $notValues = [], $isTree = true, &$retList = [])
    {
        $list = self::getList($dir, $subDir, $notValues);

        $list = self::getDirInfoList($dir, $list, $fields);

        foreach ($list as $lst) {
            $retList[] = $lst;
        }

        if ($isTree && $subDir != '') {
            $pos = strrpos($subDir, '/');
            if ($pos === false) {
                $subDir = '';
            } else {
                $subDir = substr($subDir, 0, $pos);
            }
            return self::getDirList($dir, $subDir, $fields, $notValues, $isTree, $retList);
        }

        return $retList;
    }
    
    /**
     * 去除目录键值
     * @param $groupList
     * @param string $parendDir
     * @param array $retList
     */
    private static function getDirTreeDeleteKey($groupList, $parendDir = '', &$retList=[]){
        $list = $groupList[$parendDir];
        if (!empty($list)) {
            foreach ($list as $key => $val) {
                $retList[] = $val;
                $rKey = count($retList) - 1;
                if (isset($groupList[$val['id']])) {
                    $retList[$rKey]['children'] = [];
                    self::getDirTreeDeleteKey($groupList, $val['id'], $retList[$rKey]['children']);
                }
            }
        }
    }

    public static function getDirAllList($dir = '', $fieldName = '', $isFile = false)
    {
        $list = [];
        self::getAllList('', '', $list, $dir);

        $fields = [];
        if (is_string($fieldName)) {
            $fieldName = strtolower(trim($fieldName));
            if (!empty($fieldName)) {
                if ($isFile) {
                    $fields[$fieldName] = [
                        'file' => true
                    ];
                } else {
                    $fields[$fieldName] = [];
                }
            }
        } else {
            $fieldName = '';
        }

        if (!empty($fields)) {
            list($dField, $eField, $fField) = self::getFieldExt($fields);
            foreach ($list as $key => $val) {
                $list[$key] = self::getDirInfo("{$dir}/{$val['id']}", $dField, $eField, $fField, $val);
            }
        }

        $groups = [];
        foreach ($list as $lst) {
            $pId = $lst['parent_id'];
            if (!isset($groups[$pId])) {
                $groups[$pId] = [];
            }

            $_dir = $lst['dir'];
            $title = trim($lst[$fieldName]);
            if (empty($title) || $title == $_dir) {
                $title = $_dir;
            } else {
                $title = "{$_dir}&nbsp;&nbsp;<span class=\"is_tree\">{$title}</span>";
            }
            
            $groups[$pId][$_dir] = [
                'id' => $lst['id'],
                'title' => $title
            ];
        }

        $retList = [];
        self::getDirTreeDeleteKey($groups, '', $retList);
        return $retList;
    }

    /**
     * 获取配置信息
     * @param array $posts
     * @return array
     */
    private static function getIniConfig($posts = [], $allField = [])
    {
        $retInfo = [];
        $files = [];
        $id = strtolower(trim($posts['id'], " \\/"));
        unset($posts['id']);
        unset($posts['dir']);
        foreach ($posts as $key => $val) {
            $keySrc = $key;
            $pos = strpos($key, ':');
            if ($pos === false) {
                $index = self::$extDefault;
            } else {
                $index = trim(substr($key, 0, $pos));
                if (empty($index)) {
                    $index = self::$extDefault;
                }
                $key = trim(substr($key, $pos + 1));
            }

            if (!isset($retInfo[$index])) {
                $retInfo[$index] = [];
            }

            if ($allField[$keySrc]['file']) {
                $files[$keySrc] = [$val, $allField[$keySrc]];
            } else {
                $retInfo[$index][$key] = $val;
            }
        }

        return [$id, $retInfo, $files];
    }

    /**
     * 验证目录
     * @param $dir
     */
    private static function checkedDir($dir = '')
    {
        if(!preg_match('/^\w+$/i', str_replace("/", "", $dir))){
            self::exitJson(0, "文件夹只允许字母、数字和下划线");
        }
    }

    /**
     * 列表模式
     * @param $config
     * @param null $apiObj
     * @param array $pkInfo
     * @return array
     */
    private static function runList($config, $apiObj = null, $pkInfo = [])
    {
        $dir = $config['dir'];

        $type = $_POST['type'];

        $subDir = $_POST['value'];
        if (!isset($subDir)) {
            $subDir = '';
        }

        if ($type == 'all') {
            $list = [];
            self::getAllList('', $subDir, $list, $dir);
        } else {
            $list = self::getList($dir, $subDir);
        }

        $field = $apiObj->allField;

        $aoType = $apiObj->type;
        $fieldShow = $apiObj->vimConfigField;

        list($dField, $eField, $fField) = self::getFieldExt($field);

        foreach ($fieldShow as $key => $val) {
            if (isset($field[$key])) {
                $fieldShow[$key] = [
                    'name' => $field[$key]['name'],
                    'key' => '',
                    'type' => 'text'
                ];
            }
        }

        foreach ($list as $key => $val) {
            $list[$key] = self::getDirInfo("{$dir}/{$val['id']}", $dField, $eField, $fField, $val);
        }

        foreach ($list as $key => $val) {
            foreach ($fieldShow as $k => $v) {
                if (!isset($val[$k])) {
                    $list[$key][$k] = '';
                }
            }
        }

        $list = self::getOrderList($list, $config);

        return [1, $list, $fieldShow, [], ''];
    }

    /**
     * 错误模式
     * @param $status
     * @param string $msg
     */
    private static function exitJson($status, $msg = '')
    {
        if (count($_POST) > 0) {
            EXITJSON($status, $msg);
        }

        __exit($msg);
    }

    /**
     * 获取主键
     * @return array
     */
    private static function getPk()
    {
        $pk = $_GET['pk'];

        if (empty($pk)) {
            return [false, "参数无效"];
        }

        $pkArr = json_decode($pk, true);
        if (!is_array($pkArr) || !is_string($pkArr[0])) {
            return [false, "参数无效"];
        }

        $pkInfo = json_decode($pkArr[0], true);
        if (!is_array($pkInfo) || !is_string($pkInfo['id'])) {
            return [false, "参数无效"];
        }

        $pkId = str_replace(".", "", $pkInfo['id']);
        $pkId = str_replace("\\", "/", $pkId);
        $pkId = str_replace("//", "/", $pkId);
        $pkId = trim($pkId, " /");
        if (empty($pkId)) {
            return [false, "参数无效"];
        }

        return [true, $pkId];
    }

    /**
     * 编辑模式
     * @param $config
     * @param null $apiObj
     * @param array $pkInfo
     * @return array
     */
    private static function runEdit($config, $apiObj = null, $pkInfo = [])
    {
        list($status, $pkId) = $pkInfo;
        if (!$status) {
            self::exitJson(0, $pkId);
        }

        $dir = $config['dir'];
        $baseDir = "{$dir}/{$pkId}";

        if (!is_dir($baseDir)) {
            self::exitJson(0, "目录无效");
        }

        $field = $apiObj->allField;

        $fieldShow = $apiObj->vimConfigField;

        list($dField, $eField, $fField) = self::getFieldExt($field);

        foreach ($fieldShow as $key => $val) {
            if (isset($field[$key])) {
                $fieldShow[$key] = [
                    'name' => $field[$key]['name'],
                    'key' => '',
                    'type' => 'text'
                ];
            }
        }

        $parentId = "";
        $diDir = "";
        $pos = strrpos($pkId, "/");
        if ($pos === false) {
            $diDir = $pkId;
        } else {
            $parentId = substr($pkId, 0, $pos);
            $diDir = substr($pkId, $pos + 1);
        }

        $dirInfo = [
            'id' => $pkId,
            'parent_id' => $parentId,
            'dir' => $diDir,
            '@child' => count(self::__getDirList($baseDir))
        ];

        $dirInfo = self::getDirInfo($baseDir, $dField, $eField, $fField, $dirInfo);

        foreach ($fieldShow as $k => $v) {
            if (!isset($dirInfo[$k])) {
                $dirInfo[$k] = '';
            }
        }

        if ($apiObj->tplInit->isPost()) {
            $aoType = $apiObj->type;
            if (in_array($aoType, ['add', 'edit'])) {
                $configEdit = $config[$aoType];
                if (is_array($configEdit)) {
                    foreach ($dirInfo as $k => $v) {
                        if (isset($configEdit[$k])) {
                            $dirInfo[$k] = $configEdit[$k];
                        }
                    }
                }
            }
        }

        $list = [$dirInfo];

        return [1, $list, $fieldShow, [], ''];
    }

    /**
     * 获取真实路径
     * @param string $path
     * @return string
     */
    private static function getRealDir($path = '')
    {
        if (is_null($path) || !is_string($path)) {
            return '';
        }

        $path = str_replace(" ", "", trim($path, "/\\ "));
        $path = str_replace("\\", "/", $path);
        $retPaths = [];
        $paths = explode("/", $path);
        foreach ($paths as $p) {
            if ($p == '') {
                continue;
            }
            $retPaths[] = $p;
        }

        return implode("/", $retPaths);
    }

    /**
     * 复制路径
     * @param $config
     * @param null $apiObj
     * @return array
     */
    private static function runCopy($config, $apiObj = null)
    {
        $src = self::getRealDir($_POST['src']);
        $new = self::getRealDir($_POST['new']);
        if (empty($src)) {
            self::exitJson(0, "原文件夹不能为空");
        }

        if (empty($new)) {
            self::exitJson(0, "新文件夹不能为空");
        }

        if ($src == $new) {
            self::exitJson(0, "原文件夹和新文件夹不能相同");
        }

        self::checkedDir($new);

        $srcLen = strlen($src);
        $newLen = strlen($new);
        if ($newLen > $srcLen) {
            if (substr($new, 0, $srcLen + 1) == $src . "/") {
                self::exitJson(0, "新目录不能复制到原目录内");
            }
        }

        $dir = $config['dir'];
        $baseDir = "{$dir}/{$src}";

        if (!is_dir($baseDir)) {
            self::exitJson(0, "原文件夹无效");
        }

        $newPos = strrpos($new, "/");
        $newRoot = "";
        $newLastDir = $new;
        if ($newPos !== false) {
            $newRoot = substr($new, 0, $newPos);
            $newLastDir = substr($new, $newPos + 1);
            if (!is_dir("{$dir}/{$newRoot}")) {
                self::exitJson(0, "新文件夹父目录 {$newRoot} 无效");
            }
        }

        $newDir = "{$dir}/{$new}";
        if (is_dir($newDir)) {
            self::exitJson(0, "目录 {$new} 已存在");
        }

        self::$xFile->copy($baseDir, $newDir);

        $idInfo = $apiObj->allField['id'];
        $deletes = [];
        if (is_array($idInfo) && !empty($idInfo['delete'])) {
            $iiDelete = $idInfo['delete'];
            if (is_string($iiDelete)) {
                $iiDelete = trim($iiDelete);
                $iiDelete = str_replace("/", "", $iiDelete);
                $iiDelete = str_replace("\\", "", $iiDelete);
                if (!empty($iiDelete)) {
                    $deletes[strtolower($iiDelete)] = true;
                }
            } elseif (is_array($iiDelete)) {
                foreach ($iiDelete as $iid) {
                    if (!is_string($iid)) {
                        continue;
                    }

                    $iid = trim($iid);
                    $iid = str_replace("/", "", $iid);
                    $iid = str_replace("\\", "", $iid);
                    if (!empty($iid)) {
                        $deletes[strtolower($iid)] = true;
                    }
                }
            }
        }

        if (!empty($deletes)) {
            $newDirList = self::$xFile->getAllDirs($newDir);
            $newDirList[] = $new;
            $deleteFiles = [];
            foreach ($newDirList as $ndl) {
                foreach ($deletes as $d => $dVal) {
                    $df = "{$dir}/{$ndl}/{$d}";
                    if (is_file($df)) {
                        $deleteFiles[] = $df;
                    }
                }
            }

            foreach ($deleteFiles as $df) {
                self::$xFile->delete($df);
            }
        }

        $field = $apiObj->allField;

        $fieldShow = $apiObj->vimConfigField;

        list($dField, $eField, $fField) = self::getFieldExt($field);

        foreach ($fieldShow as $key => $val) {
            if (isset($field[$key])) {
                $fieldShow[$key] = [
                    'name' => $field[$key]['name'],
                    'key' => '',
                    'type' => 'text'
                ];
            }
        }

        $dirInfo = [
            'id' => $new,
            'parent_id' => $newRoot,
            'dir' => $newLastDir,
            '@child' => count(self::__getDirList($newDir))
        ];

        $dirInfo = self::getDirInfo($newDir, $dField, $eField, $fField, $dirInfo);

        foreach ($fieldShow as $k => $v) {
            if (!isset($dirInfo[$k])) {
                $dirInfo[$k] = '';
            }
        }

        $list = [$dirInfo];

        return [1, $list, $fieldShow, [], ''];
    }

    /**
     * 保存数据到文件
     * @param $saveDir
     * @param $iniInfo
     * @param $files
     */
    private static function runSaveData($saveDir, $iniInfo, $files)
    {
        self::$ini->setPath("{$saveDir}/_.ini")->writeAll($iniInfo);
        foreach ($files as $key => $val) {
            list($v, $c) = $val;
            $fileName = str_replace(":", ".", $key);
            $filePath = "{$saveDir}/{$fileName}";
            $vTrim = trim($v);
            if ($vTrim == '') {
                self::$xFile->delete($filePath);
            } elseif ($c['type'] == 'status') {
                if ($vTrim == '0') {
                    self::$xFile->delete($filePath);
                } else {
                    self::$xFile->write($filePath, '');
                }
            } else {
                self::$xFile->write($filePath, $v);
            }
        }
    }

    /**
     * 新增数据
     * @param $pkInfo
     * @param $iniDir
     * @param $iniInfo
     * @param $files
     * @param $config
     * @param null $apiObj
     */
    private static function runSaveAdd(&$pkInfo, $iniDir, $iniInfo, $files, $config, $apiObj = null)
    {
        $dir = $config['dir'];
        self::checkedDir($iniDir);
        $baseDir = "{$dir}/{$iniDir}";
        if (is_dir($baseDir)) {
            self::exitJson(0, "文件夹：<br>{$iniDir}<br>已存在!");
        }

        $pos = strrpos($iniDir, "/");
        if ($pos !== false) {
            $parentDir = substr($iniDir, 0, $pos);
            $prevDir = "{$dir}/{$parentDir}";
            if (!is_dir($prevDir)) {
                self::exitJson(0, "无效目录 {$parentDir}");
            }
        }

        $pkInfo[0] = true;
        $pkInfo[1] = $iniDir;
        self::runSaveData($baseDir, $iniInfo, $files);
    }

    /**
     * 编辑数据
     * @param $pkInfo
     * @param $iniDir
     * @param $iniInfo
     * @param $files
     * @param $config
     * @param null $apiObj
     */
    private static function runSaveEdit(&$pkInfo, $iniDir, $iniInfo, $files, $config, $apiObj = null)
    {
        $aoType = $apiObj->type;

        list($status, $pkId) = $pkInfo;
        if (!$status && $aoType == 'edit') {
            self::exitJson(0, $pkId);
        }

        $dir = $config['dir'];

        $baseSrc = "{$dir}/$pkId";
        if (!is_dir($baseSrc)) {
            self::exitJson(0, '原文件夹无效');
        }

        $baseNow = $baseSrc;

        if ($iniDir !== $pkId) {
            $pos = strrpos($iniDir, "/");
            if ($pos !== false) {
                $parentDir = substr($iniDir, 0, $pos);
                $prevDir = "{$dir}/{$parentDir}";
                if (!is_dir($prevDir)) {
                    self::exitJson(0, "无效目录 {$parentDir}");
                }
            }

            $pkIdPath = "{$pkId}/";
            $pkIdPathLen = strlen($pkIdPath);
            $iniDirLen = strlen($iniDir);
            if ($iniDirLen > $pkIdPathLen) {
                $iniDirSub = substr($iniDir, 0, $pkIdPathLen);
                if ($iniDirSub == $pkIdPath) {
                    self::exitJson(0, '父目录不能为当前目录');
                }
            }

            self::checkedDir($iniDir);

            $baseNow = "{$dir}/{$iniDir}";
            if (is_dir($baseNow)) {
                self::exitJson(0, "文件夹：<br>{$iniDir}<br>已存在!");
            }

            rename($baseSrc, $baseNow);

            $pkInfo[1] = $iniDir;
        }

        self::runSaveData($baseNow, $iniInfo, $files);
    }

    /**
     * 数据保存
     * @param $config
     * @param null $apiObj
     * @param array $pkInfo
     */
    private static function runSave($config, $apiObj = null, &$pkInfo = [])
    {
        if (!$apiObj->tplInit->isPost()) {
            return;
        }

        $aoType = $apiObj->type;

        $allField = $apiObj->allField;

        $posts = [];
        foreach ($_POST as $key => $val) {
            $key = strtolower(trim($key));
            if (isset($allField[$key])) {
                $posts[$key] = $val;
            }
        }

        if (!in_array($aoType, ['add', 'edit', 'handle'])) {
            return;
        }

        list($iniDir, $iniInfo, $files) = self::getIniConfig($posts, $allField);
        if (empty($iniDir) && is_array($pkInfo)) {
            $iniDir = $pkInfo[1];
        }

        if (empty($iniDir)) {
            self::exitJson(0, "文件夹不能为空!");
        }

        if (empty($iniInfo)) {
            return;
        }

        if ($apiObj->tplInit->isPost()) {
            if (in_array($aoType, ['add', 'edit'])) {
                $configEdit = $config[$aoType];
                if (is_array($configEdit)) {
                    foreach ($iniInfo as $k => $v) {
                        foreach ($v as $_k => $_v) {
                            if (isset($configEdit["{$k}:${_k}"])) {
                                $iniInfo[$k][$_k] = $configEdit["{$k}:${_k}"];
                            } elseif ($k == 'default' && isset($configEdit[$_k])) {
                                $iniInfo[$k][$_k] = $configEdit[$_k];
                            }
                        }
                    }
                }
            }
        }

        if ($aoType == 'add') {
            self::runSaveAdd($pkInfo, $iniDir, $iniInfo, $files, $config, $apiObj);
        } else {
            self::runSaveEdit($pkInfo, $iniDir, $iniInfo, $files, $config, $apiObj);
        }
    }

    /**
     * 删除文件夹
     * @param $config
     * @param null $apiObj
     * @param array $pkInfo
     */
    private static function runDelete($config, $apiObj = null)
    {
        if (!$apiObj->tplInit->isPost()) {
            self::exitJson(0, "操作错误!");
        }

        $data = $_POST['data'];
        if (!is_array($data)) {
            self::exitJson(0, "参数错误!");
        }

        $pkStr = $data[0];
        if(empty($pkStr) || !is_string($pkStr)){
            self::exitJson(0, "参数错误！");
        }

        $pkInfo = json_decode($pkStr, true);
        if (empty($pkInfo) && !is_array($pkInfo)) {
            self::exitJson(0, "参数错误！");
        }

        $pkDir = $pkInfo['id'];
        if(empty($pkDir) && !is_string($pkDir)){
            self::exitJson(0, "目录不能为空！");
        }

        $pkDir = trim($pkDir, " \\/");

        if (empty($pkDir)) {
            self::exitJson(0, "目录不能为空！");
        }

        $dir = $config['dir'];
        $deletePath = "{$dir}/{$pkDir}";
        if (!is_dir($deletePath)) {
            self::exitJson(0, "目录不存在: <br> {$pkDir}");
        }

        self::$xFile->deleteDir($deletePath);

        self::exitJson(1, "删除成功!");

    }

    /**
     * 目录接口数据
     * @param $config
     * @param null $apiObj
     * @return array
     */
    public static function run($config, $apiObj = null)
    {
        $dir = $config['dir'];
        if (!is_dir($dir)) {
            self::exitJson(0, "无效目录！");
        }
        
        $aoType = $apiObj->type;

        if (in_array($aoType, ['sys', 'selectTree'])) {
            return [1];
        }

        $pkInfo = self::getPk();

        self::runSave($config, $apiObj, $pkInfo);

        if ($aoType == 'list') {
            return self::runList($config, $apiObj, $pkInfo);
        } else if ($aoType == 'delete') {
            return self::runDelete($config, $apiObj);
        }

        if ($apiObj->tplInit->isPost()) {
            if ($aoType == 'copy') {
                return self::runCopy($config, $apiObj);
            }
        } else {
            if (in_array($aoType, ['add', 'handle'])) {
                return [1];
            }
        }

        if (!in_array($aoType, ['add', 'edit', 'edits'])) {
            return [1];
        }

        return self::runEdit($config, $apiObj, $pkInfo);
    }
};