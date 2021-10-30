<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

use Tphp\Basic\Tpl\Run as Run;

return new class {
    /**
     * @see Run::mbSubstrChange()
     */
    public function mbSubstrChange($str, $length = 200, $isChange = false)
    {
        return Run::mbSubstrChange($str, $length, $isChange);
    }

    /**
     * 删除script标签
     * @param string $str
     * @return string
     */
    public function delScript($str = '')
    {
        $str = str_ireplace("<script", "<span", $str);
        $str = str_ireplace("</script", "</span", $str);
        return $str;
    }

    /**
     * 替换HTML标签中的字符
     * @param string $search
     * @param string $replace
     * @param string $subject
     * @return mixed
     */
    public function replaceStrToHtml($search = '', $replace = '', $subject = '')
    {
        if (!is_string($subject)) {
            $subject = "{$subject}";
        }
        $sLen = strlen($subject);
        $isTag = false;
        $tmpStr = "";
        $retStr = "";
        for ($i = 0; $i < $sLen; $i++) {
            $si = $subject[$i];
            if ($si == '<') {
                $isTag = true;
                if (!empty($tmpStr)) {
                    $tmpStr = str_replace($search, $replace, $tmpStr);
                    $retStr .= $tmpStr;
                    $tmpStr = "";
                }
                $retStr .= $si;
                continue;
            } elseif ($si == '>') {
                $isTag = false;
                $retStr .= $si;
                continue;
            }
            if ($isTag) {
                $retStr .= $si;
            } else {
                $tmpStr .= $si;
            }
        }
        if (!empty($tmpStr)) {
            $retStr .= str_replace($search, $replace, $tmpStr);
        }
        return $retStr;
    }
};