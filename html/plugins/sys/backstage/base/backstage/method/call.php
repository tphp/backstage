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
    public static $plu;

    private static function addMethod($methodKeyName = '', $path = '')
    {
        self::$plu->method($methodKeyName, $path)->auto(false);
    }

    private static function addMethods($configs = [])
    {
        foreach ($configs as $methodKeyName => $path) {
            self::addMethod($methodKeyName, $path);
        }
    }

    /**
     * 创建页面
     */
    public static function create($obj)
    {
        self::$plu->method('_', 'backstage.api:start');

        self::addMethods([
            // JSON 格式
            ':json' => 'backstage.api.config:json',
            // HTML 格式
            ':html' => 'backstage.api.config:html',

            // 数据列表
            ':list' => 'backstage.api.list:config',
            '::list' => 'backstage.api.list:html',

            // 数据绑定-列表
            ':bind' => 'backstage.api.bind:config',
            '::bind' => 'backstage.api.bind:html',

            // 数据绑定-已绑定
            ':isbind' => 'backstage.api.bind:config',
            '::isbind' => 'backstage.api.bind:html',

            // 数据绑定-未绑定
            ':unbind' => 'backstage.api.bind:config',
            '::unbind' => 'backstage.api.bind:html',

            // 数据绑定-扩展绑定
            ':extends' => 'backstage.api.bind:config',
            '::extends' => 'backstage.api.bind:html',

            // 新增数据
            ':add' => 'backstage.api.edit:config',
            '::add' => 'backstage.api.edit:html',

            // 编辑数据
            ':edit' => 'backstage.api.edit:config',
            '::edit' => 'backstage.api.edit:html',

            // 查看数据
            ':view' => 'backstage.api.edit:config',
            '::view' => 'backstage.api.edit:html',

            // 编辑数据独立页面
            ':handle' => 'backstage.api.handle:config',
            '::handle' => 'backstage.api.edit:html',

            // 复制数据
            ':copy' => 'backstage.api.edit:config',
            '::copy' => 'backstage.api.edit:html',

            // 级联查询
            '::selectTree' => 'backstage.api.handle:selectTree',

            // 删除表数据
            '::delete' => 'backstage.api.delete:delete',
            '::deletes' => 'backstage.api.delete:deletes',

            // 清空表数据
            '::clear' => 'backstage.api.clear:html',

            // 列表数据字段筛选
            '::sys' => 'backstage.api.sys:html',

            // 列表数据字段筛选
            '::upload' => 'backstage.api.upload:html',
        ]);
    }
};
