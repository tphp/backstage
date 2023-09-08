<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

abstract class SqlClass
{
    /**
     * 获取数据库详情
     * @param $dbInfo
     * @return mixed
     */
    abstract protected function getDatabaseDetail($dbInfo);

    /**
     * 数据库字段比较
     * @param $new
     * @param $old
     * @return mixed
     */
    abstract protected function compareDatabase($new, $old);

    /**
     * 建立数据库语句信息
     * @param $diff
     * @return mixed
     */
    abstract protected function buildQuery($diff);

    /**
     * 同步数据库
     * @return mixed
     */
    abstract protected function updateDatabase();

    /**
     * 初始化
     * SqlClass constructor.
     * @param array $dbInfo
     * @param string $conn
     */
    public function __construct($conn = '')
    {
        $this->dbInfo = config("database.connections.{$conn}");
        $this->conn = $conn;
        $this->xFile = import("XFile");
        $this->sqls = [];

        $isPlu = false;
        $pluDir = '';
        $dc = \Tphp\Config::$domain;
        if (is_array($dc) && is_array($dc['plu'])) {
            $pluDir = $dc['plu']['dir'];
            if (is_string($pluDir)) {
                $pluDir = trim($pluDir);
                if (!empty($pluDir)) {
                    $isPlu = true;
                }
            }
        }

        if ($isPlu) {
            $plu = plu($pluDir);
            $this->basePath = base_path(\Tphp\Register::getHtmlPath() . $plu->getBasePath('database/cache') . "/");
        } else {
            $this->basePath = database_path('cache/');
        }
    }

    /**
     * 保持到文件
     * @param $fileName
     */
    public function save()
    {
        // TODO: Implement save() method.

        $fileName = $this->conn;
        if (empty($fileName)) return [0, "未指定文件名！"];
        list($status, $msg, $dataNew) = $this->getDatabaseDetail($this->dbInfo);
        if (!$status) return [0, $msg];
        $filePath = $this->basePath . $fileName . ".json";
        $this->xFile->write($filePath, json_encode($dataNew, true));
        return [1, 'ok'];
    }

    /**
     * 获取信息比较
     * @param bool $isRest
     * @return array
     */
    public function getDiff($isRest = false)
    {
        // TODO: Implement getDiff() method.

        list($status, $dataOld) = $this->getJsonToFile();
        if (!$status) {
            return [0, $dataOld];
        }

        list($status, $msg, $dataNew) = $this->getDatabaseDetail($this->dbInfo);
        if ($status == 0) return [$status, $msg];
        if ($isRest) { //如果是还原则对调对比
            $diff = $this->compareDatabase($dataOld, $dataNew);
        } else {
            $diff = $this->compareDatabase($dataNew, $dataOld);
        }
        if (empty($diff)) {
            return [0, "数据相同，无需同步操作！"];
        }

        list($status, $sqls) = $this->buildQuery($diff);
        if (!$status) {
            return [0, $sqls];
        }
        $this->sqls = $sqls;
        return [1, "同步列表", $sqls];
    }

    /**
     * 开始同步
     * @param array $diff
     */
    public function run()
    {
        // TODO: Implement run() method.

        if (empty($this->sqls)) return [0, "数据相同，无需同步操作！"];
        return $this->updateDatabase();
    }

    /**
     * 设置同步数据源
     * @param array $dbInfo
     */
    public function setSrcDb($dbInfo = [])
    {
        $this->dbOld = $dbInfo;
    }

    /**
     * 获取文件
     * @param $fileName
     */
    protected function getJsonToFile()
    {
        $filePath = $this->basePath . $this->conn . ".json";
        if (!is_file($filePath)) {
            return [false, '未生成备份字段信息文件：' . $this->dbPath . $this->conn . ".json"];
        }
        $str = $this->xFile->read($filePath);
        if (empty($str)) return [true, []];
        $json = json_decode($str, true);
        empty($json) && $json = [];
        return [true, $json];
    }

    /**
     * 超时判断，设置未500毫秒
     * @param $ip
     * @param $port
     */
    protected function isLinked($ip, $database, $port, $driver)
    {
        $url = $ip;
        !empty($port) && $url .= ":{$port}";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 5000);
		curl_setopt($ch, CURLOPT_HTTP09_ALLOWED, true);
        curl_exec($ch);
        $curlErrNo = curl_errno($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlErrNo > 0 && $curlError != "Empty reply from server") {
            if (strpos($curlError, "timed") > 0) {
                $curlError = "数据库 {$database} 链接超时";
            } elseif (strpos($curlError, "port") > 0) {
                $curlError = "数据库 {$database} IP或端口配置错误";
            } elseif ($driver == 'pgsql') {
                if (strpos($curlError, "Recv failure") !== false) {
                    return [1, "链接成功"];
                }
            }
            return [0, $curlError];
        }
        return [1, "链接成功"];
    }

    /**
     * 链接测试
     */
    protected function linkedTest()
    {
        $conn = $this->conn;
        empty($conn) && $conn = 'testdb';
        $dbConfig = config("database.connections.{$conn}");
        if (empty($dbConfig)) return [0, "数据库配置错误"];

        $driver = $dbConfig['driver'];
        $ip = $dbConfig['host'];
        $database = $dbConfig['database'];
        $port = $dbConfig['port'];
        if (empty($port)) {
            if ($driver == 'mysql') {
                $port = 3306;
            } elseif ($driver == 'sqlsrv') {
                $port = 1433;
            } elseif ($driver == 'pgsql') {
                $port = 5432;
            }
        }

        if (empty($ip) || empty($database)) return [0, "链接失败"];
        list($status, $msg) = $this->isLinked($ip, $database, $port, $driver);
        if (!$status) return [0, $msg];
        $db = \DB::connection($conn);
        try {
            if ($driver == 'mysql') {
                $sqlStr = "show tables";
            } elseif ($driver == 'sqlsrv') {
                $sqlStr = "select top 1 name, object_id from sys.tables order by name asc";
            } elseif ($driver == 'pgsql') {
                $sqlStr = "select count(1) from pg_tables";
            }
            $db->select($sqlStr);
        } catch (\Exception $e) {
            $eInfo = $e->getMessage();
            if ($driver == 'mysql') {
                if (strpos($eInfo, "Unknown database") > 0) {
                    $eInfo = "数据库不存在";
                } elseif (strpos($eInfo, "Access denied for user") > 0) {
                    $eInfo = "用户或密码不正确";
                }
            } elseif ($driver == 'sqlsrv') {
                if (strpos($eInfo, "General SQL Server error") > 0) {
                    $eInfo = "数据库不存在";
                } elseif (strpos($eInfo, "Adaptive Server connection failed") > 0) {
                    $eInfo = "用户或密码不正确";
                }
            }
            return [0, $eInfo];
        }
        return [1, "链接成功"];
    }

    /**
     * 获取键值类型的数据格式
     * @param $list
     * @param null $keyName
     * @param null $valueName
     * @param null $lastName
     * @return array
     */
    protected function getListToKeyValue($list, $keyName = null, $valueName = null, $lastName = null)
    {
        $ret = [];
        if (empty($list)) {
            return $ret;
        }
        if (empty($keyName) || !is_string($keyName)) {
            return $ret;
        }

        $newList = [];
        foreach ($list as $detail) {
            $newList[] = json_decode(json_encode($detail), true);;
        }
        if (empty($valueName)) {
            foreach ($newList as $detail) {
                $kn = $detail[$keyName];
                unset($detail[$keyName]);
                $ret[$kn] = $detail;
            }
        } elseif (is_string($valueName)) {
            if (empty($lastName)) {
                foreach ($newList as $detail) {
                    $ret[$detail[$keyName]] = $detail[$valueName];
                }
            } elseif (is_string($lastName)) {
                foreach ($newList as $detail) {
                    empty($ret[$detail[$keyName]]) && $ret[$detail[$keyName]] = [];
                    $ret[$detail[$keyName]][$detail[$valueName]] = $detail[$lastName];
                }
            } elseif (is_array($lastName)) {
                foreach ($newList as $detail) {
                    empty($ret[$detail[$keyName]]) && $ret[$detail[$keyName]] = [];
                    $ret[$detail[$keyName]][$detail[$valueName]] = [];
                    $retAddr = &$ret[$detail[$keyName]][$detail[$valueName]];
                    foreach ($lastName as $ln) {
                        $retAddr[$ln] = $detail[$ln];
                    }
                }
            }
        } elseif (is_array($valueName)) {
            foreach ($newList as $detail) {
                $kn = $detail[$keyName];
                $ret[$kn] = [];
                foreach ($valueName as $vn) {
                    $ret[$kn][$vn] = $detail[$vn];
                }
            }
        }
        return $ret;
    }

    /**
     * 判断两个数组是否相等
     * @param array $arr1
     * @param array $arr2
     * @param array $keys
     * @return bool
     */
    protected function arrayIsEqual($arr1 = [], $arr2 = [], $keys = [])
    {
        $isEqual = true;
        foreach ($keys as $key) {
            if ($arr1[$key] !== $arr2[$key]) {
                $isEqual = false;
                break;
            }
        }
        return $isEqual;
    }

    /**
     * 保存日志
     * @param $name
     * @param $oks
     * @param $errs
     */
    protected function saveLogs($name, $oks, $errors)
    {
        $dateStr = date("Y/m/d_His");
        $path = storage_path('framework/cache/sql/') . "{$name}/{$dateStr}";
        if (count($oks) > 0) {
            $this->xFile->write($path . "_ok.sql", implode("\r\n", $oks));
        }
        if (count($errors) > 0) {
            $this->xFile->write($path . "_no.sql", implode("\r\n", $errors));
        }
    }
}
