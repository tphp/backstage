<?php

/**
 * This file is part of the tphp/tphp library
 *
 * @link        http://github.com/tphp/tphp
 * @copyright   Copyright (c) 2021 TPHP (http://www.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

class Ini
{
    public $xFile;
    public $chgArr;
    public $path;
    public $groupName;

    function __construct($path = "", $groupName = "")
    {
        $this->xFile = import("XFile");
        $this->setPath($path);
        $this->setGroupName($groupName);
        $this->chgArr = ["\n", "\t", "\e", "\f", "\r", "\v"];
    }

    public function xFile()
    {
        return $this->xFile;
    }

    /**
     * 设置路径
     * @param string $path
     * @return $this
     */
    public function setPath($path = "")
    {
        $path = trim($path);
        $this->path = $path;
        return $this;
    }

    /**
     * 设置空文件
     */
    private function setFileEmpty()
    {
        if (!empty($this->path) && !is_file($this->path)) {
            $this->xFile->write($this->path, "");
        }
    }

    /**
     * 设置下标
     * @param string $groupName
     * @return $this
     */
    public function setGroupName($groupName = "")
    {
        $groupName = strtolower(trim($groupName));
        empty($groupName) && $groupName = 'default';
        $this->groupName = $groupName;
        return $this;
    }

    /**
     * 获取下标
     */
    public function getGroupName()
    {
        return $this->groupName;
    }

    /**
     * 写入到文件当前分组
     * @param $data
     * @param bool $isAdd
     * @return $this
     */
    public function write($data, $isAdd = true)
    {
        if (!is_array($data)) return $this;
        $newData = [];
        foreach ($data as $key => $val) {
            if (is_string($val) || is_numeric($val)) {
                $newData[$key] = $val;
            } elseif (is_array($val) || is_object($val)) {
                $newData[$key] = json_encode($val, JSON_UNESCAPED_UNICODE);
            } elseif (is_bool($val)) {
                if ($val) {
                    $newData[$key] = 'true';
                } else {
                    $newData[$key] = 'false';
                }
            }
        }
        if (empty($newData)) return $this;
        $this->writeAll([
            $this->groupName => $newData
        ], $isAdd);
        return $this;
    }

    /**
     * 写入到文件
     * @param $data
     * @param bool $isAdd 是否仅添加
     * @return string
     */
    public function writeAll($data, $isAdd = true)
    {
        if (!is_array($data)) return $this;
        $string = '';
        $iniData = [];

        if ($isAdd) {
            $iniData = $this->readAll();
        }

        foreach ($data as $key => $val) {
            $key = strtolower(trim($key));
            if (is_array($val) && is_string($key)) {
                foreach ($val as $k => $v) {
                    $k = strtolower(trim($k));
                    $iniData[$key][$k] = $v;
                }
            }
        }
        ksort($iniData);
        $chgArr = $this->chgArr;
        $chgKey = $chgArr;
        $chgKey[] = " ";
        if (isset($iniData['default'])) {
            $def = ['default' => $iniData['default']];
            unset($iniData['default']);
            $iniData = array_merge($def, $iniData);
        }
        foreach (array_keys($iniData) as $key) {
            if ($this->inArray($key, $chgKey)) continue;
            $string .= '[' . $key . "]\n";
            $string .= $this->writeGetString($iniData[$key], '') . "\n";
        }
        $this->setFileEmpty();
        file_put_contents($this->path, $string);
        return $this;
    }

    private function inArray($key, $arr)
    {
        $bool = false;
        foreach ($arr as $chr) {
            if (strpos($key, $chr) !== false) {
                $bool = true;
                break;
            }
        }
        return $bool;
    }

    /**
     * @param $ini
     * @param $prefix
     * @return string
     */
    private function writeGetString(& $ini, $prefix)
    {
        if (!is_array($ini)) return "";
        $string = '';
        ksort($ini);
        $chgArr = $this->chgArr;
        $chgKey = $chgArr;
        $chgKey[] = " ";
        foreach ($ini as $key => $val) {
            $key = strtolower(trim($key));
            if ($this->inArray($key, $chgKey)) continue;
            if (is_array($val)) {
                $string .= $this->writeGetString($ini[$key], $prefix . $key . '.');
            } else {
                $val = str_replace("\r", "", $val);
                $val = str_replace("\n", "[\\n]", $val);
                $tv = $this->setValue($val);
                $string .= $prefix . $key . ' = ' . $tv . "\n";
            }
        }
        return $string;
    }

    /**
     * @param $val
     * @return string
     */
    private function setValue($val)
    {
        if ($val === true) {
            return 'true';
        } else if ($val === false) {
            return 'false';
        }
        return $val;
    }

    public function read($keyName = "")
    {
        return $this->readAll($this->groupName, $keyName);
    }

    /**
     * 读取所有信息
     * @param string $groupName
     * @param string $keyName
     * @return array|mixed|string
     */
    public function readAll($groupName = "", $keyName = "")
    {
        $ini = array();
        if (empty($this->path) || !is_file($this->path)) {
            $lines = [];
        } else {
            $lines = file($this->path);
        }
        $section = 'default';
        $multi = '';
        foreach ($lines as $line) {
            if (substr($line, 0, 1) !== ';') {
                $key = "";
                if (preg_match('/^\[(.*)\]/', $line, $m)) {
                    $section = $m[1];
                } else if ($multi === '' && preg_match('/^([a-z0-9_.\[\]-]+)\s*=\s*(.*)$/i', $line, $m)) {
                    $key = $m[1];
                    $val = $m[2];
                    $val = str_replace("[\\n]", "\n", $val);
                    if (substr($val, -1) !== "\\") {
                        $val = trim($val);
                        $this->manageKeys($ini[$section], $key, $val);
                        $multi = '';
                    } else {
                        $multi = substr($val, 0, -1) . "\n";
                    }
                } else if ($multi !== '') {
                    if (substr($line, -1) === "\\") {
                        $multi .= substr($line, 0, -1) . "\n";
                    } else {
                        $this->manageKeys($ini[$section], $key, $multi . $line);
                        $multi = '';
                    }
                }
            }
        }

        $buf = get_defined_constants(true);
        $constList = array();
        foreach ($buf['user'] as $key => $val) {
            $constList['{' . $key . '}'] = $val;
        }

        array_walk_recursive($ini, array('Ini', 'replaceConstList'), $constList);

        $groupName = strtolower(trim($groupName));
        $keyName = strtolower(trim($keyName));
        if (!empty($groupName)) {
            if (!isset($ini[$groupName])) {
                return [];
            }
            $iniG = $ini[$groupName];
            if (!empty($keyName)) {
                if (!is_array($iniG) || !isset($iniG[$keyName])) {
                    return "";
                }
                return $iniG[$keyName];
            }
            return $iniG;
        }
        return $ini;
    }

    /**
     * @param $val
     * @return bool|int
     */
    private function getValue($val)
    {
        if (preg_match('/^-?[0-9]$/i', $val)) {
            return intval($val);
        } else if (strtolower($val) === 'true') {
            return true;
        } else if (strtolower($val) === 'false') {
            return false;
        } else if (preg_match('/^"(.*)"$/i', $val, $m)) {
            return $m[1];
        } else if (preg_match('/^\'(.*)\'$/i', $val, $m)) {
            return $m[1];
        }
        return $val;
    }

    /**
     * @param $val
     * @return int
     */
    private function getKey($val)
    {
        if (preg_match('/^[0-9]$/i', $val)) {
            return intval($val);
        }
        return $val;
    }

    /**
     * @param $ini
     * @param $key
     * @param $val
     */
    private function manageKeys(&$ini, $key, $val)
    {
        if (preg_match('/^([a-z0-9_-]+)\.(.*)$/i', $key, $m)) {
            $this->manageKeys($ini[$m[1]], $m[2], $val);
        } else if (preg_match('/^([a-z0-9_-]+)\[(.*)\]$/i', $key, $m)) {
            if ($m[2] !== '') {
                $ini[$m[1]][$this->getKey($m[2])] = $this->getValue($val);
            } else {
                $ini[$m[1]][] = $this->getValue($val);
            }
        } else {
            $ini[$this->getKey($key)] = $this->getValue($val);
        }
    }

    /**
     * @param $item
     * @param $key
     * @param $constList
     */
    private function replaceConstList(& $item, $key, $constList)
    {
        if (is_string($item)) {
            $item = strtr($item, $constList);
        }
    }
}