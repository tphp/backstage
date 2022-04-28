<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

use Tphp\Register;
use Tphp\Config as TphpConfig;

class SystemInit
{
    private $obj;
    private $userClass;

    public function __construct($obj = null)
    {
        $this->obj = $obj;
        $btpt = Register::$topPath;
        $btpt = trim(trim($btpt), "/");
        $btpt = str_replace("/", "_", $btpt);
        $this->userInfoId = $btpt . "_sys_user_login_userinfo";
    }

    public function getConnection($conn = '')
    {
        if (!empty($conn)) {
            return $conn;
        }
        $conn = '';
        $obj = $this->obj;
        if (!empty($obj)) {
            $config = $obj->config;
            if (!empty($config) && !empty($config['conn'])) {
                $conn = $config['conn'];
            }
        }

        if (empty($conn)) {
            $conn = TphpConfig::$domain['conn'];
        }

        if (is_function($conn)) {
            $conn = $conn();
        }
        return $conn;
    }

    /**
     * 获取随机字符串
     *
     * @param int $length
     * @param string $char
     * @return bool|string
     */
    private function strRand($length = 5, $char = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
    {
        $string = '';
        for ($i = $length; $i > 0; $i--) {
            $string .= $char[mt_rand(0, strlen($char) - 1)];
        }
        return $string;
    }

    /**
     * 获取用户信息
     * @return mixed
     */
    public function getUserInfo()
    {
        return Session::get($this->userInfoId);
    }

    /**
     * 用户登录
     * @param $username
     * @param bool $isPassword
     * @param string $password
     * @return array
     */
    public function userLogin($username, $isPassword = false, $password = '')
    {
        $username = strtolower(trim($username));
        if(empty($username)) {
            return [false, "用户名不能为空"];
        }elseif(empty($password) && $isPassword) {
            return [false, "密码不能为空"];
        }

        $tableUser = $this->tableUser();

        try{
            $userInfo = $tableUser->db()->where($tableUser->getUsername(false), "=", $username)->first();
        } catch (Exception $e){
            $this->obj->flushCache();
            return [false, "Error: " . $e->getMessage()];
        }

        if(empty($userInfo)) {
            return [false, "用户或密码不正确"];
        }

        if ($tableUser->isDefault()) {
            if ($userInfo->status . "" != '1') {
                return [false, "用户已禁用"];
            }
        }

        $tSalt = $tableUser->getSalt(false);
        $tUsername = $tableUser->getUsername(false);
        if ($isPassword) {
            $tPassword = $tableUser->getPassword(false);
            $salt = $userInfo->$tSalt;
            empty($salt) && $salt = '';
            $md5Pwd = md5($password . $salt);
            if ($userInfo->$tPassword != $md5Pwd) {
                return [false, "用户或密码不正确"];
            }
        }

        $menuIds = [];
        $roleIds = [];
        $tAdmin = $tableUser->getAdmin();
        if(!$tAdmin[$username]) {
            $tId = $tableUser->getId(false);
            $ruList = $this->tableRoleUser()->db()->select('role_id')->where('user_id', '=', $userInfo->$tId)->get();
            foreach ($ruList as $ru) {
                $roleIds[] = $ru->role_id;
            }
            if(!empty($roleIds)) {
                $rList = $this->tableRole()->db()->select('json')->whereIn("id", $roleIds)->get();
                $menuKvs = [];
                foreach ($rList as $r) {
                    $rJson = trim($r->json);
                    if ($rJson == '') {
                        continue;
                    }

                    $rSplit = explode(",", $rJson);

                    foreach ($rSplit as $rs) {
                        $rs = trim($rs);
                        if ($rs !== '') {
                            $menuKvs[$rs] = true;
                        }
                    }
                }

                $menuIds = array_keys($menuKvs);
            }
        }

        $menuIda = [];
        if(!empty($menuIds)) {
            $menuIdsGt = [];
            $menuIdsLt = [];
            foreach ($menuIds as $val){
                if($val >= 0){
                    //数据库菜单ID
                    $menuIdsGt[] = $val;
                }else{
                    //默认设置菜单ID
                    $menuIdsLt[] = $val;
                }
            }

            if (TphpConfig::$domain['folder']) {
                $menuDirList = TphpConfig::$domainPath->plu->call('dir.menus');
                if (empty($menuIdsGt)) {
                    $menuList = $menuDirList;
                } else {
                    $menuList = [];
                    foreach ($menuDirList as $mdl) {
                        if (!in_array($mdl['id'], $menuIdsGt)) {
                            $menuList[] = $mdl;
                        }
                    }
                }
            } else {
                $db = $this->tableMenu()->db();
                if (!empty($menuIdsGt)) {
                    $db->whereNotIn("id", $menuIdsGt);
                }
                $menuList = $db->Where("status", "=", "1")->select("id", "url", "name")->get();
            }

            $menuList = json_decode(json_encode($menuList, true), true);
            
            if(!empty($menuIdsLt)) {
                $defList = $this->obj->pluMain()->call('dir.menus:extMenus');
                if (!empty($defList) && is_array($defList)) {
                    foreach ($defList as $key => $val) {
                        if (!in_array($val['id'], $menuIdsLt)) {
                            $menuList[] = $val;
                        }
                    }
                }
            }

            foreach ($menuList as $key=>$val) {
                $url = $val['url'];
                $pos = strpos($url, "?");
                if($pos > 0){
                    $url = substr($url, 0, $pos);
                }
                $pos = strpos($url, "#");
                if($pos > 0){
                    $url = substr($url, 0, $pos);
                }
                $url = str_replace(".html", "", $url);
                $url = trim(trim($url), "/");
                $md5 = substr(md5($url), 8, 8);
                $menuIda[$md5] = [
                    "id" => $val['id'],
                    "name" => $val['name'],
                    "url" => $val['url'],
                ];
            }
        }

        $tId = $tableUser->getId(false);
        $tNickname = $tableUser->getNickname(false);
        $tImage = $tableUser->getImage(false);
        $retInfo = [
            'id' => $userInfo->$tId,
            'username' => $userInfo->$tUsername,
            'nickname' => $userInfo->$tNickname,
            'role_id' => $roleIds,
            'menuIds' => $menuIds,
            'menuIda' => $menuIda,
            'image' => $userInfo->$tImage
        ];

        Session::put($this->userInfoId, $retInfo, 24 * 60 * 60);

        return [true, $retInfo];
    }

    /**
     * 用户表
     *
     * @param string $conn
     * @return mixed
     */
    public function tableUser($password = '')
    {
        $user = $this->getConnection(TphpConfig::$domain['user']);
        $userClass = $this->userClass;
        if (!empty($userClass)) {
            return $userClass;
        }

        $userClass = TphpConfig::$domainPath->plu->call('user');
        $this->userClass = $userClass;

        $userClass->setConfig($user, $this->obj);

        if ($user['default'] !== false) {
            $model = plu('', $this->obj)->model([
                'table' => $user['table'],
                'guarded' => [],
                'dateFormat' => 'U',
                'field' => function (Illuminate\Database\Schema\Blueprint $table) {
                    $table->increments('id')->comment('用户ID');
                    $table->string('username', 20)->comment('用户名');
                    $table->string('nickname', 50)->comment('昵称')->nullable();
                    $table->tinyInteger('sex')->comment('性别')->nullable();
                    $table->string('password', 64)->comment('密码')->nullable();
                    $table->string('salt', 10)->comment('密码盐值')->nullable();
                    $table->tinyInteger('status')->comment('状态')->nullable();
                    $table->string('image', 200)->comment('头像')->nullable();
                    $table->string('image_big', 200)->comment('高清头像')->nullable();
                    $table->string('remark', 1024)->comment('简介')->nullable();
                    $table->integer('login_time')->comment('登录时间')->nullable();
                },
                'init' => function () use ($password) {
                    $username = 'admin';
                    empty($password) && $password = $username;
                    $salt = $this->strRand();
                    $passwordMd5 = md5($password . $salt);
                    return [
                        [
                            'username' => $username,
                            'password' => $passwordMd5,
                            'salt' => $salt,
                            'status' => '1'
                        ],
                    ];
                },
                'before' => function ($hasTable) use ($password) {
                    if (!method_exists($this->obj, 'getCacheId')) {
                        return false;
                    }

                    $btp = Register::$topPath;
                    $userInfoId = $this->obj->getCacheId("/{$btp}/sys/user/login/userinfo");
                    $isPost = (count($_POST) > 0);
                    if ($hasTable) {
                        if ($this->obj->tpl === "sys/user/login/init") {
                            if ($isPost) {
                                EXITJSON(0, '错误： 密码已初始化！');
                            }
                            if (empty(Session::get($userInfoId))) {
                                redirect("/sys/user/login")->send();
                            } else {
                                redirect("/")->send();
                            }
                        }
                    } elseif ($this->obj->tpl === "sys/user/login/init") {
                        return false;
                    } else {
                        if (empty($password)) {
                            Session::forget($userInfoId);
                            if ($isPost) {
                                EXITJSON(1, '用户信息未初始化', '/sys/user/login/init');
                            } else {
                                redirect("/sys/user/login/init")->send();
                            }
                            return false;
                        }
                    }

                    return true;
                }
            ], $user['conn']);

            $userClass->model = $model;
            $userClass->table = $model->table;
            $userClass->conn = $model->conn;
        }

        return $userClass;
    }

    /**
     * 用户角色字段表
     *
     * `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
     * `name` VARCHAR(20) NULL DEFAULT NULL COMMENT '角色名称',
     * `sort` TINYINT(3) UNSIGNED NULL DEFAULT '255' COMMENT '排序',
     * `status` TINYINT(3) NULL DEFAULT '1' COMMENT '状态',
     * `json` VARCHAR(1024) NULL DEFAULT NULL COMMENT '角色目录',
     * `create_time` INT(11) NULL DEFAULT NULL COMMENT '创建时间',
     * `update_time` INT(11) NULL DEFAULT NULL COMMENT '更新时间',
     * @param string $conn
     * @return mixed
     */
    public function tableRole()
    {
        $conn = $this->getConnection();
        return plu('', $this->obj)->model([
            'table' => 'sys_role',
            'guarded' => [],
            'field' => function (Illuminate\Database\Schema\Blueprint $table) {
                $table->increments('id')->comment('角色ID');
                $table->string('name', 20)->comment('角色名称')->nullable();
                $table->unsignedTinyInteger('sort')->comment('排序')->nullable()->default(255);
                $table->tinyInteger('status')->comment('状态')->nullable()->default(1);
                $table->string('json', 1024)->comment('角色目录')->nullable();
            }
        ], $conn);
    }

    /**
     * 用户角色字段绑定表
     * @return mixed
     */
    public function tableRoleUser()
    {
        $conn = $this->getConnection();
        return plu('', $this->obj)->model([
            'table' => 'sys_role_user',
            'guarded' => [],
            'timestamps' => false,
            'field' => function (Illuminate\Database\Schema\Blueprint $table) {
                $table->integer('role_id')->comment('角色ID');
                // 兼容外部用户ID不为数字类型的情况
                $table->string('user_id', 128)->comment('用户ID');
            }
        ], $conn);
    }

    /**
     * 菜单表
     *
     * @param string $conn
     * @return mixed
     */
    public function tableMenu()
    {
        $conn = $this->getConnection();
        $dc = TphpConfig::$domain;
        $config = [
            'table' => 'sys_menu',
            'guarded' => [],
            'dateFormat' => 'U',
            'field' => function (Illuminate\Database\Schema\Blueprint $table) {
                $table->increments('id')->comment('菜单ID');
                $table->integer('parent_id')->comment('父ID')->nullable();
                $table->string('name', 20)->comment('菜单名称')->nullable();
                $table->string('description', 50)->comment('描述')->nullable();
                $table->string('icon', 50)->comment('文字图标样式')->nullable();
                $table->string('url', 100)->comment('链接')->nullable();
                $table->string('params', 100)->comment('URL参数')->nullable();
                $table->unsignedTinyInteger('sort')->comment('排序')->nullable();
                $table->tinyInteger('status')->comment('是否启用：0禁用 1启用')->nullable()->default('1');
            }
        ];
        if (is_array($dc['menu']) && is_array($dc['menu'])) {
            $config['init'] = $dc['menu'];
        }
        return plu('', $this->obj)->model($config, $conn);
    }

    /**
     * 菜单用户显示隐藏字段表
     *
     * @param string $conn
     * @return mixed
     */
    public function tableMenuField()
    {
        $conn = $this->getConnection();
        return plu('', $this->obj)->model([
            'table' => 'sys_menu_field',
            'guarded' => [],
            'timestamps' => false,
            'field' => function (Illuminate\Database\Schema\Blueprint $table) {
                $table->integer('menu_id')->comment('菜单ID');
                // 兼容外部用户ID不为数字类型的情况
                $table->string('user_id', 128)->comment('用户ID');
                $table->string('field', 512)->comment('字段信息')->nullable();
                $table->primary(['menu_id', 'user_id']);
            }
        ], $conn);
    }
}