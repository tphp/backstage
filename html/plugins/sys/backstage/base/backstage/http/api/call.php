<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

use Tphp\Basic\Http;

return new class
{
    /**
     * 获取数据下标，以 "." 隔开
     * @param array $data
     * @param string $index
     * @return array|mixed|null
     */
    private static function getApiIndex($data = [], $index = "")
    {
        $ret = $data;
        $indexArr = explode(".", $index);
        foreach ($indexArr as $ia) {
            if (!is_array($ret)) {
                $ret = null;
                break;
            }
            $ret = $ret[trim($ia)];
        }
        return $ret;
    }

    /**
     * 外部接口数据
     * @param $config
     * @param array $cPage
     * @param array $field
     * @param null $obj
     * @return array
     */
    public static function run($config, $cPage = [], $field = [], $obj = null)
    {
        $url = $config['url'];
        list($url, $params) = Http::getUrlParams($url);
        $tplType = $obj->tplType;
        if ($tplType == 'list') {
            $page = $config['page'];
        } else {
            $page = false;
        }
        $gets = $_GET;
        $pk = $gets['pk'];
        $isExp = false;
        if ($page === false) {
            // 非分页初始化设置
            $isPage = false;
            if (empty($config['list'])) {
                $config['list'] = 'data';
            }
        } else {
            // 分页初始化设置
            $isPage = true;
            if (!is_array($page)) {
                $page = [];
            }

            // 分页参数传递
            if (empty($page['params'])) {
                $page['params'] = [];
            }
            if (empty($page['params']['page'])) {
                $page['params']['page'] = 'p';
            }
            if (empty($page['params']['pagesize'])) {
                $page['params']['pagesize'] = 'psize';
            }

            $pName = $page['params']['page'];
            $p = $gets['p'];
            empty($p) && $p = 1;
            $gets[$pName] = $p;
            $gets['p'] = $p;
            $pSize = $gets['psize'];
            // 如果是列表并且是导出状态
            if ($obj->methodType == 'list') {
                $exp = $_GET['_@export@_'];
                if (in_array($exp, ['this', 'all'])) {
                    // 导出全部
                    if ($exp == 'all') {
                        $pSize = env('SQL_LIMIT', 10000);
                        !is_numeric($pSize) && $pSize = 10000;
                    }
                    $isExp = true;
                }
            }
            $pSizeName = $page['params']['pagesize'];
            if (empty($pSize)) {
                $pSize = $cPage['pagesize'];
            }
            if (empty($pSize)) {
                $pSize = $cPage['pagesizedef'];
            }
            if (!empty($pSize)) {
                $gets[$pSizeName] = $pSize;
            }
            $gets['psize'] = $pSize;

            // 分页信息获取
            if (empty($page['info'])) {
                $page['info'] = [];
            }
            if (empty($page['info']['page'])) {
                $page['info']['page'] = 'data.p';
            }
            if (empty($page['info']['pagesize'])) {
                $page['info']['pagesize'] = 'data.psize';
            }
            if (empty($page['info']['total'])) {
                $page['info']['total'] = 'data.total';
            }
            $config['page'] = $page;

            // 分页数据获取
            if (empty($config['list'])) {
                $config['list'] = 'data.list';
            }
        }

        foreach ($gets as $key => $val) {
            $params[$key] = $val;
        }
        $pageGet = $config['get'];
        if (is_array($pageGet)) {
            foreach ($pageGet as $key => $val) {
                $params[$key] = $val;
            }
        }
        if (!empty($pk)) {
            $pkJson = json_decode($pk, true);
            if (!empty($pkJson)) {
                $pkJson0 = $pkJson[0];
                if (!empty($pkJson0)) {
                    $pk0 = json_decode($pkJson0, true);
                    if (is_array($pk0)) {
                        foreach ($pk0 as $key => $val) {
                            $params[$key] = $val;
                        }
                    }
                }
            }
        }

        $posts = $_POST;
        $pagePost = $config['post'];
        if (is_array($pagePost)) {
            foreach ($pagePost as $key => $val) {
                $posts[$key] = $val;
            }
        }
        $headers = [];
        $pageHeader = $config['header'];
        if (is_array($pageHeader)) {
            foreach ($pageHeader as $key => $val) {
                $headers[$key] = $val;
            }
        }
        $method = $config['method'];

        $paramStr = http_build_query($params);
        $url .= "?{$paramStr}";

        if ($tplType == 'delete') {
            $pData = $posts['data'];
            unset($posts['data']);
            $deletes = [];
            foreach ($pData as $pd) {
                $pdArr = json_decode($pd, true);
                !empty($pdArr) && is_array($pdArr) && $deletes[] = $pdArr;
            }
            if (count($deletes) > 0) {
                if (count($deletes[0]) > 1) {
                    foreach ($deletes[0] as $k => $v) {
                        $posts[$k] = $v;
                    }
                } else {
                    foreach ($deletes as $del) {
                        foreach ($del as $k => $v) {
                            $v = trim($v);
                            if (empty($v)) {
                                continue;
                            }
                            $v = str_replace("'", "''", $v);
                            if (empty($posts[$k])) {
                                $posts[$k] = [];
                            }
                            $posts[$k][] = $v;
                        }
                    }
                    foreach ($posts as $key => $val) {
                        if (is_array($val)) {
                            $posts[$key] = json_encode($val, true);
                        }
                    }
                }
            }
        }
        if ($isExp) {
            $posts = [];
        }
        $html = Http::getHttpData($url, $posts, $method, $headers);
        $html = trim($html);
        if (empty($html)) {
            EXITJSON(0, "获取数据错误： {$url}");
        }
        $htmlData = json_decode($html, true);
        if (empty($htmlData)) {
            EXITJSON(0, $html);
        }

        // 返回状态设置
        if (!isset($config['code']) || !is_string($config['code'])) {
            $code = false;
        } elseif (empty($config['code'])) {
            $code = 'code';
            $codeOk = 1;
        } else {
            $code = $config['code'];
            if (is_string($code)) {
                $codeOk = 1;
            } elseif (is_array($code)) {
                list($code, $codeOk) = $code;
                empty($code) && $code = 'code';
                empty($codeOk) && $codeOk !== 0 && $codeOk = 1;
            } else {
                $code = 'code';
                $codeOk = 1;
            }
        }

        $msg = $config['msg'];
        if (empty($msg) || !is_string($msg)) {
            $msg = 'msg';
        }

        $msgValue = self::getApiIndex($htmlData, $msg);

        if ($code !== false) {
            // 错误提醒
            $tCode = self::getApiIndex($htmlData, $code);
            if ($tCode != $codeOk) {
                EXITJSON(0, $msgValue);
            }
        }

        $list = self::getApiIndex($htmlData, $config['list']);
        if (!is_array($list)) {
            if (count($_POST) > 0) {
                EXITJSON(1, $msgValue);
            }
            $list = [];
        }
        // 新增时直接返回成功
        if (empty($list) && count($_POST) > 0 && $tplType == 'add') {
            EXITJSON(1, $msgValue);
        }
        // 删除直接返回信息
        if ($tplType == 'delete') {
            EXITJSON(1, $msgValue);
        }

        if ($isPage) {
            $cot = self::getApiIndex($htmlData, $page['info']['total']);
            $pageSize = $cPage['pagesize'];
            $currentPage = $gets['p'];
            $currentPage <= 0 && $currentPage = 1;
            $item = array_slice($list, ($currentPage - 1) * $pageSize, $pageSize);
            $hrUrl = $_SERVER['HTTP_REFERER'];
            $pos = strpos($hrUrl, '?');
            if ($pos > 0) {
                $hrUrl = substr($hrUrl, 0, $pos);
            }
            $pages = new \Illuminate\Pagination\LengthAwarePaginator($item, $cot, $pageSize, $currentPage, [
                'path' => $hrUrl,
                'pageName' => 'p'
            ]);
            $pages->page = $p;
            $pages->pageSize = $pageSize;
            $pages->pageSizeDef = $cPage['pagesizedef'];
            $pages->count = $cot;
        } else {
            $pages = [];
        }

        $fieldShow = [];
        $hasTitle = false;
        foreach ($field as $key => $val) {
            $val['title'] && $hasTitle = true;
            $fieldShow[$key] = [
                'name' => $val['name'],
                'key' => $val['title'] ? 'PRI' : '',
                'type' => 'text'
            ];
        }
        // 如果不存在主键则默认使用第一个字段
        if (!$hasTitle) {
            foreach ($field as $key => $val) {
                $fieldShow[$key]['key'] = 'PRI';
                break;
            }
        }

        return [1, $list, $fieldShow, $pages, ''];
    }
};