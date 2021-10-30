<?php

/**
 * This file is part of the tphp/backstage library
 *
 * @link        http://github.com/tphp/backstage
 * @copyright   Copyright (c) 2021 TPHP (http://backstage.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

/**
 * 加载对应的文件
 */
return new class
{
    public $plu;

    /**
     * 加载静态文件
     * @param string $static
     */
    private function loadStatic($static = '', $borwserName = null)
    {

        if (!is_string($static)) {
            return;
        }

        $static = trim($static);
        if (empty($static)) {
            return;
        }

        $plu = $this->plu;

        switch ($static) {
            case 'ueditor':
                $plu->js([
                    "@{$plu->dir}/js/ueditor" => [
                        'ueditor.config.js'
                    ],
                    "@{$plu->dir}/js/ueditor/src" => [
                        'editor.js',
                        'core/browser.js',
                        'core/utils.js',
                        'core/EventBase.js',
                        'core/dtd.js',
                        'core/domUtils.js',
                        'core/Range.js',
                        'core/Selection.js',
                        'core/Editor.js',
                        'core/Editor.defaultoptions.js',
                        'core/loadconfig.js',
                        'core/ajax.js',
                        'core/filterword.js',
                        'core/node.js',
                        'core/htmlparser.js',
                        'core/filternode.js',
                        'core/plugin.js',
                        'core/keymap.js',
                        'core/localstorage.js',
                        'plugins/defaultfilter.js',
                        'plugins/inserthtml.js',
                        'plugins/autotypeset.js',
                        'plugins/autosubmit.js',
                        'plugins/background.js',
                        'plugins/image.js',
                        'plugins/justify.js',
                        'plugins/font.js',
                        'plugins/link.js',
                        'plugins/iframe.js',
                        'plugins/scrawl.js',
                        'plugins/removeformat.js',
                        'plugins/blockquote.js',
                        'plugins/convertcase.js',
                        'plugins/indent.js',
                        'plugins/print.js',
                        'plugins/preview.js',
                        'plugins/selectall.js',
                        'plugins/paragraph.js',
                        'plugins/directionality.js',
                        'plugins/horizontal.js',
                        'plugins/time.js',
                        'plugins/rowspacing.js',
                        'plugins/lineheight.js',
                        'plugins/insertcode.js',
                        'plugins/cleardoc.js',
                        'plugins/anchor.js',
                        'plugins/wordcount.js',
                        'plugins/pagebreak.js',
                        'plugins/wordimage.js',
                        'plugins/dragdrop.js',
                        'plugins/undo.js',
                        'plugins/copy.js',
                        'plugins/paste.js',
                        'plugins/puretxtpaste.js',
                        'plugins/list.js',
                        'plugins/source.js',
                        'plugins/enterkey.js',
                        'plugins/keystrokes.js',
                        'plugins/fiximgclick.js',
                        'plugins/autolink.js',
                        'plugins/autoheight.js',
                        'plugins/autofloat.js',
                        'plugins/video.js',
                        'plugins/table.core.js',
                        'plugins/table.cmds.js',
                        'plugins/table.action.js',
                        'plugins/table.sort.js',
                        'plugins/contextmenu.js',
                        'plugins/shortcutmenu.js',
                        'plugins/basestyle.js',
                        'plugins/elementpath.js',
                        'plugins/formatmatch.js',
                        'plugins/searchreplace.js',
                        'plugins/customstyle.js',
                        'plugins/catchremoteimage.js',
                        'plugins/insertparagraph.js',
                        'plugins/webapp.js',
                        'plugins/template.js',
                        'plugins/music.js',
                        'plugins/autoupload.js',
                        'plugins/autosave.js',
                        'plugins/charts.js',
                        'plugins/section.js',
                        'plugins/simpleupload.js',
                        'plugins/serverparam.js',
                        'plugins/insertfile.js',
                        'ui/ui.js',
                        'ui/uiutils.js',
                        'ui/uibase.js',
                        'ui/separator.js',
                        'ui/mask.js',
                        'ui/popup.js',
                        'ui/colorpicker.js',
                        'ui/tablepicker.js',
                        'ui/stateful.js',
                        'ui/button.js',
                        'ui/splitbutton.js',
                        'ui/colorbutton.js',
                        'ui/tablebutton.js',
                        'ui/autotypesetpicker.js',
                        'ui/autotypesetbutton.js',
                        'ui/cellalignpicker.js',
                        'ui/pastepicker.js',
                        'ui/toolbar.js',
                        'ui/menu.js',
                        'ui/combox.js',
                        'ui/dialog.js',
                        'ui/menubutton.js',
                        'ui/multiMenu.js',
                        'ui/shortcutmenu.js',
                        'ui/breakline.js',
                        'ui/message.js',
                        'adapter/editorui.js',
                        'adapter/editor.js',
                        'adapter/message.js',
                        'adapter/autosave.js'
                    ]
                ]);
                break;
            case 'markdown':
                $plu->css('js/markdown/css/editormd.min.css')->js('@js/markdown/editormd.min.js');
                break;
            case 'jquery':
                $plu->js([
                    '@js/jquery/jquery.min.js',
                    '@js/jquery.index.js'
                ]);
                break;
            case 'layui':
                $plu->css([
                    'js/layui/css/layui.css',
                    'admin/css/font-awesome.min.css'
                ])->js([
                    '@js/layui/layui.js'
                ]);
                break;
            case 'list':
                $plu->css('admin/vim/css/list.css')->js('admin/vim/js/list.js');
                break;
            case 'edit':
                $plu->css('admin/vim/css/edit.css')->js('admin/vim/js/edit.js');
                break;
        }
    }

    /**
     * 智能加载静态文件
     * @param array $statics
     */
    public function index(...$statics)
    {
        if (!is_array($statics)) {
            return;
        }

        list($borwserName) = plu('sys.default')->call('tools:getBrowser');

        foreach ($statics as $static) {
            $this->loadStatic($static, $borwserName);
        }
    }
};