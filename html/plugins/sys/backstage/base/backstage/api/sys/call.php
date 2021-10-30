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

    /**
     * 系统配置
     * 列表字段筛选查询
     * @param $apiObj
     */
    public function html($apiObj)
    {
        $type = trim($_GET['type']);
        if (empty($type)) {
            $apiObj->__exitError("Err 404!");
        }
        $type = strtolower($type);
        $isPost = $apiObj->tplInit->isPost();
        if ($type == 'menu_field') {
            if (!$isPost) {
                $apiObj->__exitError("参数传递错误");
            }
            $userInfo = $apiObj->getUserInfo();
            $dc = \Tphp\Config::$domain;
            if (empty($userInfo) && $dc['backstage']) {
                $apiObj->__exitError("用户未登录");
            }

            $id = $_POST['id'];
            if (is_null($id) || trim($id) === '' || !is_numeric($id)) {
                $apiObj->__exitError("菜单ID不能为空");
            }

            $pType = $_POST['type'];
            $field = $_POST['field'];
            if (!empty($pType)) {
                $id = hexdec(substr(md5("{$id}_{$pType}"), 12, 6));
            }
            $userId = $userInfo['id'];
            $conn = trim($dc['conn']);
            empty($conn) && $conn = $apiObj->defaultConn;
            $tableMenuField = import("SystemInit", $apiObj->tplInit)->tableMenuField();
            $menuCount = $tableMenuField->where('menu_id', '=', $id)->where('user_id', '=', $userId)->count();
            if ($menuCount > 0) {
                $tableMenuField->where('menu_id', '=', $id)->where('user_id', '=', $userId)->update([
                    'field' => $field
                ]);
                $msg = '添加成功';
            } else {
                $tableMenuField->insert([
                    'menu_id' => $id,
                    'user_id' => $userId,
                    'field' => $field
                ]);
                $msg = '保持成功';
            }
            $apiObj->tplInit->unCache("info_{$id}_{$userId}");
            EXITJSON(1, $msg);
        }
        $apiObj->__exitError("命令错误");
    }
};