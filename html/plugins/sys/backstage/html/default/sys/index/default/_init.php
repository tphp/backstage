<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

return function(){
    function get_sys_linux_info(){
        $str = shell_exec('more /proc/meminfo');
        $pattern = "/(.+):\s*([0-9]+)/";
        preg_match_all($pattern, $str, $out);
        $infoStr = "<div>物理内存总量：<span>".intval($out[2][0] / 1024)."MB</span></div>";
        $infoStr .= "<div>内存使用率：<span>".round((100 * ($out[2][0] - $out[2][1])) / $out[2][0], 2)."%</span></div>";

        $mode = "/(cpu)[\s]+([0-9]+)[\s]+([0-9]+)[\s]+([0-9]+)[\s]+([0-9]+)[\s]+([0-9]+)[\s]+([0-9]+)[\s]+([0-9]+)[\s]+([0-9]+)/";
        $string=shell_exec("more /proc/stat");
        preg_match_all($mode, $string, $arr);
        $total = $arr[2][0] + $arr[3][0] + $arr[4][0] + $arr[5][0] + $arr[6][0] + $arr[7][0] + $arr[8][0] + $arr[9][0];
        $time = $arr[2][0] + $arr[3][0] + $arr[4][0] + $arr[6][0] + $arr[7][0] + $arr[8][0] + $arr[9][0];
        $percent = round($time / $total, 3);
        $percent = $percent * 100;
        $infoStr .= "<div>CPU使用率:<span>".$percent."%</span></div>";

        $fp = popen('df -lh | grep -E "^(/)"',"r");
        $rs = fread($fp, 10240);
        pclose($fp);
        $rs = preg_replace("/\s{2,}/",' ',$rs);  //把多个空格换成 “_”
        $hds = explode("\n", trim($rs));
        if(!empty($hds)) {
            $infoStr .= "<table class=\"layui-table\" lay-size=\"sm\" style='margin-top: 20px; width: 600px;'><thead><tr><th>硬盘路径</th><th>总容量</th><th>已使用</th><th>剩余</th><th>使用率</th></tr></thead>";
            foreach ($hds as $hd) {
                $hs = explode(" ", trim($hd));
                $infoStr .= "<tr><td>{$hs[5]}</td><td>$hs[1]</td><td>$hs[2]</td><td>$hs[3]</td><td>$hs[4]</td></tr>";
            }
            $infoStr .= "</table>";
        }
        return $infoStr;
    }

    $links = plu(\Tphp\Config::$domainPath->basePluPath)->getConfig('default.backstage.links');
    if (is_function($links)) {
        $links = $links();
    }
    $this->setView('list', $links);
    $version = "<div>Laravel版本：<span>".app()::VERSION."</span></div>";
    if(strtoupper(substr(PHP_OS,0,3)) === 'WIN'){
        $this->setView('sysInfo', "{$version}<div style='color:#666;'>".php_uname()."</div>");
    }else{
        $this->setView('sysInfo', $version.get_sys_linux_info());
    }
};
