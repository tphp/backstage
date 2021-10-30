<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

return new class
{
    public $model;
    public $table;
    public $conn;
    public $config;
    public $obj;
    public $admin = [
        'admin' => true
    ];

    /**
     * 获取数据库
     * @return mixed
     */
    public function db()
    {
        if (!empty($this->model)) {
            return $this->model->db();
        }

        $db = $this->obj->db($this->table, $this->conn);
        return $db;
    }

    /**
     * 系统配置
     * @param array $config
     */
    public function setConfig($config = [], $obj = null)
    {
        $this->config = $config;
        $this->obj = $obj;
        if ($config['default'] === false) {
            $this->table = $config['table'];
            $this->conn = $config['conn'];
        }

        $admin = $config['admin'];
        if (is_string($admin)) {
            $admin = strtolower(trim($admin));
            if (!empty($admin)) {
                $this->admin[$admin] = true;
            }
        } elseif (is_array($admin)) {
            foreach ($admin as $a) {
                if (!is_string($a)) {
                    continue;
                }

                $a = strtolower(trim($a));
                if (!empty($a)) {
                    $this->admin[$a] = true;
                }
            }
        }
    }

    /**
     * 获取管理员
     * @return array
     */
    public function getAdmin()
    {
        return $this->admin;
    }

    /**
     * 获取字段名称
     * @param string $fieldName
     * @param bool $isAs
     * @return string
     */
    private function getFieldName($fieldName = '', $isAs = true)
    {
        if (!isset($this->config[$fieldName])) {
            return $fieldName;
        }

        if ($isAs) {
            return $this->config[$fieldName] . " as " . $fieldName;
        }

        return $this->config[$fieldName];
    }

    /**
     * 获取ID字段
     * @param bool $isAs
     * @return string
     */
    public function getId($isAs = true)
    {
        return $this->getFieldName('id', $isAs);
    }
    
    /**
     * 获取username字段
     * @param bool $isAs
     * @return string
     */
    public function getUsername($isAs = true)
    {
        return $this->getFieldName('username', $isAs);
    }

    /**
     * 获取username字段
     * @param bool $isAs
     * @return string
     */
    public function getPassword($isAs = true)
    {
        return $this->getFieldName('password', $isAs);
    }
    
    /**
     * 获取image字段
     * @param bool $isAs
     * @return string
     */
    public function getImage($isAs = true)
    {
        return $this->getFieldName('image', $isAs);
    }

    /**
     * 获取nickname字段
     * @param bool $isAs
     * @return string
     */
    public function getNickname($isAs = true)
    {
        return $this->getFieldName('nickname', $isAs);
    }

    /**
     * 获取nickname字段
     * @param bool $isAs
     * @return string
     */
    public function getSalt($isAs = true)
    {
        return $this->getFieldName('salt', $isAs);
    }
    
    /**
     * 是否默认
     * @return bool
     */
    public function isDefault()
    {
        return $this->config['default'] !== false;
    }
    
    /**
     * 用户表信息
     * @return mixed
     */
    public static function index()
    {
        return new static();
    }
};
