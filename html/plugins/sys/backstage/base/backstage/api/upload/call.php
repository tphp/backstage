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
     * 更新文件
     * @param array $resizes
     * @param bool $isSaveSrc
     * @param null $baseUrl
     * @return mixed
     */
    private function upload($resizes = [], $isSaveSrc = false, $baseUrl = null, $fileName = "")
    {
        $upload = import('Upload', $baseUrl, $fileName);
        if (!empty($resizes) && is_array($resizes)) {
            //缩略图上传
            foreach ($resizes as $key => $val) {
                if (!empty($val) && is_array($val)) {
                    list($w, $h) = $val;
                    if ($w > 0 && $h > 0) {
                        $upload->addResize($w, $h, $key);
                    }
                }
            }
        }
        return $upload->run($isSaveSrc);
    }

    /**
     * 上传文件或图片
     * @param $apiObj
     */
    public function html($apiObj)
    {
        $uploadPath = import('UploadPath')->get();
        $field = $_GET['field'];
        $handle = $apiObj->vConfig;
        $hf = $handle[$field];
        empty($hf) && $hf = [];
        $vHandle = $apiObj->vimConfigHandle[$field];
        if (!empty($vHandle) && is_array($vHandle)) {
            foreach ($vHandle as $key => $val) {
                !isset($hf[$key]) && $hf[$key] = $val;
            }
        }
        $apiObj->tplInit->getDataForArgs($hf);
        $path = $hf['path'];
        if (isset($path) && is_string($path)) {
            $path = str_replace("\\", "/", $path);
            $path = trim($path, " /");
            if (!empty($uploadPath)) {
                $path = "{$uploadPath}/{$path}";
            }
        } else {
            $path = $uploadPath;
        }
        $thumbs = $hf['thumbs'];
        empty($thumbs) && $thumbs = [];

        $vtp = $hf['type'];
        $fFmt = $apiObj->fileFormats;
        if ($vtp == 'image' || $vtp == 'file') {
            $format = [];
            $vfm = $hf['format'];
            if ($vtp == 'image') {
                $format = $fFmt['image'];
            } elseif (empty($vfm)) {
                $format = $fFmt['file'];
            } else {
                if (is_string($vfm)) {
                    $vfm = explode(",", $vfm);
                }
                if (is_array($vfm)) {
                    foreach ($vfm as $v) {
                        if (!empty($v) && is_string($v)) {
                            $v = strtolower(trim($v));
                            !empty($v) && $format[] = $v;
                        }
                    }
                }
            }

            if (!empty($format)) {
                foreach ($_FILES as $key => $val) {
                    $name = $val['name'];
                    $pos = strrpos($name, ".");
                    if ($pos > 0) {
                        $fileExt = strtolower(trim(substr($name, $pos + 1)));
                        if (!in_array($fileExt, $format)) {
                            $apiObj->__exitError("格式 {$fileExt} 不支持");
                        }
                    } else {
                        $apiObj->__exitError("上传后缀名不能为空");
                    }
                }
            }
        } else {
            $apiObj->__exitError("{$vtp} 不被允许");
        }

        if (empty($path)) {
            $path = $apiObj->tplInit->tplInit;
        }

        $fileInfo = $this->upload($thumbs, false, $path, $hf['filename'])->urls("_file_" . $field);
        if (empty($fileInfo)) $apiObj->__exitError("404 ERR");
        EXITJSON(1, "上传成功", $fileInfo);
    }
};