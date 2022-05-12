<!DOCTYPE html>
@php
    $staticDomainPath = $plu->static();
    $staticAdmin = $staticDomainPath."admin/";
    $includePath = $tplBase.$tplPath."/viml";
    $isIe = plu('sys.default')->call('tools:isIe');
    $color = $_DC_['color'];
    if(!empty($pageinfo)){
        $underShow = true;
        $underShowStr = "true";
    }else{
        $underShow = false;
        $underShowStr = "false";
    }
    $plu->css([
        'js/layui/css/layui.css',
        'js/layui/css/modules/formselects/v4/formselects-v4.css',
        'admin/css/font-awesome.min.css',
        'admin/vim/css/list.css',
    ])->js([
        '@js/jquery/jquery.min.js',
        '@js/layui/layui.js',
        '@js/jquery.index.js',
        'admin/vim/js/list.js'
    ]);

    $tplInit = \Tphp\Config::$tpl->tplInit;

    $oper = $vim['oper'];
    foreach ($oper as $key => $val) {
        if (!is_array($val)) {
            continue;
        }

        $vUrl = $val['url'];
        if (is_string($vUrl)) {
            $vUrl = trim($vUrl);
            if ($vUrl[0] == '.' && ($vUrl[1] == '/' || $vUrl[1] == '\\')) {
                $vUrl = "/{$tplInit}/" . substr($vUrl, 2);
                $oper[$key]['url'] = $vUrl;
            }
        }
    }
@endphp
<html>
<head>
    <meta charset="utf-8" />
    <title>TPL : {{$tplPath}}</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    {!! $plu->view('vim.style', [
        'fieldCount' => empty($fieldSrc) ? 0 : count($fieldSrc)
    ]) !!}
    <script>var layer_is_list = "yes"; </script>
</head>
<body data-tpl-type="{{$tplType}}" data-base-url="/{{$tplPath}}" data-isfixed="{{$vim['isfixed']}}" data-oper-title="{{$operTitle}}" data-handle-width="{{$vim['handleinfo']['width']}}" data-handle-height="{{$vim['handleinfo']['height']}}" data-handle-fixed="{{$vim['handleinfo']['fixed']?'true':'false'}}" data-handle-ismax="{{$vim['handleinfo']['ismax']?'true':'false'}}" data-istree="{{$isTree?'true':'false'}}" data-fd='{!! json__encode($field) !!}' data-tree='{!! json__encode($tree) !!}' data-menu-id='{{$_GET['_mid_']}}'>
<div class="web_loadding" style="color:#999">正在加载中...</div>
@if(!empty($search))
<form class="layui-form" action="">
    <div class="layui-form-item">
        @foreach($search as $key=>$val)
        @php
            $fk = $field[$key];
            if(isset($val['name'])){
                $fkName = $val['name'];
            }else{
                $fkName = $fk['name'];
            }
            $tp = $val['type'];
            if(in_array($tp, ['radio', 'checkbox', 'selects'])){
                $searchWidth = "auto";
            }else{
                if (empty($val['width'])) {
                    $searchWidth = "150px";
                } else {
                    $searchWidth = "{$val['width']}px";
                }
            }
            $fkSize = $val['size'];
        @endphp
        <div class="lfi-in">
            <label class="layui-form-label">{{strlen($fkName) > 20 ? $key : $fkName}}</label>
            @if($tp == 'time' || $tp == 'between')
                <div class="layui-input-inline layui-input-between" style="margin-right: 0px;"><input type="text" class="layui-input {{ $tp == 'time' ? "js_search_time" : "" }}" name="{{$key}}" value="{{$_GET[$key]}}" id="search_{{$key}}"></div>
                <div class="layui-input-inline" style="width:auto;margin: 3px;padding: 0px;">-</div>
                <div class="layui-input-inline layui-input-between"><input type="text" class="layui-input {{ $tp == 'time' ? "js_search_time" : "" }}" name="{{$key}}__" value="{{$_GET[$key."__"]}}" id="search_{{$key}}__"></div>
            @else
                <div class="layui-input-inline" style="width: {{$searchWidth}}; @if($tp == 'radio') margin-top: -3px; @endif">
                @if($tp == 'status')
                <select name="{{$key}}" lay-filter="change">
                    <option value=""></option>
                    @foreach($val['list'] as $k=>$v) <option value="{{$k}}" @if(isset($_GET[$key]) && $_GET[$key] == $k) selected="" @endif>{!!$v!!}</option> @endforeach
                </select>
                @elseif($tp == 'select')
                <select name="{{$key}}" lay-filter="change">
                    @if ($val['istop'] !== false) <option value=""></option> @endif
                    @foreach($val['list'] as $k=>$v) <option value="{{$k}}" @if(isset($_GET[$key]) && $_GET[$key] == $k) selected="" @endif>{!!$v!!}</option> @endforeach
                </select>
                @elseif($tp == 'selects' || $tp == 'checkbox')
                <select name="{{$key}}" xm-select="{{$key}}" xm-select-skin="default" xm-select-search="" lay-filter="change">
                    @php
                        $gv = explode(",", $_GET[$key]);
                    @endphp
                    <option value=""></option>
                    @foreach($val['list'] as $k=>$v) <option value="{{$k}}" @if(in_array($k, $gv)) selected="" @endif>{!!$v!!}</option> @endforeach
                </select>
                @elseif($tp == 'radio')
                    <input type="{{$tp}}" name="{{$key}}" value="" title="不选" @if(empty($_GET[$key])) checked="" @endif >
                    @foreach($val['list'] as $k=>$v) <input type="{{$tp}}" name="{{$key}}" value="{{$k}}" title="{{$v}}" @if(isset($_GET[$key]) && $_GET[$key] == $k) checked="" @endif > @endforeach
                @else
                    <input type="text" name="{{$key}}" autocomplete="off" class="layui-input" value="{{$_GET[$key]}}" @if(isset($fkSize)) size="{{$fkSize}}" @endif>
                @endif
                </div>
            @endif
        </div>
        @endforeach
        <div class="layui-inline">
            <button class="layui-btn layui-btn-primary layui-btn-xs js_search_submit" lay-submit="" lay-filter="search">搜索</button>
            <button class="layui-btn layui-btn-primary layui-btn-xs" lay-submit="" lay-filter="reset">重置</button>
        </div>
    </div>
</form>
@endif
<div class="layui-form-batch">
    <div class="js_batch">
        <div class="layui-table-tool">
            @if(!empty($fieldSrc))<div class="layui-inline js_tools_ext" title="筛选列"><i class="layui-icon layui-icon-cols"></i></div>@endif
            <div class="layui-inline js_tools_export" title="导出"><i class="layui-icon layui-icon-export"></i></div>
            <div class="layui-inline js_tools_print" title="打印"><i class="layui-icon layui-icon-print"></i></div>
        </div>
        @if(!empty($fieldSrc))
        <div class="js_tools_ext_box">
            <select name="_field_set_args_" xm-select="_field_set_args_" xm-select-skin="default" xm-select-search="" style="position: absolute">
                @foreach($fieldSrc as $key=>$val)
                <option value="{{$key}}" @if($val['disabled']) disabled="" selected="" @elseif($val['selected']) selected="" @endif>{{$val['name']}}</option>
                @endforeach
            </select>
        </div>
        @endif
        <ul class="js_tools_export_box">
            @if($vim['is']['checkbox'])<li class="js_batch_export_checked">导出所选</li>@endif
            <li class="js_batch_export_this" style="color:#08F;">导出本页</li>
            <li class="js_batch_export_all" style="color:#080;">导出 {{$sqlLimit}} 行</li>
        </ul>
        @if(!empty($vim['batch']))
        @foreach($vim['batch'] as $key=>$val)
            @if($key == 'handle')
                @foreach($vim['handles'] as $k=>$v)<button class="layui-btn layui-btn-primary layui-btn-xs js_batch_{{$key}}" data-hdkey="{{$k}}" lay-batch="" lay-filter="{{$key}}">{{$v['key']}}</button>@endforeach
            @else
            <button class="layui-btn layui-btn-primary layui-btn-xs js_batch_{{$key}}" lay-batch="" lay-filter="{{$key}}">{{$val}}</button>
            @endif
        @endforeach
        @endif
        <button class="layui-btn layui-btn-primary layui-btn-xs js_flush">刷新</button>
        @if(!empty($vim['batchhtml']))
            @if (is_string($vim['batchhtml']))
                {!! $vim['batchhtml'] !!}
            @elseif (is_array($vim['batchhtml']))
                @foreach($vim['batchhtml'] as $bh)
                    @if(is_string($bh)) {!! $bh !!} @endif
                @endforeach
            @endif
        @endif
    </div>
</div>
<div class="layui-table-view" style="margin: 10px; display: none; overflow: hidden;" data-click-find="thead tr th .layui-edge" layui-height-ful="0" layui-isunder="{{$underShowStr}}">
    <div class="layui-table-header">
        @if(!empty($field))
        <table class="layui-table" lay-size="sm">
            <thead>
            <tr>
                @if($vim['is']['numbers'])<th data-type="numbers"></th>@endif
                @if($vim['is']['checkbox'])<th data-type="checkbox"></th>@endif
                @foreach($field as $key=>$val)
                    @php
                        $addStr = "";
                        if ($val['order']) {
                            if ($_GET['_sort'] == $key) {
                                $addClass = $_GET['_order'];
                            } else {
                                $addClass = "noset";
                            }
                            $addStr = 'lay-sort="'.$addClass.'"';
                        }

                        if ($val['status']) {
                            $t = str_replace('"', "&quot;", $val['text']);
                            $addStr .= " data-type=\"status\" data-text=\"{$t}\"";
                        } elseif ($val['edit']) {
                            $addStr .= " data-edit=\"true\"";
                        } elseif (!empty($val['click'])) {
                            $vUrl = $val['click']['url'];
                            if (is_string($vUrl)) {
                                $vUrl = trim($vUrl);
                                if ($vUrl[0] == '.' && ($vUrl[1] == '/' || $vUrl[1] == '\\')) {
                                    $vUrl = "/{$tplInit}/" . substr($vUrl, 2);
                                    $val['click']['url'] = $vUrl;
                                }
                            }
                            $je = json__encode($val['click']);
                            $addStr .= " data-click='{$je}'";
                        }

                        if (isset($val['min-width'])) {
                            $t = str_replace('"', "&quot;", $val['min-width']);
                            $addStr .= " style=\"min-width: {$t}px;\"";
                        }
                    @endphp @if(!($val['hidden']))<th width="{{$val['width']}}" data-field="{{$key}}" {!!$addStr!!}>{!! $val['name'] !!}</th>
                    @endif
                @endforeach
                @if(!empty($vim['oper']))<th data-type="oper" width="{{$vim['operwidth']}}" data-json='{!! json__encode($oper) !!}'>操作</th>@endif
            </tr>
            </thead>
        </table>
        @endif
    </div>
    <div class="layui-table-body layui-table-main layui-form" lay-filter="table-body">
        @if(is_array($list))
        <table class="layui-table" lay-size="sm">
            <tbody>
            @foreach($list as $key=>$val)
                <tr pk='{!! $pkList[$key] !!}' pkmd5='{!! substr(md5($pkList[$key]), 8, 8) !!}' @if (!empty($val['@class'])) class="{{$val['@class']}}" @endif @if(isset($srcList[$key]['@child'])) child="{{$srcList[$key]['@child']}}" vchild="{{$srcList[$key][$tree['child']]}}" pkparent="" level="0" @endif >
                    @foreach($field as $k=>$v)
                    @if(!($v['hidden']))
                        @php
                            $_ss = '';
                            $_vv = '';
                            empty($field[$k]['length']) ? $__len = 200 : $__len = $field[$k]['length'];
                            if(isset($srcList[$key][$k])) {
                                $_ss = mb_strlen($srcList[$key][$k]) > $__len ? $plu->call('tools:mbSubstrChange', $srcList[$key][$k], $__len) : $srcList[$key][$k];
                            }

                            if(isset($val[$k])) {
                                strlen($val[$k]) > $__len ? $_vv = $plu->call('tools:mbSubstrChange', $val[$k], $__len, true) : $_vv = $val[$k];
                                $_vg = $_GET[$k];
                                if($v['type'] == 'tree'){
                                    $_vg = str_replace("/", " ", $_vg);
                                    $_vgs = explode(' ', $_vg); $_vgKvs = [];
                                    foreach ($_vgs as $__k => $__v){
                                        $__v = trim($__v);
                                        if(!empty($__v)){
                                            $_vv = str_replace($__v, "_#{$__v}#_", $_vv);
                                            $_vgKvs[$__v] = "_#{$__v}#_";
                                        }
                                    }
                                    foreach ($_vgKvs as $__k => $__v){
                                        $_vv = $plu->call('tools:replaceStrToHtml', $__v, "<span style='color:#F33'>{$__k}</span>", $_vv);
                                    }
                                } else {
                                    $v['type'] != 'between' && !empty($_vg) && $_vv = $plu->call('tools:replaceStrToHtml', $_vg, "<span style='color:#F33'>{$_vg}</span>", $_vv);
                                }
                            }
                        @endphp
                        <td> @if(isset($srcList[$key][$k])) <s>{{ $_ss }}</s> @endif @if(isset($val[$k])) <v> {{ $_vv }}</v>@endif </td>
                    @endif
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
        @else
            <div style="margin: 20px;color:#F00">错误信息：{{$list}}</div>
        @endif
    </div>
    @if($underShow)
    <div class="layui-table-page">
        @if(!empty($pageinfo))<div id="pageinfo" style="float: right; margin-right: 15px; margin-bottom: 50px;" data-count="{!! $pageinfo['total'] !!}" data-page="{!! $pageinfo['now'] !!}" data-pagesize="{!! $pageinfo['size'] !!}" data-pagesizedef="{!! $pageinfo['sizedef'] !!}" data-color="{{$color[1]}}"></div>@endif
    </div>
    @endif
</div>
<div style="width: 1px; height: 1px;overflow: hidden;"><textarea id="WindowCopy" style="border: none;"></textarea></div>
<script src="{{url($staticDomainPath.'js/layui/lay/modules/formselects-v4.js')}}" @if($isIe) type="text/babel" @else type="text/javascript" charset="utf-8" @endif></script>
@php
    $view_exists = view()->exists($includePath);
    $old_plu = null;
    if ($view_exists) {
        $basePluPath = \Tphp\Config::$domainPath->basePluPath;
        if ($basePluPath !== $plu->dir) {
            $old_plu = $plu;
            $plu = plu($basePluPath);
        }
    }
@endphp
@if($view_exists)@include($includePath)
@endif
@php
    if (!empty($old_plu)) {
        $plu = $old_plu;
    }
@endphp
</body>
</html>
