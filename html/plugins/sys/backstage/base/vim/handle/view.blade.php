<!DOCTYPE html>
@php
    $staticDomainPath = $plu->static();
    $staticAdmin = $staticDomainPath."admin/";
    $includePath = $tplBase.$tplPath."/vimh";
    $isIe = plu('sys.default')->call('tools:isIe');
    $pkStr = $_GET['pk'];
    !empty($pkStr) && $pkStr = "&pk={$pkStr}";

    $plu->css([
        'js/layui/css/layui.css',
        'admin/css/font-awesome.min.css',
        'admin/vim/css/edit.css',
    ])->js([
        '@js/layui/layui.js',
        '@js/jquery/jquery.min.js',
        '@js/jquery.index.js',
        'admin/vim/js/edit.js',
    ]);
@endphp
<html>
<head>
    <meta charset="utf-8" />
    <title>TPL : {{$tplPath}}</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    {!! $plu->view('vim.style') !!}
</head>
<body data-tpl-type="{{$tplType}}" data-base-url="/{{$tplPath}}" data-field='{!! empty($field) ? '' : json__encode($field) !!}'>
@if(!empty($handleGroup) && is_array($handleGroup))
@php
    $handleGroupCount = count($handleGroup);
@endphp
<form class="layui-form @if($handleGroupCount <= 1) layui-form-main @endif" action="" lay-filter="main">
    @if($handleGroupCount > 1)
    <div class="layui-tab layui-tab-brief">
        <ul class="layui-tab-title js_tab_head">
            @foreach($handleGroup as $gKey=>$handle)
            <li data-tab="{{$gKey}}" @if($gKey == $handleGroupChecked) class="layui-this" @endif data-md5="{{ substr(md5($gKey), 8, 8) }}">{{empty($gKey) ? '基本设置' : $gKey}}</li>
            @endforeach
        </ul>
        <div class="layui-tab-content @if($handleGroupCount > 1) layui-form-main @endif">
    @endif
    @foreach($handleGroup as $gKey=>$handle)
        @if($handleGroupCount > 1) <div class="layui-tab-item  @if($gKey == $handleGroupChecked) layui-show @endif js_tab_body" data-md5="{{ substr(md5($gKey), 8, 8) }}"> @endif
        @foreach($handle as $key=>$val)
            @php
                $type = $val['type'];
                empty($val['list']) && $val['list'] = [];
                $_verify = $val['verify'];
                $_verifyStr = "";
                if(!empty($_verify)){
                    $_verifyStr .= "lay-verify=\"{$_verify}\"";
                    $_verify[0] != '@' && $_verifyStr .= " placeholder=\"请输入{$val['src_name']}\"";
                }
                if($isView || $val['view']){
                    $_isView = true;
                    $view = 'disabled=""';
                }else{
                    $_isView = false;
                    $view = '';
                }

                $keyId = substr(md5($key), 12, 8);
            @endphp
            @if($type == 'hidden')
                <input type="hidden" @if($val['md5']) data-name="{{$key}}" @else name="{{$key}}" @endif>
            @elseif($type == 'label')
            <div class="layui-form-item">
                <label class="layui-form-label">{!!$val['name']!!}</label>
                <div class="layui-input-block">
                    <div class="layui-form-mid layui-word-aux">{!! $field[$key] !!}</div>
                </div>
            </div>
            @elseif($type == 'segment')
            <fieldset class="layui-elem-field layui-field-title"><legend>{!!$val['name']!!}</legend></fieldset>
            @elseif($type == 'password')
            <div class="layui-form-item">
                <label class="layui-form-label">{!!$val['name']!!}</label>
                <div class="layui-input-block">
                    <input type="{{$type}}" @if($val['md5']) data-name="{{$key}}" @else name="{{$key}}" @endif {!!$_verifyStr!!} class="layui-input js_password_readonly" {!!$view!!} readonly>
                </div>
            </div>
                @if($val['md5'])
                <div class="layui-form-item">
                    <label class="layui-form-label">确认{!!$val['name']!!}</label>
                    <div class="layui-input-block">
                        <input type="{{$type}}" name="{{$key}}" lay-verify="md5" class="layui-input" {!!$view!!}>
                    </div>
                </div>
                @endif
            @elseif($type == 'article')
            <div class="layui-form-item">
                @if (!empty($val['name'])) <label class="layui-form-label">{!!$val['name']!!}</label> @endif
                <div @if (!empty($val['name'])) class="layui-input-block" @endif style="z-index:100;">
                    @php
                        empty($val['height']) ? $h = 300 : $h = $val['height'];
                        is_numeric($h) ? $hstr = "{$h}px" : $hstr = $h;
                    @endphp
                    <textarea class="js_ueditor" name="{{$key}}" id="editor_{{$keyId}}" style="width:100%;height:{{$hstr}};" {!!$view!!}></textarea>
                </div>
            </div>
            @elseif($type == 'markdown')
                <div class="layui-form-item">
                    @if (!empty($val['name'])) <label class="layui-form-label">{!!$val['name']!!}</label> @endif
                    <div @if (!empty($val['name'])) class="layui-input-block" @endif>
                        <div class="js_markdown" id="markdown_{{$keyId}}" style="width:100%; z-index: 801;">
                            <textarea name="{{$key}}" style="display: none;" {!!$view!!}></textarea>
                        </div>
                    </div>
                </div>
            @elseif($type == 'select')
                <div class="layui-form-item layui-form" lay-filter="filter_{{$key}}">
                    <label class="layui-form-label">{!!$val['name']!!}</label>
                    <div class="layui-input-block">
                        <select name="{{$key}}" {!!$view!!}>
                            @if(!isset($val['istop']) || $val['istop']) <option value="" @if(empty($field[$key])) selected="" @endif></option> @endif
                            @foreach($val['list'] as $k=>$v) <option value="{{$k}}">{!!$v!!}</option> @endforeach
                        </select>
                    </div>
                </div>
            @elseif($type == 'selects' || $type == 'checkbox')
                <div class="layui-form-item layui-form" lay-filter="filter_{{$key}}">
                    <label class="layui-form-label">{!!$val['name']!!}</label>
                    @php
                        empty($field[$key]) ? $gv = [] : $gv = explode(",", $field[$key]);
                        $isCheckbox = $isIe || $type == 'checkbox';
                        $afdIsInt = strpos($allField[$key]['type'], 'int');
                    @endphp
                    <div @if($isCheckbox) class="layui-input-block js_checkbox" data-key="{{$key}}" @else class="layui-input-block" @endif>
                        @if($isCheckbox)
                            @foreach($val['list'] as $k=>$v)<input type="checkbox" name="{{$key}}[]" lay-skin="primary" value="{{$k}}" title="{{$v}}" @if(in_array($k, $gv)) checked="" @endif>@endforeach
                        @else
                        <select name="{{$key}}" @if($afdIsInt === false) xm-select="{{$key}}" xm-select-skin="default" xm-select-search="" @endif {!!$view!!}>
                            @if($afdIsInt === false)
                                <option value=""></option>
                            @else
                                @if(!isset($val['istop']) || $val['istop']) <option value="" @if(empty($field[$key])) selected="" @endif></option> @endif
                            @endif
                            @foreach($val['list'] as $k=>$v) <option value="{{$k}}" @if(in_array($k, $gv)) selected="" @endif>{!!$v!!}</option> @endforeach
                        </select>
                        @endif
                    </div>
                </div>
            @elseif($type == 'radio')
                <div class="layui-form-item layui-form" lay-filter="filter_{{$key}}">
                    <label class="layui-form-label">{!!$val['name']!!}</label>
                    <div class="layui-input-block">
                        @php
                            $vv=$field[$key];
                        @endphp
                        @if($val['top'] !== false) <input type="{{$type}}" name="{{$key}}" value="" title="不选" @if(empty($vv)) checked="" @endif {!!$view!!}> @endif
                        @foreach($val['list'] as $k=>$v) <input type="{{$type}}" name="{{$key}}" value="{{$k}}" title="{{$v}}" @if($k == $vv) checked="" @endif {!!$view!!}> @endforeach
                    </div>
                </div>
            @elseif($type == 'image' || $type == 'file')
                @php
                    $type == 'image' ? $typeName = '上传图片' : $typeName = '上传文件';
                @endphp
                <div class="layui-form-item">
                    <label class="layui-form-label">{!!$val['name']!!}</label>
                    <div class="layui-input-block">
                        <input class="file_upload" type="file" data-name="{{$key}}" name="_file_{{$key}}" data-url="/{{$tplPath}}.upload?field={{$key}}{{$pkStr}}">
                        <input class="layui-input js_image_or_file" type="text" name="{{$key}}" {!!$view!!}>
                        <button class="layui-btn layui-btn-primary js_btn_image_or_file" data-type="{{$type}}" {!!$view!!}>{{$typeName}}</button>
                    </div>
                    @if($type == 'image')
                        @php
                            $thumbs = $val['thumbs'];
                        @endphp
                        @if(!empty($thumbs) && is_array($thumbs))
                            @foreach($thumbs as $k=>$v) @if(!isset($handle[$k]) && isset($allField[$k])) <input name="{{$k}}" id="{{$k}}" type="hidden" value="{{$handle[$k]}}"> @endif @endforeach
                        @endif
                        <div class="layui-input-block js_image_show">
                            <a href="{{$field[$key]}}" target="_blank"><img src="{{$field[$key]}}" /></a>
                            <span></span>
                        </div>
                    @endif
                </div>
            @elseif($type == 'trees')
                <div class="layui-form-item">
                    <label class="layui-form-label">{!!$val['name']!!}</label>
                    <div class="layui-input-block">
                        <div class="js_input_trees" name="{{$key}}" id="trees_{{$keyId}}" data-json='{!! json__encode($val['list']) !!}' {!!$view!!}></div>
                    </div>
                </div>
            @elseif($type == 'dir')
                <div class="layui-form-item">
                    <label class="layui-form-label">{!!$val['name']!!}</label>
                    <div class="layui-input-block">
                        <input class="layui-input js_dir" type="text" name="{{$key}}" {!!$view!!} {!!$_verifyStr!!}>
                        <div class="layui-btn layui-btn-primary css_btn_dir js_btn_dir" data-name="{{$key}}" data-json='{!! json__encode($val['list']) !!}' {!!$view!!}><i class="fa fa-ellipsis-h" title="ellipsis-h"></i></div>
                    </div>
                </div>
            @elseif($type == 'field')
                @php
                    $vfd = $val['list'];
                @endphp
                @if(!empty($vfd) && is_array($vfd))
                    @php
                        $vfdKv = json_decode($field[$key], true);
                        empty($vfdKv) && $vfdKv = [];
                    @endphp
                    @foreach($vfd as $k=>$v)
                    <div class="layui-form-item">
                        <label class="layui-form-label">{!!$v!!}</label>
                        <div class="layui-input-block">
                            <input class="layui-input" name="{{$key}}[{{$k}}]" type="text" value="{{$vfdKv[$k]}}" {!!$view!!}>
                        </div>
                    </div>
                    @endforeach
                @else
                    <div class="layui-form-item">
                        <div class="layui-input-block">
                            <label class="layui-form-label" style="width: auto; padding-left: 0px; color:#999;">{!!$val['name']!!}未设置</label>
                        </div>
                    </div>
                @endif
            @elseif($type == 'tpl')
                @php
                    $vTpl = trim($val['tpl']);
                @endphp
                @if(!empty($vTpl))
                    @php
                        strpos($vTpl, ".") === false && $vTpl .= ".html";
                        empty($val['config']) ? $tcf = [] : $tcf = $val['config'];
                    @endphp
                    {!! tpl($vTpl, $tcf) !!}
                @endif
            @elseif($type == 'html')
                {!! $val['value'] !!}
            @elseif($type == 'bind' || $type == 'extends')
                <input type="hidden" name="_#{{$key}}#_" value="yes">
                <iframe class="css_iframe_inbox" id="html_{{$keyId}}" name="{{$key}}" data-src="{!! $val['src'] !!}"></iframe>
            @else
            <div class="layui-form-item">
                <label class="layui-form-label">{!!$val['name']!!}</label>
                @if($type == 'status')
                <div class="layui-input-block">
                    <input class="js_status" type="checkbox" name="{{$key}}" lay-skin="switch" lay-text="{{$val['text']}}" {!!$view!!}>
                </div>
                @elseif($type == 'tree')
                    <input type="hidden" name="{{$key}}" {!!$view!!} />
                    <div class="js_tree layui-select-option-first" data-function="{{$val['function']}}" data-isview="{{$_isView?'true':'false'}}" data-json='{!! json__encode($val['text']) !!}' data-notvalues='{!! json__encode($val['notValues']) !!}' data-key="{{$key}}"></div>
                @elseif($type == 'textarea')
                    <div class="layui-input-block">
                        <textarea class="layui-textarea" @if(!empty($val['rows'])) rows="{{ $val['rows'] }}" @endif type="{{$type}}" name="{{$key}}" {!!$_verifyStr!!} class="layui-input" {!!$view!!}></textarea>
                    </div>
                @elseif($type == 'time')
                    <div class="layui-input-block">
                        <input class="layui-input js_input_time" type="text" name="{{$key}}" id="time_{{$keyId}}" {!!$_verifyStr!!} {!!$view!!}>
                    </div>
                @else
                <div class="layui-input-block">
                    <input type="{{$type}}" name="{{$key}}" {!!$_verifyStr!!} class="layui-input" {!!$view!!}>
                </div>
                @endif
            </div>
            @endif
        @endforeach
        @if($handleGroupCount > 1) </div> @endif
    @endforeach
    @if($handleGroupCount > 1)
        </div>
    </div>
    @endif
    <div style="display: none"><button class="layui-btn layui-btn-submit" lay-submit="" lay-filter="submit">立即提交</button><button type="reset" class="layui-btn  layui-btn-reset">重置</button></div>
</form>
@if($tplType == 'handle')
    <div class="layui-form layui-form-main">
        <div class="layui-input-block">
        <button class="layui-btn layui-btn-normal js_btn_save">保存</button>
        <button class="layui-btn layui-btn-primary js_btn_reset">还原</button>
        <button class="layui-btn layui-btn-primary js_btn_flush">刷新</button>
        </div>
    </div>
@endif
@endif

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

@php
    $verifyFile = env('BACKSTAGE_VERIFY');
@endphp
@if(!empty($verifyFile) && is_string($verifyFile))<script src="{{url($verifyFile)}}" charset="utf-8"></script>
@endif
{!! $plu->view('vim.handle.js', [
    'staticAdmin' => $staticAdmin,
    'staticDomainPath' => $staticDomainPath,
    'types' => $types
]) !!}
@if($handleGroupCount > 1)
    <script>
        layui.use(['element'], function(){

        })
    </script>
@endif
</body>
</html>
