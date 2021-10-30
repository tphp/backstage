<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

namespace Tphp\Basic\Api;
use Tphp\Basic\Sql\Init as SqlInit;
use Tphp\Basic\Tpl\Init as TplInit;
use Tphp\Basic\Sql\SqlCache;

/**
 * 编辑模块
 * Class Vim
 * @package Tphp\Basic
 */
return new class
{

    private $extends;
    private $extendsSrc;
    private $extendsConfigs;
    private $plu;
    private $pluMain;

    public static function __init(TplInit $tplInit, $apiObj)
    {
        $new = new static ();
        $new->tplInit = $tplInit;
        $new->apiObj = $apiObj;
        $new->sqlInit = SqlInit::__init();
        $new->plu = plu(\Tphp\Config::$domainPath->basePluPath);
        $new->pluMain = \Tphp\Config::$domainPath->plu;
        return $new;
    }

    /**
     * 操作其他规则数据库，自定义数据查询
     * @param string $table
     * @return mixed
     */
    protected function db($table = "", $conn = "")
    {
        return $this->tplInit->db($table, $conn);
    }

    /**
     * 退出
     * @param $msg
     * @return mixed
     */
    private function __exitError($msg)
    {
        return $this->apiObj->__exitError($msg);
    }

    /**
     * 获取数据转换值
     * @param $data
     * @param bool $isField
     * @return array
     */
    private function getVimFileChangeData($data, $isField = false)
    {
        $newData = [];
        $tplType = $this->tplInit->tplType;
        $qUrl = "/{$this->tplInit->tplInit}";
        if (is_array($data)) {
            foreach ($data as $key => $val) {
                if (is_int($key)) {
                    is_string($val) && $newData[$val] = [];
                } elseif (is_string($val)) {
                    $newData[$key] = [
                        'name' => $val
                    ];
                } else {
                    if ($isField) {
                        if (is_array($val['click'])) {
                            $val['click'] = $this->tplInit->keyToLower($val['click']);
                            if (isset($val['edit'])) {
                                unset($val['edit']);
                            }
                        }
                    }

                    if (isset($val['bind']) || isset($val['extends'])) {
                        // 新增功能隐藏绑定或扩展
                        if ($tplType == 'add') {
                            continue;
                        }

                        $valType = isset($val['bind']) ? 'bind' : 'extends';
                        if (isset($val['edit'])) {
                            unset($val['edit']);
                        }

                        $extendsName = "";
                        $extendsConfig = [];
                        if (is_array($val[$valType])) {
                            $extendsConfig = $this->tplInit->keyToLower($val[$valType]);
                            $val[$valType] = $extendsConfig;
                            if (is_string($extendsConfig['extends'])) {
                                $extendsName = $extendsConfig['extends'];
                            }
                        }

                        $__type__ = $_GET['__type__'];
                        $_mid_ = $_GET['_mid_'];
                        $_url_ = "{$qUrl}.{$valType}?pk={$pk}&__type__={$extendsName}&__otype__={$__type__}&_mid_={$_mid_}";

                        if ($isField) {
                            $click = $val['click'];
                            if (empty($click)) {
                                $click = [];
                            }

                            foreach (['key', 'ismax'] as $reKey) {
                                if (!isset($click[$reKey]) && isset($extendsConfig[$reKey])) {
                                    $click[$reKey] = $extendsConfig[$reKey];
                                }
                            }

                            $_GET['__type__'] = "";
                            if (!isset($click['key']) || !is_bool($click['key'])) {
                                $click['key'] = false;
                            }
                            $click['bind'] = true;
                            $click['url'] = "{$qUrl}.{$valType}?__type__={$extendsName}&__otype__={$__type__}&_mid_={$_mid_}";
                            $val['click'] = $click;
                        } else {
                            $pk = urlencode($_GET['pk']);
                            $val['src'] = "{$qUrl}.{$valType}?pk={$pk}&__type__={$extendsName}&__otype__={$__type__}&_mid_={$_mid_}";
                            $val['type'] = $valType;
                            empty($val['group']) && $val['group'] = $val['name'] ?? $key;
                            empty($val['key']) && $val['key'] = $key;
                        }
                    }

                    $newData[$key] = $val;
                }
            }
        }
        return $newData;
    }

    /**
     * 设置所有扩展
     * @param array $srcExtends
     * @param array $retExtends
     */
    private function setExtendsAllData($srcExtends = [], &$retExtends = [])
    {
        $newExtents = [];
        foreach ($srcExtends as $keyName => $ext) {
            if (isset($retExtends[$keyName])) {
                continue;
            }

            if (is_string($ext)) {
                $ext = $this->getExtendsData($ext);
            } elseif (!is_array($ext)) {
                continue;
            }

            $retExtends[$keyName] = $ext;

            $extVim = $ext['vim'];
            if (is_array($extVim) && is_array($extVim['extends'])) {
                foreach ($extVim['extends'] as $eKey => $val) {
                    if (is_string($eKey)) {
                        $newExtents[$eKey] = $val;
                    }
                }
            }
        }

        if (!empty($newExtents)) {
            $this->setExtendsAllData($newExtents, $retExtends);
        }
    }

    /**
     * 获取所有扩展
     * @return array
     */
    private function getExtendsAllData()
    {
        if (isset($this->extends)) {
            return $this->extends;
        }

        $extends = [];
        $this->setExtendsAllData($this->extendsSrc, $extends);

        $this->extends = $extends;
        return $extends;
    }
    
    /**
     * 获取文件
     * @param $file
     * @return bool|mixed
     */
    private function includeFile($file)
    {
        if (file_exists($file)) {
            return include $file;
        }

        return null;
    }

    /**
     * 获取配置文件为小写模式
     * @param array $vim
     * @return array
     */
    private function getVimToLower($vim = [])
    {
        foreach (['field', 'handle'] as $c) {
            if (!isset($vim[$c])) {
                continue;
            }
            
            if (!is_array($vim[$c])) {
                unset($vim[$c]);
                continue;
            }
            
            $vc = $vim[$c];
            $nvc = [];
            foreach ($vc as $vcKey => $vcVal) {
                if (is_integer($vcKey)) {
                    if (!is_string($vcVal)) {
                        continue;
                    }

                    $vcVal = strtolower(trim($vcVal));
                    $nvc[$vcVal] = [];
                } else {
                    $nvc[strtolower(trim($vcKey))] = $vcVal;
                }
            }
            $vim[$c] = $nvc;
        }

        return $vim;
    }

    /**
     * 获取扩展数据
     * @param string $callPath
     * @return array
     */
    private function getExtendsData(String $callPath = '')
    {
        $callPath = trim($callPath);
        $ret = [];
        if ($callPath[0] == ':') {
            // PLU模式

            $callPath = ltrim($callPath, ' :');
            $data = [
                'plu' => [
                    'caller' => $callPath
                ]
            ];
            $ret['data'] = $data;
            $vim = $this->plu->call($callPath . ":vim");
            if (!empty($vim) && is_array($vim)) {
                $ret['vim'] = $vim;
            }
        } else {
            // TPL模式
            $cPath = str_replace(".", "/", $callPath);
            $cPath = str_replace("\\", "/", $cPath);
            $cPath = trim($cPath, " /") . "/";
            $bPath = \Tphp\Register::getViewPath($this->apiObj->baseTplPath . $cPath);
            if (!is_dir($bPath)) {
                return $ret;
            }

            $dataFile = $bPath . "data.php";
            if (!is_file($dataFile)) {
                return $ret;
            }

            $callPath = '';
            $data = $this->includeFile($dataFile);
            if (is_array($data)) {
                $ret['data'] = $data;
                if (is_array($data['plu']) && is_string($data['plu']['caller'])) {
                    $callPath = $data['plu']['caller'];
                }
            }

            if (empty($callPath)) {
                $vimFile = $bPath . "vim.php";
                if (is_file($vimFile)) {
                    $vim = $this->includeFile($vimFile);
                    if (!empty($vim) && is_array($vim)) {
                        $ret['vim'] = $this->getVimToLower($vim);
                    }
                }
            } else {
                $vim = $this->plu->call($callPath . ":vim");
                if (!empty($vim) && is_array($vim)) {
                    $ret['vim'] = $this->getVimToLower($vim);
                }
            }

        }
        return $ret;
    }

    /**
     * 获取扩展
     * @param string $keyName
     * @return array
     */
    private function getExtends($keyName = '')
    {
        $ret = [];
        if (!is_string($keyName)) {
            return $ret;
        }

        $keyName = trim($keyName);
        if (empty($keyName)) {
            return $ret;
        }

        $allExtends = $this->getExtendsAllData();

        return $allExtends[$keyName] ?? $ret;
    }

    /**
     * 获取扩展配置
     * @param string $typeSrc
     * @param array $fileVim
     * @return array|mixed
     */
    private function getExtendsConfig($typeSrc = '', $fileVim = [])
    {
        if (isset($this->extendsConfigs)) {
            return $this->extendsConfigs[$typeSrc] ?? [];
        }

        $extendsConfigs = [];
        $allExtends = $this->getExtendsAllData();
        $allExtends['###'] = [
            'vim' => $fileVim
        ];
        foreach ($allExtends as $ae) {
            $vim = $ae['vim'];
            if (empty($vim) || !is_array($vim)) {
                continue;
            }

            foreach (["field", "handle", "oper"] as $conf) {
                $vimConf = $vim[$conf];
                if (empty($vimConf) || !is_array($vimConf)) {
                    continue;
                }

                foreach ($vimConf as $info) {
                    if (empty($info) || !is_array($info))
                    {
                        continue;
                    }
                    foreach (["bind", "extends"] as $type) {
                        $typeInfo = $info[$type];
                        if (empty($typeInfo) || !is_array($typeInfo))
                        {
                            continue;
                        }

                        $extendsName = $typeInfo['extends'];
                        if (!is_string($extendsName)) {
                            continue;
                        }
                        
                        $extendsName = trim($extendsName, " :");
                        if (empty($extendsName)) {
                            continue;
                        }

                        unset($typeInfo['extends']);
                        $typeInfo['type'] = $type;
                        $extendsConfigs[$extendsName] = $typeInfo;
                    }

                }
            }
        }

        $this->extendsConfigs = $extendsConfigs;
        return $this->extendsConfigs[$typeSrc] ?? [];
    }

    /**
     * 获取主键值
     * @param string $keyName
     * @param bool $isPk
     * @return array
     */
    private function getExtendsConfigPks($keyName = '', $isPk = false)
    {
        // 验证数据是否有效
        $keys = [];
        if ($this->tplInit->tplType == 'add' && !$isPk) {
            $pk = $_GET['gpk'];
        } else {
            $pk = $_GET['pk'];
        }
        if (!empty($pk)) {
            $pkArr = json_decode($pk, true);
            if (!empty($pkArr) && is_array($pkArr)) {
                foreach ($pkArr as $pa) {
                    if (is_string($pa)) {
                        $pa = json_decode($pa, true);
                    }
                    if (empty($pa) || !is_array($pa)) {
                        continue;
                    }
                    if (!is_null($pa[$keyName])) {
                        $keys[] = $pa[$keyName];
                    }
                }
            }
        }

        return $keys;
    }

    /**
     * 设置扩展类型：绑定
     * @param $config
     * @param $extendsConfig
     */
    private function setExtendsConfigBind(&$config, $extendsConfig)
    {
        $tplType = $this->tplInit->tplType;
        $isPost = $this->tplInit->isPost();
        if (!in_array($tplType, ['isbind', 'unbind']) && !($isPost && $tplType == 'add' && $_GET['bind'] == 'isbind')) {
            return;
        }

        $table = $extendsConfig['table'];
        if (empty($table)) {
            return;
        }
        $conn = $extendsConfig['conn'];

        // 原表
        $_this = $extendsConfig['this'];
        if (empty($_this) || !is_array($_this) || count($_this) < 2) {
            return;
        }

        list($thisKey, $thisValue) = $_this;
        if (empty($thisKey) || !is_string($thisKey)) {
            return;
        }

        $thisKey = strtolower(trim($thisKey));
        if (empty($thisKey)) {
            return;
        }

        if (empty($thisValue) || !is_string($thisValue)) {
            return;
        }

        $thisValue = strtolower(trim($thisValue));
        if (empty($thisValue)) {
            return;
        }

        // 目标表
        $_that = $extendsConfig['that'];
        if (empty($_that) || !is_array($_that) || count($_that) < 2) {
            return;
        }

        list($thatKey, $thatValue) = $_that;
        if (empty($thatKey) || !is_string($thatKey)) {
            return;
        }

        $thatKey = strtolower(trim($thatKey));
        if (empty($thatKey)) {
            return;
        }

        if (empty($thatValue) || !is_string($thatValue)) {
            return;
        }

        $thatValue = strtolower(trim($thatValue));
        if (empty($thatValue)) {
            return;
        }

        // 验证数据是否有效
        $keys = $this->getExtendsConfigPks($thisKey);
        if (empty($keys)) {
            return;
        }

        // 验证字段是否有效
        $tableInfo = $this->tableInfo($conn, $table);
        if(empty($tableInfo)) {
            $this->__exitError("表 {$table} 不存在");
        }

        if (!isset($tableInfo[$thisValue])) {
            $this->__exitError("表 {$table} 字段 {$thisValue} 不存在");
        }

        if (!isset($tableInfo[$thatValue])) {
            $this->__exitError("表 {$table} 字段 {$thatValue} 不存在");
        }

        // 绑定和解绑操作
        if ($isPost) {
            if ($tplType == 'add') {
                // 回调函数
                $this->apiObj->bindUpdate = function ($pks = []) use ($conn, $table, $keys, $thisValue, $thatKey, $thatValue) {
                    if (empty($pks) || !is_array($pks)) {
                        return;
                    }
                    $pkData = [];
                    foreach ($pks as $pkInfo) {
                        $pkStr = $pkInfo['pk'];
                        if (empty($pkStr)) {
                            continue;
                        }
                        $pkArr = json_decode($pkStr, true);
                        if (empty($pkArr)) {
                            continue;
                        }
                        foreach ($keys as $k) {
                            $pkData[] = [
                                $thisValue => $k,
                                $thatValue => $pkArr[$thatKey]
                            ];
                        }
                    }
                    
                    if (empty($pkData)) {
                        return;
                    }

                    $this->db($table, $conn)->insert($pkData);
                };
                return;
            }

            $src = $_POST['src'];
            if (empty($src)) {
                EXITJSON(0, "无效数据传递");
            }

            $srcArr = json_decode($src, true);
            if (empty($srcArr) || !is_array($srcArr)) {
                EXITJSON(0, "无效数据传递");
            }

            $srcKeys = [];
            foreach ($srcArr as $sVal) {
                $sArr = json_decode($sVal, true);
                if (empty($sArr) || !is_array($sArr)) {
                    continue;
                }
                if (isset($sArr[$thatKey])) {
                    $srcKeys[] = $sArr[$thatKey];
                }
            }

            if (empty($srcKeys)) {
                EXITJSON(0, "无效数据传递");
            }

            $db = $this->db($table, $conn);
            if ($tplType == 'isbind') {
                $count = 0;
                foreach ($keys as $k) {
                    foreach ($srcKeys as $sk) {
                        $count ++;
                        $db->orWhere([
                            [$thisValue, "=", $k],
                            [$thatValue, "=", $sk],
                        ]);
                    }
                }
                $count > 0 && $db->delete();
                EXITJSON(1, "解绑成功");
            }

            $setData = [];
            foreach ($keys as $k) {
                foreach ($srcKeys as $sk) {
                    $setData[] = [
                        $thisValue => $k,
                        $thatValue => $sk
                    ];
                }
            }

            $db->insert($setData);
            EXITJSON(1, "绑定成功");
        }

        // 页面展示
        $values = [];
        $list = $this->db($table, $conn)->select($thatValue)->whereIn($thisValue, $keys)->get();
        foreach ($list as $val) {
            if (!is_null($val->$thatValue)) {
                $values[] = $val->$thatValue;
            }
        }
        
        $where = [];
        $isEmpty = false;

        if (empty($values)) {
            if ($tplType == 'isbind') {
                $isEmpty = true;
                // 先是等于 1 然后不等于 1
                $where = [$thatKey, "=", 1];
            }
        } else {
            if ($tplType == 'isbind') {
                $where = [$thatKey, "=", $values];
            } else {
                $where = [$thatKey, "<>", $values];
            }
        }

        if (empty($where)) {
            return;
        }

        if (empty($config['config'])) {
            $config['config'] = [];
        }

        if (empty($config['config']['where']) || !is_array($config['config']['where'])) {
            $config['config']['where'] = [];
        }

        $config['config']['where'][] = $where;
        if ($isEmpty) {
            $config['config']['where'][] = [$thisKey, '<>', 1];
        }
    }

    /**
     * 设置扩展类型： 扩展
     * @param $config
     * @param $extendsConfig
     */
    private function setExtendsConfigExtends(&$config, $extendsConfig)
    {
        $bind = $extendsConfig['bind'];
        if (empty($bind) || !is_array($bind) || count($bind) < 2) {
            return;
        }

        list($thisKey, $thatKey) = $bind;
        if (!is_string($thatKey) || !is_string($thatKey)) {
            return;
        }

        $thisKey = strtolower(trim($thisKey));
        $thatKey = strtolower(trim($thatKey));

        if (empty($thisKey) || empty($thatKey)) {
            return;
        }

        // 验证数据是否有效
        $keys = $this->getExtendsConfigPks($thisKey);
        if (empty($keys)) {
            return;
        }

        $tplType = $this->tplInit->tplType;
        $allField = $this->getAllField($config);
        if (!isset($allField[$thatKey])) {
            $this->__exitError("表 {$config['config']['table']} 字段 {$thatKey} 不存在");
        }

        $isPost = $this->tplInit->isPost();
        if ($tplType == 'extends' || $tplType == 'edit') {
            $where = $config['config']['where'];
            if (empty($where)) {
                $where = [];
            }
            $where[] = [$thatKey, "=", $keys];
            $config['config']['where'] = $where;
        } elseif ($tplType == 'add') {
            if ($isPost) {
                $_POST[$thatKey] = $keys[0];
            }
        }
    }

    /**
     * 设置扩展配置
     * @param $config
     * @param $extendsConfig
     */
    private function setExtendsConfig(&$config, $extendsConfig)
    {
        $type = $extendsConfig['type'];
        if (!in_array($type, ['bind', 'extends'])) {
            return;
        }

        unset($extendsConfig['type']);

        if (empty($extendsConfig)) {
            return;
        }

        if ($type == 'bind') {
            $this->setExtendsConfigBind($config, $extendsConfig);
        } else {
            $this->setExtendsConfigExtends($config, $extendsConfig);
        }
    }

    /**
     * 转化数据
     * @param array $data
     * @return array
     */
    private function setDataFillToLower($data = [])
    {
        $newData = [];
        foreach ($data as $key => $val) {
            if (is_int($key)) {
                if (is_string($val)) {
                    $val = strtolower(trim($val));
                    !empty($val) && $newData[$val] = [];
                }
                continue;
            }
            
            $key = strtolower(trim($key));
            if (empty($key)) {
                continue;
            }
            
            if (is_string($val)) {
                $newData[$key] = [
                    'name' => $val
                ];
                continue;
            }
            
            if (is_array($val)) {
                $newData[$key] = $this->tplInit->keyToLower($val);
            }
        }
        return $newData;
    }
    
    /**
     * 填充 filed 和 handle
     * @param $data
     * @return mixed
     */
    private function setDataFill($data = [])
    {
        if (!is_array($data)) {
            return $data;
        }

        $field = $data['field'];
        $handle = $data['handle'];

        if (empty($field) || !is_array($field) || empty($handle) || !is_array($handle)) {
            return $data;
        }

        $tplInit = $this->tplInit;
        $shareData = [];

        if ($tplInit->methodType == 'list') {
            $shareData = ['handle', 'field'];
        } else {
            if (!$tplInit->isPost()) {
                $shareData = ['field', 'handle'];
            }
        }

        $data['field'] = $this->setDataFillToLower($field);
        $data['handle'] = $this->setDataFillToLower($handle);
        
        // 列表配置和编辑配置共享
        if (!empty($shareData)) {
            list($sharePrve, $shareNext) = $shareData;
            $vPrve = $data[$sharePrve];
            $vNext = &$data[$shareNext];
            if (!empty($vPrve) && !empty($vNext)) {
                foreach ($vPrve as $hKey => $hVal) {
                    if (!isset($vNext[$hKey]) || empty($hVal)) {
                        continue;
                    }

                    foreach ($hVal as $k => $v) {
                        if (!isset($vNext[$hKey][$k])) {
                            $vNext[$hKey][$k] = $v;
                        }
                    }
                }
            }
        }

        return $data;
    }

    /**
     * 获取vim.php配置数据
     * @return array
     */
    private function getVimFile()
    {
        $tplInit = $this->tplInit;
        $tplType = $tplInit->tplType;
        $config = $tplInit->getDataConfig(false);

        foreach ($tplInit as $key => $val) {
            if (empty($this->$key)) {
                $this->$key = $val;
            }
        }

        $pc = $tplInit->getPluginsConfig();
        $pcVim = [];

        $field = [];
        $tplConfig = $tplInit->config['config'];
        if (!empty($tplConfig) && !empty($tplConfig['table'])) {
            $tplTable = $tplConfig['table'];
            if (is_string($tplTable)) {
                $field = $this->tableInfo("", $tplTable);
            }
        }

        if (is_object($pc) && method_exists($pc, 'vim')) {
            $pcVim = call_user_func_array([$pc, 'vim'], [$field, $tplInit]);
            if (empty($pcVim)) {
                $pcVim = [];
            }
        }

        $vimPath = $tplInit->dataPath . 'vim.php';
        $fileVim = [];
        if (file_exists($vimPath)) {
            $fileVim = $this->includeFile($vimPath);
            if (is_function($fileVim)) {
                $fileVim = $fileVim($field);
            }
        }

        if (empty($fileVim) || !is_array($fileVim)) {
            $fileVim = $pcVim;
        } elseif (!empty($pcVim)) {
            $tplInit->arrayMerge($fileVim, $pcVim, false);
        }

        $fileVim = $this->getVimToLower($fileVim);

        if (is_array($fileVim['extends'])) {
            $this->extendsSrc = $fileVim['extends'];
        } else {
            $this->extendsSrc = [];
        }

        $type = $_GET['__type__'];
        $typeSrc = '';
        if (in_array($tplType, ['bind', 'extends']) && $tplInit->isPost()) {
            $type = $_GET['__otype__'];
        }

        if (is_string($type)) {
            $type = trim($type, " :");
            $pos = strpos($type, ":");
            $typeSrc = $type;
            if ($pos !== false) {
                $type = substr($type, 0, $pos);
            }
        }

        $extends = [];
        $extendsConfig = [];
        $srcFileVim = $fileVim;
        if (is_string($type) && !empty($type)) {
            $extends = $this->getExtends($type);
            $extendsConfig = $this->getExtendsConfig($typeSrc, $fileVim);
            if (!empty($extends)) {
                $fileVim = $extends['vim'] ?? [];
                !is_array($fileVim) && $fileVim = [];
                $bindData = $extends['data'] ?? [];
                !is_array($bindData) && $bindData = [];
                $config = $tplInit->getDataConfig(false, true, $bindData);
            }
        }

        $this->setExtendsConfig($config, $extendsConfig, $tplInit);

        $methodType = $tplType;
        $notIn = [
            'bind',
            'isbind',
            'unbind',
            'extends'
        ];
        if (in_array($methodType, $notIn)) {
            if (isset($tplInit->config) && is_string($tplInit->config['method'])) {
                $cMethod = trim($tplInit->config['method']);
                !empty($cMethod) && $methodType = $cMethod;
            }
        }

        $tplInit->methodType = $methodType;

        // 如果是绑定事件
        if (!$tplInit->isPost() && in_array($tplType, $notIn)) {
            if (empty($extends)) {
                $fileVim = [];
            } elseif ($tplType == 'bind') {
                $qStr = $_SERVER['QUERY_STRING'];
                $qUrl = "/{$tplInit->tplInit}";
                $htmlIsBind = $this->pluMain->view("vim.handle.bind", [
                    "src" => "{$qUrl}.isbind?{$qStr}",
                    "type" => "isbind"
                ]);
                $htmlUnBind = $this->pluMain->view("vim.handle.bind", [
                    "src" => "{$qUrl}.unbind?{$qStr}",
                    "type" => "unbind"
                ]);
                $fileVim = [
                    'handle' => [
                        'bind' => [
                            'type' => 'html',
                            'value' => $htmlIsBind,
                            'group' => '已绑定'
                        ],
                        'unbind' => [
                            'type' => 'html',
                            'value' => $htmlUnBind,
                            'group' => '未绑定'
                        ],
                    ]
                ];
            } else if ($tplType === 'isbind' || $tplType === 'unbind') {
                if ($tplType === 'isbind') {
                    $_name = "解绑";
                } else {
                    $_name = "绑定";
                }
                $oper = [
                    "__{$tplType}__" => [
                        "name" => $_name,
                        "key" => "id",
                        "url" => $typeSrc,
                        "type" => $tplType
                    ]
                ];
                if (is_array($fileVim['oper'])) {
                    foreach ($fileVim['oper'] as $key => $val) {
                        $oper[$key] = $val;
                    }
                }

                $fileVim['oper'] = $oper;
            }
        }

        if (!empty($fileVim) && is_array($fileVim)) {
            $data = $this->setDataFill($tplInit->keyToLower($fileVim));
            if (is_array($data['oper'])) {
                foreach ($data['oper'] as $key => $val) {
                    $data['oper'][$key] = $tplInit->keyToLower($val);
                }
            }
            if (isset($data['field'])) {
                $data['field'] = $this->getVimFileChangeData($data['field'], true);
            }
            if (isset($data['handle'])) {
                $data['handle'] = $this->getVimFileChangeData($data['handle'], false);
            }
        } else {
            $data = [];
        }

        if (method_exists($tplInit, '__vim')) {
            call_user_func_array([$tplInit, '__vim'], [&$data, &$field]);
        }

        if ($tplInit->config['type'] == 'dir') {
            if (!is_array($data['tree'])) {
                $data['tree'] = [];
            }

            $data['tree']['parent'] = 'parent_id';
            $data['tree']['child'] = 'id';
            $data['tree']['value'] = '';
        }

        return [$data, $config];
    }


    /**
     * 获取所有字段信息
     * @param $config
     * @return array
     */
    private function getAllField($config)
    {
        if (in_array(strtolower($config['type']), ['sql', 'sqlfind'])) {
            $c = $config['config'];
            empty($c['conn']) && $c['conn'] = config('database.default');
            $tableInfo = $this->tableInfo($c['conn'], $c['table']); //获取某个表的所有字段信息
            $pluFields = [];
            $tplConfig = $this->tplInit->config;

            // 如果插件中字段存在注释，则优先使用插件注释
            if (is_array($tplConfig) && is_array($tplConfig['plu']) && is_array($tplConfig['plu']['field'])) {
                $pluFields = $tplConfig['plu']['field'];
            }

            if (!empty($pluFields)) {
                foreach ($pluFields as $key => $val) {
                    if (!is_array($tableInfo[$key])) {
                        continue;
                    }

                    $comment = $val['comment'];
                    if (empty($comment) || !is_string($comment)) {
                        continue;
                    }

                    $comment = trim($comment);
                    if (empty($comment)) {
                        continue;
                    }

                    $tableInfo[$key]['name'] = $comment;
                    
                    // 列表和编辑默认设置
                    foreach (['field', 'handle'] as $fdName) {
                        $valFd = $val[$fdName];
                        if (empty($valFd) || !is_array($valFd)) {
                            continue;
                        }
                        $info = [];
                        foreach ($valFd as $k => $v) {
                            if (!is_string($k)) {
                                continue;
                            }
                            $info[$k] = $v;
                        }
                        if (!empty($info)) {
                            $tableInfo[$key][$fdName] = $info;
                        }
                    }
                }
            }
            return $tableInfo;
        }
        return [];
    }

    /**
     * 获取其他所有字段信息
     * @param $config
     * @return array
     */
    private function tableInfo($conn = "", $table = "")
    {
        list($table, $conn) = $this->sqlInit->getPluginTable($table, $conn, $this->tplInit);
        return SqlCache::getTableInfo($conn, $table); //获取某个表的所有字段信息
    }

    /**
     * 获取新的配置文件
     * @param $config
     * @param $vimField
     */
    private function getNewConfig($config, $vimConfig, &$allField)
    {
        $vimField = $vimConfig['field'];
        if (in_array(strtolower($config['type']), ['sql', 'sqlfind'])) {
            $cField = $config['config']['field']; //配置中的字段信息
        } else {
            $allField = [];
            if (is_array($vimConfig['field'])) {
                foreach ($vimConfig['field'] as $key => $val) {
                    if (is_array($val)) {
                        $allField[$key] = $val;
                    } elseif (is_string($val)) {
                        $allField[$val] = [
                            "name" => $val
                        ];
                    }
                }
            }
            $vimHandle = $vimConfig['handle'];
            if (is_array($vimHandle)) {
                foreach ($vimHandle as $key => $val) {
                    if (is_array($val)) {
                        foreach ($val as $k => $v) {
                            $allField[$key][$k] = $v;
                        }
                    } elseif (is_string($val)) {
                        $allField[$key][$val]['name'] = $val;
                    }
                }
            }
            $cField = $allField;
        }
        $cfNames = [];

        if (empty($cField)) $cField = [];

        $fieldKeyVal = [];
        foreach ($cField as $key => $val) {
            if (is_string($val)) {
                if (!empty($allField)) {
                    $cfNames[] = $val;
                }
            } elseif (is_array($val)) {
                foreach ($val as $k => $v) {
                    if (!empty($v[3])) {
                        if (is_string($v[3])) {
                            $cfNames[] = $v[3];
                        } elseif (is_array($v[3])) {
                            foreach ($v[3] as $kk => $vv) {
                                if (is_string($vv)) {
                                    $cfNames[] = $vv;
                                    if (is_string($kk)) {
                                        $fieldKeyVal[$vv] = $kk;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        $this->fieldKeyVal = $fieldKeyVal;

        $cfNames = array_unique($cfNames);

        $vimFieldNew = [];
        if (empty($vimField)) $vimField = [];
        foreach ($vimField as $key => $val) {
            $fName = "";
            if (is_string($key)) {
                $fName = $key;
            } elseif (is_string($val)) {
                $fName = $val;
            }
            if (empty($fName)) continue;
            if (empty($allField[$fName]) && !in_array($fName, $cfNames)) {
                $isContinue = true;
                if (is_array($val) && (is_array($val['from']) || $val['custom'])) {
                    $isContinue = false;
                }
                if ($isContinue) {
                    continue;
                }
            }
            $name = $allField[$fName]['name'];

            if (is_int($key)) {
                if (is_string($val)) {
                    if (empty($name) && is_string($val)) $name = $val;
                    $vimFieldNew[$val] = [
                        'name' => $name
                    ];
                }
            } else {
                if (is_string($val)) {
                    $vimFieldNew[$key] = [
                        'name' => $val
                    ];
                } elseif (is_array($val)) {
                    if (empty($val['name'])) {
                        if (empty($name)) {
                            $val['name'] = $key;
                            $val['system'] = true;
                        } else {
                            $val['name'] = $name;
                        }
                    }
                    foreach ($val as $k => $v) {
                        $vimFieldNew[$key][strtolower(trim($k))] = $v;
                    }
                }
            }
        }

        //获取最终的数据库配置信息
        $cFieldNew = [];
        foreach ($cField as $key => $val) {
            if (is_string($val)) {
                !empty($vimFieldNew[$val]) && $cFieldNew[] = $val;
            } elseif (is_array($val)) {
                $tf = [];
                $tfDel = [];
                foreach ($val as $k => $v) {
                    $tfIn = [$v[0], $v[1], $v[2]];
                    $tfDel[] = $tfIn;
                    $names = "";
                    if (is_array($v[0])) {
                        $names = $this->tableInfo($v[0][1], $v[0][0]);
                    }
                    if (!empty($v[3])) {
                        if (is_string($v[3])) {
                            if (!empty($vimFieldNew[$v[3]])) {
                                $tfIn[3][] = $v[3];
                                $vimFieldNew[$v[3]]['find'] = $tfDel;

                                if ($vimFieldNew[$v[3]]['name'] == "#") {
                                    $vimFieldNew[$v[3]]['name'] = $names[$v[3]]['name'];
                                }

                                if (empty($vimFieldNew[$v[3]]['name'])) {
                                    $vimFieldNew[$v[3]]['name'] = $v[3];
                                }
                            }
                        } elseif (is_array($v[3])) {
                            foreach ($v[3] as $kk => $vv) {
                                if (is_string($vv) && !empty($vimFieldNew[$vv])) {
                                    $tfIn[3][$kk] = $vv;
                                    $vimFieldNew[$vv]['find'] = $tfDel;
                                    if ($vimFieldNew[$vv]['name'] == "#") {
                                        $vimFieldNew[$vv]['name'] = $names[$vv]['name'];
                                    }
                                    if (empty($vimFieldNew[$vv]['name'])) {
                                        $vimFieldNew[$vv]['name'] = $vv;
                                    }
                                }
                            }
                        }
                    }
                    $tf[] = $tfIn;
                }
                $i = count($tf) - 1;
                for (; $i >= 0; $i--) {
                    if (empty($tf[$i][3])) {
                        unset($tf[$i]);
                    } else {
                        break;
                    }
                }

                !empty($tf) && is_string($tf) && $cFieldNew[] = $tf;
            }
        }

        foreach ($vimFieldNew as $key => $val) {
            if (!$val['hidden']) {
                $tp = $val['type'];
                empty($tp) && $tp = $key;
                if (in_array($tp, ['create_time', 'update_time', 'time'])) {
                    !isset($val['fixed']) && $vimFieldNew[$key]['fixed'] = true;
                    !isset($val['order']) && $vimFieldNew[$key]['order'] = true;
                    !isset($val['width']) && $vimFieldNew[$key]['width'] = 115;
                    $vimFieldNew[$key]['align'] = 'center';
                }
            }
        }

        //增加data.php文件中未定义的但在vim.php中定义的字段
        $allWidth = 0;
        foreach ($vimFieldNew as $key => $val) {
            if (!$val['hidden']) {
                if (!in_array($key, $cfNames)) {
                    $cFieldNew[] = $key;
                }
                if (empty($val['width']) || $val['width'] <= 0) {
                    $dw = 20;
                } else {
                    $dw = $val['width'];
                }
                !isset($val['fixed']) && !$val['fixed'] && $allWidth += $dw;
                $vimFieldNew[$key]['width'] = $dw;
            }

        }

        foreach ($vimFieldNew as $key => $val) {
            if (!$val['hidden']) {
                if ($val['fixed']) {
                    $vimFieldNew[$key]['width'] = $val['width'];
                } else {
                    $vimFieldNew[$key]['width'] = round($val['width'] * 100 / $allWidth, 2) . "%";
                }
            }
        }

        $isFixed = 'true';
        $allWidth > 0 && $isFixed = 'false';
        return [$cFieldNew, $vimFieldNew, $isFixed];
    }

    /**
     * 设置字段相关值
     * @param $fields
     */
    private function setFields(&$fields, $config)
    {
        if (empty($fields) || !is_array($fields)) return;
        if (isset($config['config']) && isset($config['config']['conn'])) {
            $connDef = $config['config']['conn'];
        }
        empty($connDef) && $connDef = config('database.default');
        $fromIsset = [];
        foreach ($fields as $key => $val) {
            if (!(is_string($key) && is_array($val))) continue;
            if (isset($val['from'])) {
                $from = $val['from'];
                if (isset($from[0], $from[1], $from[2])) {
                    $table = $from[0];
                    if (is_array($table)) {
                        list($table, $conn) = $table;
                        if (empty($table)) continue;
                    }
                    if (empty($conn) && !empty($from[3]) && is_string($from[3])) {
                        $conn = $from[3];
                    }
                    list($table, $conn) = $this->sqlInit->getPluginTable($table, $conn, $this->tplInit);
                    if (empty($table)) continue;
                    $fd = $from[1];
                    if (is_array($fd)) {
                        list($fd) = $fd;
                    }
                    $fv = $from[2];
                    empty($conn) && $conn = $connDef;
                    if (!empty($table) && !empty($fd) && !empty($fv) && is_string($table) && is_string($fd) && is_string($fv)) {
                        $fds = $this->tableInfo($conn, $table);
                        $list = [];
                        if (!empty($fds) && !empty($fds[$fd]) && !empty($fds[$fv])) {
                            $tFlag = "{$conn}#{$table}#{$fd}#{$fv}";
                            if (isset($fromIsset[$tFlag])) {
                                $lst = $fromIsset[$tFlag];
                            } else {
                                $lstDb = $this->tplInit->db($table, $conn)->select($fd, $fv);
                                // 排序设置
                                $fo = $val['fromorder'];
                                if (!empty($fo) && is_array($fo)) {
                                    foreach ($fo as $fok => $fov) {
                                        $fok = trim(strtolower($fok));
                                        $fov = trim(strtolower($fov));
                                        if (is_string($fok) && isset($fds[$fok]) && in_array($fov, ['asc', 'desc'])) {
                                            $lstDb->orderby($fok, $fov);
                                        }
                                    }
                                }
                                $fw = $val['fromwhere'];
                                if (!empty($fw) && is_array($fw)) {
                                    $fw = $this->getWhereRealList($fw, $fds);
                                    $this->tplInit->setWhere($lstDb, $fw, $fds);
                                }

                                $lst = $lstDb->get();
                            }
                            foreach ($lst as $k => $v) {
                                $list[$v->$fd] = $v->$fv;
                            }
                            $fromIsset[$tFlag] = $lst;
                        }
                        $fields[$key]['list'] = $list;
                    }
                }
            }
            if (empty($val['type'])) {
                if (is_array($fields[$key]['list'])) {
                    $fields[$key]['type'] = 'select';
                } elseif ($key == 'time') {
                    $fields[$key]['type'] = 'time';
                }
            }

            if (isset($val['trees']) && !empty($val['trees']) && is_array($val['trees'])) {
                $fields[$key]['type'] = 'trees';
                $fields[$key]['tree'] = $val['trees'];
                unset($fields[$key]['trees']);
            }
        }
    }

    /**
     * 获取真实的条件查询语句
     * @param $where
     * @param array $field
     * @return array
     */
    public function getWhereRealList($where, $field = [])
    {
        $sWhere = [];
        if (empty($where) || empty($field)) {
            return $sWhere;
        }

        if (!empty($where) && is_array($where)) {
            foreach ($where as $key => $val) {
                if (is_int($key)) {
                    if (is_string($val)) {
                        if (!empty($where[1]) && is_string($where[1]) && isset($where[2])) {
                            $sWhere[] = [strtolower(trim($val)), $where[1], $where[2]];
                        }
                        break;
                    } elseif (is_array($val)) {
                        if (!empty($val[0]) && !empty($val[1]) && isset($val[2])) {
                            $sWhere[] = [strtolower(trim($val[0])), $val[1], $val[2]];
                        }
                    }
                }
            }
        }
        $retWhere = [];
        foreach ($sWhere as $key => $val) {
            $v0 = $val[0];
            $v1 = strtolower(trim($val[1]));
            $v2 = $val[2];
            if (isset($field[$v0]) && in_array($v1, ['=', '>', '>=', '<=', '<', '<>', 'like'])) {
                if (!empty($v2) || $v2 === 0 || $v2 === '0') {
                    $retWhere[] = [$v0, $v1, $v2];
                }
            }
        }
        return $retWhere;
    }

    /**
     * 获取data.php中的数据并进行修改配置
     * @param $tpl
     */
    public function getDataConfig()
    {
        $tplInit = $this->tplInit;
        list($vimConfig, $config) = $this->getVimFile();
        $allField = $this->getAllField($config);
        
        foreach (['field', 'handle'] as $fType) {
            if (is_array($vimConfig[$fType])) {
                foreach ($vimConfig[$fType] as $key => $val) {
                    if (is_array($val) && is_array($vimConfig[$fType][$key])) {
                        foreach ($val as $k => $v) {
                            !isset($vimConfig[$fType][$key][$k]) && $vimConfig[$fType][$key][$k] = $v;
                        }

                        // 置入插件默认值
                        if (isset($allField[$key]) && is_array($allField[$key][$fType])) {
                            foreach ($allField[$key][$fType] as $k => $v) {
                                !isset($vimConfig[$fType][$key][$k]) && $vimConfig[$fType][$key][$k] = $v;
                            }
                        }
                    }
                }
            }
        }

        if ($tplInit->methodType == 'list') {
            $this->setFields($vimConfig['field'], $config);
        } else {
            if ($tplInit->isPost()) {
                if (is_null($vimConfig['handle'])) {
                    $vimConfig['handle'] = [];
                }
                $vField = $vimConfig['field'];
                if (!empty($vField)) {
                    foreach ($vField as $fKey => $fVal) {
                        if (!is_array($fVal)) {
                            continue;
                        }
                        !isset($vimConfig['handle'][$fKey]) && $vimConfig['handle'][$fKey];
                        foreach ($fVal as $k => $v) {
                            if (isset($vimConfig['handle'][$fKey][$k])) {
                                continue;
                            }
                            $vimConfig['handle'][$fKey][$k] = $v;
                        }
                    }
                }
            }
            $this->setFields($vimConfig['handle'], $config);
        }

        if (isset($vimConfig['tree'])) {
            $tree = $tplInit->keyToLower($vimConfig['tree']);
            if (isset($tree['edit'])) {
                $treeEdit = $tree['edit'];
            } else {
                $treeEdit = true;
            }
            $tType = $tplInit->config['type'];
            $parent = strtolower(trim($tree['parent']));
            $child = strtolower(trim($tree['child']));
            if ((isset($allField[$parent]) && isset($allField[$child])) || $tType == 'dir') {
                $tree['parent'] = $parent;
                $tree['child'] = $child;
                $vimConfig['tree'] = $tree;
                !empty($config['config']['table']) && $tree['table'] = $config['config']['table'];
                if ($tType == 'dir') {
                    $vName = $tree['name'] ?? '目录';
                    if (!isset($vimConfig['field']['id'])) {
                        $vimConfig['field']['id'] = [
                            'hidden' => true,
                            'name' => $vName,
                            'verify' => 'required'
                        ];
                        !empty($tree['delete']) && $vimConfig['field']['id']['delete'] = $tree['delete'];
                    }
                    !empty($config['config']['dir']) && $tree['dir'] = $config['config']['dir'];
                } else {
                    $vName = $tree['name'] ?? '分类';
                }
                if (!isset($vimConfig['field'][$parent])) {
                    $vimConfig['field'][$parent] = [
                        'hidden' => true,
                        'name' => $vName,
                        'type' => 'tree',
                        'tree' => $tree
                    ];
                } else {
                    $vimConfig['field'][$parent]['type'] = 'tree';
                    $vimConfig['field'][$parent]['tree'] = $tree;
                    !isset($vimConfig['field'][$parent]['name']) && $vimConfig['field'][$parent]['name'] = $vName;
                }

                if ($treeEdit && !empty($vimConfig['handle'])) {
                    $handle = [];
                    if ($tType == 'dir') {
                        $dirIds = $this->getExtendsConfigPks('id', true);
                        if (empty($dirIds[0]) || !is_string($dirIds[0])) {
                            $dirValue = "";
                        } else {
                            $dirValue = trim($dirIds[0], " /") . "/";
                        }

                        $handle[$child] = [
                            'type' => 'dir',
                            'value' => $dirValue,
                            'tree' => $tree
                        ];
                        if (in_array($this->tplType, ['add', 'edit', 'handle', 'copy'])) {
                            $handle[$child]['list'] = $this->pluMain->call('backstage.http.dir:getDirAllList', $tree['dir'], $tree['field'], $tree['file']);
                        }


                    } else {
                        $handle[$parent] = [
                            'batch' => '批量分组'
                            //'verify' => 'required|phone'
                        ];
                    }

                    foreach ($vimConfig['handle'] as $key => $val) {
                        $handle[$key] = $val;
                    }

                    if ($tType == 'dir') {
                        foreach ($handle as $key => $val) {
                            if (isset($val['batch'])) {
                                unset($handle[$key]['batch']);
                            }
                        }
                    }

                    $vimConfig['handle'] = $handle;
                }
            } else {
                unset($vimConfig['tree']);
            }
        }
        !isset($vimConfig['ispage']) && $vimConfig['ispage'] = true;
        list($fConfig, $vConfig, $isFixed) = $this->getNewConfig($config, $vimConfig, $allField);
        $retConfig = [];
        if ($vimConfig['ispage']) {
            $retConfig['ispage'] = true;
            if ($vimConfig['pagesize'] > 0) {
                $retConfig['pagesize'] = $vimConfig['pagesize'];
            }
        }
        foreach ($vConfig as $key => $val) {
            if (!isset($allField[$key])) {
                //智能去除功能：编辑、排序
//                unset($vConfig[$key]['edit']);
                unset($vConfig[$key]['order']);
                if (!isset($val['from']) && !is_array($val['from']) && !isset($val['find']) && !is_array($val['find']) && $val['custom'] !== true) {
                    unset($vConfig[$key]['search']);
                }
            }
        }

        $tmpFo = [];
        foreach ($fConfig as $key => $val) {
            if (is_string($val)) {
                $tmpFo[$val] = true;
                continue;
            }
            if (is_string($key)) {
                $tmpFo[$key] = true;
                continue;
            }
            if (!is_array($val)) continue;
            foreach ($val as $k => $v) {
                $v3 = $v[3];
                if (is_string($v3)) {
                    $tmpFo[$v3] = true;
                    continue;
                }
                if (!is_array($v3)) continue;
                foreach ($v3 as $kk => $vv) {
                    if (is_string($vv)) $tmpFo[$vv] = true;
                }
            }
        }

        if (!empty($allField)) {
            //增加主键字段（列表对应必须）
            foreach ($allField as $key => $val) {
                if ($val['key'] == 'PRI') {
                    !isset($vConfig[$key]) && $fConfig[] = $key;
                } elseif (isset($vConfig[$key]) && !$tmpFo[$key]) {
                    $fConfig[] = $key;
                }
            }
        }

        $retConfig['config']['field'] = $fConfig;
        !empty($config['config']['order']) && $retConfig['config']['order'] = $config['config']['order'];
        !empty($config['config']['where']) && $retConfig['config']['where'] = $config['config']['where'];
        $handleInfo = $tplInit->keyToLower($vimConfig['handleinfo']);
        empty($handleInfo) && $handleInfo = [];
        empty($handleInfo['width']) && $handleInfo['width'] = 500;
        empty($handleInfo['height']) && $handleInfo['height'] = 400;
        empty($handleInfo['fixed']) && $handleInfo['fixed'] = false;
        empty($handleInfo['ismax']) && $handleInfo['ismax'] = false;
        $vimConfig['handleinfo'] = $handleInfo;
        $vimConfig['isfixed'] = $isFixed;

        $handleInit = []; //默认处理配置
        $hInit = $vimConfig['handleinit'];
        if (!empty($hInit) && is_array($hInit)) {
            foreach ($hInit as $key => $val) {
                if (!empty($key) && !empty($val) && is_string($key) && isset($allField[$key])) {
                    if (is_string($val) || is_numeric($val)) {
                        $handleInit[$key] = $val;
                    }
                }
            }
            if (empty($handleInit)) {
                unset($vimConfig['handleinit']);
            } else {
                $vimConfig['handleinit'] = $handleInit;
            }
        }

        if (!empty($handleInit)) {
            $vHandle = $vimConfig['handle'];
            $keyTmp = [];
            foreach ($vHandle as $key => $val) {
                $keyName = "";
                if (is_int($key)) {
                    if (!empty($val) && is_string($val)) $keyName = $val;
                } elseif (is_string($key)) {
                    $keyName = $key;
                    if ($val['type'] == 'image' && is_array($val['thumbs'])) {
                        foreach ($val['thumbs'] as $k => $v) {
                            if (isset($allField[$k])) {
                                $retConfig['config']['field'][] = $k;
                            }
                        }
                    }
                }
                if (!empty($keyName) && isset($handleInit[$keyName])) {
                    $keyTmp[] = $key;
                }
            }
            if (!empty($keyTmp)) {
                foreach ($keyTmp as $val) {
                    unset($vHandle[$val]);
                }
            }
            $vimConfig['handle'] = $vHandle;
        }

        //字段搜索加入
        $fieldTmp = [];
        foreach ($retConfig['config']['field'] as $val) {
            if (is_string($val)) {
                $fieldTmp[$val] = true;
            }
        }
        foreach ($vConfig as $key => $val) {
            $vFrom = $val['from'];
            if (is_array($vFrom) && is_array($vFrom[1]) && count($vFrom[1]) > 1) {
                $vfkey = $vFrom[1][1];
                if (is_string($vfkey) && !isset($fieldTmp[$vfkey]) && isset($allField[$vfkey])) {
                    $retConfig['config']['field'][] = $vfkey;
                    $fieldTmp[$vfkey] = true;
                }
            }
        }

        // 再次共享数据
        $vimConfig = $this->setDataFill($vimConfig);
        
        return [$retConfig, $vConfig, $vimConfig, $allField];
    }
};
