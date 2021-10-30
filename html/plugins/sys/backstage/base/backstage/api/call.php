<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

use Tphp\Basic\Sql\Init as SqlInit;
use Tphp\Basic\Tpl\Init as TplInit;
use Tphp\Register;

return new class
{
    public static $plu;

    /**
     * 获取Vim组件
     * @return mixed
     */
    private function getVim()
    {
        if (!empty($this->tplInit->vim)) {
            return $this->tplInit->vim;
        }
        $this->tplInit->vim = self::$plu->call('backstage.vim:__init', $this->tplInit, $this);
        return $this->tplInit->vim;
    }

    /**
     * 运行公共函数
     * @param string $funName
     * @param mixed ...$args
     * @return mixed
     */
    public function callCommands($funName = '', &...$args)
    {
        $call = self::$plu->getCall("backstage.api.commands:{$funName}", $this);
        if (empty($call)) {
            return;
        }
        return $call($this, ...$args);
    }

    /**
     * 获取用户信息
     * @return mixed
     */
    public function getUserInfo()
    {
        return $this->callCommands("getUserInfo", $this->tplInit, $this->vim);
    }

    /**
     * 键值转化为小写
     * @param $value
     * @return mixed
     */
    public function keyToLower($value)
    {
        return $this->tplInit->keyToLower($value);
    }

    /**
     * 错误消息提醒
     * @param $msg
     */
    public function __exitError($msg)
    {
        if ($this->tplInit->isPost()) {
            EXITJSON(0, $msg);
        } else {
            if (!is_string($msg)) {
                $msg = json_encode($msg, true);
            }
            __exit($msg);
        }
    }

    public function getSqlFlag($db)
    {
        $flag = '"';
        $driver = $db->connection->getConfig()['driver'];
        if (empty($driver) || !is_string($driver)) {
            return $flag;
        }

        $driver = strtolower(trim($driver));

        if ($driver == 'mysql') {
            $flag = '`';
        }

        return $flag;
    }

    /**
     * 设置处理方式
     * @param $handles
     */
    private function setHandles(&$handles)
    {
        $tpl = $this->tpl;
        $ida = $this->getUserInfo()['menuIda'];
        empty($md5s) && $md5s = [];
        $keys = array_keys($handles);
        foreach ($keys as $val) {
            $hv = $handles[$val];
            $isUrl = false;
            if (is_array($hv)) {
                if (isset($hv['url'])) {
                    $t = $tpl . "/" . $hv['url'];
                    $isUrl = true;
                }
            }
            if (!$isUrl) {
                if (is_string($hv)) {
                    $tp = $val;
                    $tp == 'handle' && $tp = 'edit';
                    $t = $tpl . "." . $tp;
                } else {
                    continue;
                }
            }
            $md5 = substr(md5($t), 8, 8);
            if (isset($ida[$md5])) {
                unset($handles[$val]);
            }
        }
    }

    public function page404($msg = "404 Page Error!")
    {
        if (count($_POST) > 0) {
            EXITJSON(0, $msg);
        } else {
            __exit($msg);
        }
    }

    /**
     * 设置配置项
     * 初始化数据
     */
    private function getConfig()
    {
        list($this->retConfig, $this->vConfig, $vimConfig, $this->allField) = $this->vim->getDataConfig($this->tplInit);

        // 如果非后台，data.php配置文件中必须设置 vim = true 才能访问
        if (\Tphp\Config::$domain['backstage'] !== true && $this->tplInit->config['vim'] !== true) {
            if (in_array($this->tplInit, ['list', 'add', 'edit', 'handle', 'bind', 'isbind', 'unbind', 'extends'])) {
                $this->page404();
            }
        }

        $vConfig = $this->vConfig;
        $operWidth = 20; //单个操作宽度

        //分页设置
        $config = [];
        $type = $this->type;

        //操作窗口标题
        $operTitle = "";
        if (!empty($vimConfig['field'])) {
            foreach ($vimConfig['field'] as $key => $val) {
                if (is_array($val) && $val['title']) {
                    $operTitle = $key;
                    break;
                }
            }
        }

        //如果标题未找到则使用主键
        if (empty($operTitle) && !empty($this->allField)) {
            foreach ($this->allField as $key => $val) {
                if ($val['key'] == 'PRI') {
                    $operTitle = $key;
                    break;
                }
            }
        }

        $this->operTitle = $operTitle;

        if (empty($vimConfig['field'])) {
            $this->vimConfigField = [];
        } else {
            $this->vimConfigField = $vimConfig['field'];
        }

        if (empty($vimConfig['handle'])) {
            $this->vimConfigHandle = [];
        } else {
            $this->vimConfigHandle = $vimConfig['handle'];
        }

        if (empty($vimConfig['handleinit'])) {
            $this->vimConfigHandleInit = [];
        } else {
            $this->vimConfigHandleInit = $vimConfig['handleinit'];
        }

        if (empty($vimConfig['delete'])) {
            $this->vimConfigDelete = [];
        } else {
            $this->vimConfigDelete = $vimConfig['delete'];
        }

        if (empty($vimConfig['tree'])) {
            $this->vimConfigTree = [];
        } else {
            $this->vimConfigTree = $vimConfig['tree'];
        }

        $tvcc = $this->vim->config['config'];
        $defaultConn = $tvcc['conn'];
        if (empty($defaultConn)) {
            $defaultConn = \Tphp\Config::$domain['conn'];
        }
        $this->defaultConn = $defaultConn;
        $this->defaultTable = $tvcc['table'];

        $reset = [];
        if (isset($vimConfig['field'])) {
            $reset['field'] = &$vimConfig['field'];
        }
        if (isset($vimConfig['handle'])) {
            $reset['handle'] = &$vimConfig['handle'];
        }
        foreach ($reset as $key => $val) {
            foreach ($val as $k => $v) {
                $_type = '';
                if (isset($v['tree'])) {
                    $_info = &$reset[$key][$k]['tree'];
                    $_type = 'tree';
                } elseif (isset($v['trees'])) {
                    $_info = &$reset[$key][$k]['trees'];
                    $_type = 'tree';
                } else {
                    continue;
                }

                if ($_type == 'tree') {
                    $table = $_info['table'];
                    if (empty($table)) {
                        continue;
                    }

                    $isArray = false;
                    if (is_array($table)) {
                        list($table, $conn) = $table;
                        $isArray = true;
                    } else {
                        $conn = "";
                    }
                    if (empty($conn)) {
                        $conn = $defaultConn;
                    }
                    list($table, $conn) = $this->sqlInit->getPluginTable($table, $conn, $this->tplInit);
                    if ($isArray) {
                        $_info['table'] = [$table, $conn];
                    } else {
                        $_info['table'] = $table;
                    }
                }
            }
        }

        // 自动加载配置模式
        $tplType = $this->tplInit->tplType;
        if (empty($tplType)) {
            $tplType = 'json';
        }

        $this->tplInit->plu = $this->tplInit->getPluObject();
        
        // 先运行 _init.php 或 _init 方法
        $di = $this->tplInit->getDataInit();
        if ($di !== true) {
            if (is_array($di) && isset($di[1])) {
                EXITJSON($di[1]);
            }
        }
        
        $methodName = ":{$tplType}";
        if ($this->tplInit->hasMethod($methodName) && isset($this->methods[$methodName])) {
            $config = $this->tplInit->getMethod($methodName)($this, $vimConfig);
        }

        //操作设置
        $newHandle = [];
        $batch = [];
        $handles = [];
        $oper = $vimConfig['oper'];
        (empty($oper) || !is_array($oper)) && $oper = [];

        $operUnset = [];
        $this->setHandles($oper);
        foreach ($oper as $key => $val) {
            if (!is_array($val) || !is_string($key)) {
                $operUnset[] = $key;
                continue;
            }

            if (!isset($val['url']) && !isset($val['bind']) && !isset($val['extends'])) {
                $operUnset[] = $key;
                continue;
            }

            !isset($val['name']) && $val['name'] = $key;
            $kName = $val['name'];
            $kNameLen = (strlen($kName) + mb_strlen($kName, 'UTF8')) / 4;
            $operWidth += 12 * $kNameLen + 22;
        }
        foreach ($operUnset as $val) unset($oper[$val]);

        $cType = $this->tplInit->config['type'];

        $is = $vimConfig['is'];
        $handle = $vimConfig['handle'];
        if (empty($handle)) {
            unset($is['add']);
            unset($is['edit']);
        }

        if (!empty($vimConfig['tree'])) {
            $vTree = $vimConfig['tree'];
            if (isset($vTree['edit'])) {
                $treeEdit = $vTree['edit'];
            } else {
                $treeEdit = $is['add'] ?? false;
            }
            $batch['open_close'] = "全部展开";
            if ($treeEdit) {
                if ($cType == 'dir') {
                    $oper['copy'] = "复制";
                }
                $oper['add'] = "新增";
            }
        }

        $is['add'] && $batch['add'] = '新增';

        if ($is['view']) {
            $oper['view'] = "查看";
        }

        if (!empty($handle) && is_array($handle)) {
            foreach ($handle as $key => $val) {
                if (is_numeric($key)) {
                    $newHandle[$val] = ['name' => $val];
                } elseif (is_string($val) || is_numeric($val)) {
                    $newHandle[$key] = ['name' => $val];
                } elseif (is_array($val)) {
                    if (empty($val['name'])) {
                        $val['name'] = $key;
                        empty($val['name']) && $val['name'] = $vConfig[$key]['name'];
                        $val['system'] = true;
                    }
                    if (isset($val['batch_only'])) {
                        if ($val['batch_only'] !== false) {
                            $bo = $val['batch_only'];
                            is_string($bo) && $bo = trim($bo);
                            if ($bo === true || empty($bo) || !is_string($bo)) $bo = "编辑";
                            unset($val['batch']);
                            unset($val['batch_only']);
                            $handles[$bo][$key] = $val;
                        } else {
                            $newHandle[$key] = $val;
                        }
                    } elseif (isset($val['batch'])) {
                        if ($val['batch'] !== false) {
                            $bt = $val['batch'];
                            is_string($bt) && $bo = trim($bt);
                            if ($bt === true || empty($bt) || !is_string($bt)) $bt = "编辑";
                            unset($val['batch']);
                            $handles[$bt][$key] = $val;
                        }
                        $newHandle[$key] = $val;
                    } else {
                        $newHandle[$key] = $val;
                    }
                }
            }
        } elseif (is_bool($handle) && $handle) {
            $newHandle = true;
        }

        if (!empty($handles)) {
            $hds = [];
            foreach ($handles as $key => $val) {
                $hds[] = [
                    'key' => $key,
                    'field' => $val
                ];
            }
            $batch['handle'] = '编辑';
            $vimConfig['handles'] = $hds;
        }

        $is['deletes'] && $batch['delete'] = '删除';
        $is['clear'] && $batch['clear'] = '清空数据';

        $is['import'] && $batch['import'] = '导入';
        if (empty($newHandle)) {
            unset($vimConfig['handle']);
        } else {
            $vimConfig['handle'] = $newHandle;
            if (!isset($is['edit']) || $is['edit'] !== false) {
                $oper['handle'] = "编辑";
            }
        }
        if (empty($batch)) {
            unset($config['batch']);
        } else {
            $this->setHandles($batch);
            if (!empty($batch) && is_array($batch)) {
                if (empty($vimConfig['batch'])) {
                    $vimConfig['batch'] = $batch;
                } else {
                    if (!is_array($vimConfig['batch'])) {
                        $vimConfig['batch'] = [];
                    }
                    foreach ($batch as $key => $val) {
                        $vimConfig['batch'][$key] = $val;
                    }
                }
            }
        }

        if ($is['delete']) {
            $oper['delete'] = "删除";
        }
        $this->setHandles($oper);
        foreach ($oper as $key => $val) {
            if (!is_string($val)) continue;
            $kNameLen = (strlen($val) + mb_strlen($val, 'UTF8')) / 4;
            $operWidth += 12 * $kNameLen + 22;
        }

        if ($operWidth > 0) {
            $oWidth = $vimConfig['operwidth']; //单个操作宽度
            empty($oWidth) && $oWidth = 0;
            !is_numeric($oWidth) && $oWidth = 0;
            $oWidth < 0 && $oWidth = 0;

            $vimConfig['operwidth'] = $operWidth + $oWidth;
        } else {
            unset($vimConfig['operwidth']);
        }
        $vimConfig['oper'] = $oper;

        if ($is['deletes'] || $is['exportChecked'] || !empty($handles)) {
            $vimConfig['is']['checkbox'] = true;
        } else {
            unset($vimConfig['is']['checkbox']);
        }
        $this->vimConfig = $vimConfig;
        return $config;
    }

    /**
     * ini.php配置
     * @param $ini
     */
    public function setIni(&$ini)
    {
        $sql = &$ini['#sql'];

        $vch = $this->vimConfigHandle;
        $vcf = $this->vimConfigField;
        $afd = $this->allField;
        foreach ($vcf as $key => $val) {
            $keyName = "";
            if (is_string($key)) {
                $keyName = $key;
            } elseif (is_string($val)) {
                $keyName = $val;
            }
            $vchVal = $vch[$key];
            $tp = "";
            if (isset($val['type'])) {
                $tp = $val['type'];
            } elseif (isset($vchVal) && isset($vchVal['type'])) {
                $tp = $vchVal['type'];
            } elseif (!empty($keyName)) {
                if (in_array($keyName, ['create_time', 'update_time', 'time'])) $tp = $keyName;
            }
            if (strpos($afd[$keyName]['type'], 'int') === false) {
                $isInt = false;
            } else {
                $isInt = true;
            }
            if (empty($sql[$keyName]) && in_array($tp, ['create_time', 'update_time'])) {
                // 当数据不为空则转换
                if ($isInt) {
                    $sql[$keyName] = [
                        ['date', 'Y-m-d H:i:s', true]
                    ];
                }
            } elseif ($tp == 'time') {
                if ($isInt) {
                    // 当数据不为空则转换
                    $sql[$keyName] = [
                        ['date', 'Y-m-d H:i:s', true]
                    ];
                }
            }

            if (in_array($tp, ['tree', 'dir'])) {
                continue;
            }

            $vList = $val['list'];
            if (empty($vList)) {
                $vList = $vchVal['list'];
            }
            if (isset($vList) && is_array($vList)) {
                $sql[$keyName] = [
                    ['str_replace_list', $vList]
                ];
            }
        }

        if (empty($sql)) {
            unset($ini['#sql']);
        }
    }

    /**
     * 获取主键信息
     * @return array
     */
    public function getPks()
    {
        $allField = $this->allField;
        $pks = [];
        if (!empty($allField)) {
            foreach ($allField as $key => $val) {
                $val['key'] == 'PRI' && $pks[] = $key;
            }
        }
        if (empty($pks)) {
            $vConfig = $this->vConfig;
            if (!empty($vConfig)) {
                foreach ($vConfig as $key => $val) {
                    if (isset($val['title']) && $val['title']) {
                        $pks[] = $key;
                    }
                }
            }
        }
        return $pks;
    }

    /**
     * 设置文件配置
     * @param TplInit $tplInit
     * @return array
     */
    public function start(TplInit $tplInit)
    {
        if (empty($tplInit)) {
            return [];
        }

        $this->tplInit = $tplInit;
        $this->sqlInit = SqlInit::__init();
        $this->vim = $this->getVim();
        $this->methods = TplInit::$methods;

        $this->baseTplPath = "";
        if (!is_null(Register::$tplPath)) {
            $this->baseTplPath = trim(trim(Register::$tplPath, "/")) . "/";
        }
        $this->fileFormats = [
            'image' => ['gif', 'jpg', 'jpeg', 'png', 'bmp', 'ico'],
            'file' => ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'htm', 'html', 'txt', 'zip', 'rar', 'gz', 'bz2', 'pdf']
        ];

        $tplInit->register(TplInit::API_INI, function (&$ini) {
            $this->setIni($ini);
        });

        $tplInit->register(TplInit::API_CONFIG, function () use ($tplInit) {

            $this->type = $tplInit->tplType;
            $this->tpl = $tplInit->tplInit;

            $config = $this->getConfig();
            $tplInit->addConfig($config);
            $tplInit->setConfig();
        });

        $tplInit->register(TplInit::API_DATA, function ($data) use ($tplInit) {
            $this->data = $data;
            $tplType = $tplInit->tplType;
            $methodName = "::{$tplType}";
            if ($this->tplInit->hasMethod($methodName) && isset($this->methods[$methodName])) {
                $data = $this->tplInit->getMethod($methodName)($this, $data);
            }
            return $data;
        });

        $tplInit->register(TplInit::API_RUN, function ($type, $config) use ($tplInit) {
            return self::$plu->call('backstage.http:run', $type, $config, $this);
        });

        $tplInit->register(TplInit::API_FIELD, function ($config, $conn = null, $table = null) {
            self::$plu->call('backstage.sql.field:reset', $this, $config, $conn, $table);
        });
    }
};
