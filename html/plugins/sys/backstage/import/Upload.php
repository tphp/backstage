<?php

/**
 * This file is part of the tphp/tphp library
 *
 * @link        http://github.com/tphp/tphp
 * @copyright   Copyright (c) 2021 TPHP (http://www.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

class Upload
{
    public function __construct($path = "", $fileName = "")
    {
        $this->resize = [];
        if (empty($fileName) || !is_string($fileName)) {
            if (is_numeric($fileName)) {
                $fileName .= "";
            } else {
                $fileName = "";
            }
        }
        $this->fileName = trim(trim(trim($fileName), "/\\"));
        $this->path = trim(trim($path, "\/\\"));
        $this->savePath = storage_path('app/public/');
        $this->xFile = import('XFile');
        $this->files = [];
    }

    /**
     * 增加缩列图
     * @param int $w 宽
     * @param int $h 高
     * @return $this
     */
    public function addResize($w = 0, $h = 0, $keyName = "")
    {
        if ($w > 0 && $h > 0) {
            $resize = $this->resize;
            $resize[$keyName] = [$w, $h];
            $this->resize = $resize;
        }
        return $this;
    }

    /**
     * 生成缩列图
     * @param $im
     * @param $maxwidth
     * @param $maxheight
     * @param $fileName
     */
    private function resizeImage($im, $w, $h, $fileName)
    {
        if (file_exists($fileName)) {
            try {
                unlink($fileName);
            } catch (\Exception $e) {
                // Nothing TODO
            }
        }
        $width = imagesx($im);
        $height = imagesy($im);
        if (($w && $width > $w) || ($h && $height > $h)) {
            if ($w && $width > $w) {
                $widthRatio = $w / $width;
                $resizeWidth = true;
            }
            if ($h && $height > $h) {
                $heightratio = $h / $height;
                $resizeHight = true;
            }
            if ($resizeWidth && $resizeHight) {
                if ($widthRatio < $heightratio) {
                    $ratio = $widthRatio;
                } else {
                    $ratio = $heightratio;
                }
            } elseif ($resizeWidth) {
                $ratio = $widthRatio;
            } elseif ($resizeHight) {
                $ratio = $heightratio;
            }
            $newWidth = $width * $ratio;
            $newHeight = $height * $ratio;
            if (function_exists("imagecopyresampled")) {
                $newIm = imagecreatetruecolor($newWidth, $newHeight);
                imagecopyresampled($newIm, $im, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            } else {
                $newIm = imagecreate($newWidth, $newHeight);
                imagecopyresized($newIm, $im, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            }
            ImageJpeg($newIm, $fileName);
            ImageDestroy($newIm);
        } else {
            ImageJpeg($im, $fileName);
        }
    }

    /**
     * 运行文件
     * @param $isSaveSrc 是否保存源文件
     */
    public function run($isSaveSrc = false)
    {
        if (empty($_FILES)) return $this;
        $request = Request();
        $files = [];
        foreach ($_FILES as $key => $val) {
            $name = $val['name'];
            $pos = strrpos($name, ".");
            if ($pos > 0) {
                $fileExt = strtolower(trim(substr($name, $pos)));
            } else {
                $fileExt = "";
            }
            $time = time();
            $fileName = $this->fileName;
            $path = $this->path;
            if (empty($fileName)) {
                $path .= "/" . date("Ym/d", $time);
                $fileBase = $time . "_" . str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);
            } else {
                $fileBase = $fileName;
            }
            $file = $fileBase . $fileExt;
            if ($isSaveSrc || $pos === false || empty($this->resize)) {
                $files[$key]["file"] = $path . "/" . $file;
                $request->file($key)->storeAs($path, $file, 'public');
            } else {
                $dir = $this->savePath . $path . "/";
                !is_readable($dir) && $this->xFile->mkdir($dir);
            }
            if ($pos > 0) {
                $type = $val['type'];
                $tmpName = $val['tmp_name'];
                if ($val['size']) {
                    if ($type == "image/pjpeg" || $type == "image/jpeg") {
                        $im = imagecreatefromjpeg($tmpName);
                    } elseif ($type == "image/x-png" || $type == "image/png") {
                        $im = imagecreatefrompng($tmpName);
                    } elseif ($type == "image/gif") {
                        $im = imagecreatefromgif($tmpName);
                    }
                    if ($im) {
                        foreach ($this->resize as $keyName => $vList) {
                            list($w, $h) = $vList;
                            if (is_numeric($keyName)) $keyName = "{$w}_{$h}";
                            $bFile = $path . "/" . $fileBase . "_{$w}_{$h}" . $fileExt;
                            $thumbPath = $this->savePath . $bFile;
                            $this->resizeImage($im, $w, $h, $thumbPath);
                            $files[$key][$keyName] = $bFile;
                        }
                        ImageDestroy($im);
                    }
                }
            }
        }
        $this->files = $files;
        return $this;
    }

    /**
     * 获取浏览文件路径
     * @param null $keyNamme
     * @return array
     */
    public function urls($keyNamme = "", $isReal = false)
    {
        $retFiles = [];
        if (empty($this->files)) return $retFiles;
        $files = $this->files;
        $savePath = $this->savePath;
        $surl = rtrim(trim(Storage::url("")), "/") . "/";
        if (empty($keyNamme)) {
            if ($isReal) {
                foreach ($files as $key => $val) {
                    foreach ($val as $k => $v) {
                        $v = ltrim(trim($v), "/");
                        $retFiles[$key][$k] = [$surl . $v, $savePath . $v];
                    }
                }
            } else {
                foreach ($files as $key => $val) {
                    foreach ($val as $k => $v) {
                        $v = ltrim(trim($v), "/");
                        $retFiles[$key][$k] = $surl . $v;
                    }
                }
            }
        } elseif (!empty($files[$keyNamme])) {
            $fileInfo = $files[$keyNamme];
            if ($isReal) {
                foreach ($fileInfo as $key => $val) {
                    $val = ltrim(trim($val), "/");
                    $retFiles[$key] = [$surl . $val, $savePath . $val];
                }
            } else {
                foreach ($fileInfo as $key => $val) {
                    $val = ltrim(trim($val), "/");
                    $retFiles[$key] = $surl . $val;
                }
            }
        }
        return $retFiles;
    }
}
