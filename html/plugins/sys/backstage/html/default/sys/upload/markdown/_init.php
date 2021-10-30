<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

return function () {
    $mdPath = storage_path("app/public/");
    header("Content-Type:text/html; charset=utf-8");
    header("Access-Control-Allow-Origin: *");

    $uploadPath = import('UploadPath')->get();
    if (!empty($uploadPath)) {
        $uploadPath .= "/";
    }

    $savePath = "/markdown/{$uploadPath}" . date("Ym/d");

    function str_rand($length = 5, $char = '0123456789')
    {
        $string = '';
        for ($i = $length; $i > 0; $i--) {
            $string .= $char[mt_rand(0, strlen($char) - 1)];
        }
        return $string;
    }

    $fileName = time() . "_" . str_rand();

    function exit_json($status, $msg = "", $data = "")
    {
        echo json_encode([
            'success' => $status,
            'message' => $msg,
            'url' => $data
        ]);
        __exit();
    }

    try {
        $upload = $this->upload([[800, 800]], false, $savePath, $fileName);
        $files = $upload->files;
        if (empty($files) || empty($files['editormd-image-file']) || empty($files['editormd-image-file']['800_800'])) {
            exit_json(0, '图片文件上传有误！');
        }
        $oldFile = $files['editormd-image-file']['800_800'];
        $newFile = str_replace("_800_800", "", $oldFile);
        $oldSrc = $mdPath . $oldFile;
        $newSrc = $mdPath . $newFile;
        rename($oldSrc, $newSrc);
        exit_json(1, '上传成功', "/storage/" . $newFile);
    } catch (Exception $e) {
        exit_json(0, $e->getMessage());
    }
    exit_json(0, '404 ERR!');
};