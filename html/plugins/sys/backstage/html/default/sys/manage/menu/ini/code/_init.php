<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

use Tphp\Register;

return function (){
    if($this->isPost()) {
        $code = "";
        $name = $_POST['name'];
        if (!empty($name)) {
            $apcu = Tphp\Basic\Apcu::__init();
            $sysNote = $apcu->apcuFetch('_sysnote_');
            $xFile = import("XFile");
            $funcPath = "/function/" . $sysNote[$name]['path'] . "/func.php";
            $filePath = Register::getHtmlPath(true) . $funcPath;
            if(!is_file($filePath)) {
                foreach (Register::$viewPaths as $vp) {
                    $filePath = $vp . $funcPath;
                    if (is_file($filePath)) {
                        break;
                    }
                }
            }
            $code = $xFile->read($filePath);
        }
        EXITJSON(1, htmlspecialchars($code));
    }
    EXITJSON(0, '404错误');
};