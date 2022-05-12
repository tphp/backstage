<!DOCTYPE html>
@php
    list($borwserName) = plu('sys.default')->call('tools:getBrowser');

    $plu->css([
        'js/layui/css/layui.css',
        'admin/css/font-awesome.min.css',
    ])->js([
        '@js/jquery/jquery.min.js',
        '@js/layui/layui.js',
        '@js/jquery.index.js',
    ]);

@endphp
<html lang="zh-CN">
<head>
    <meta name="keywords" content="{{$keywords}}" />
    <meta name="description" content="{{$description}}" />
    <title>{{$title}}</title>
    {!! $plu->view('vim.style') !!}
    @if($borwserName !== 'Chrome')
        <style>
            .layui-form-checkbox{
                margin: auto !important;
                top: 0px !important;
            }
        </style>
    @endif
</head>
<body data-tpl-type="{{$tplType}}" data-base-url="/{{$tplPath}}" data-field='{!! empty($field) ? '' : json__encode($field) !!}'>
{!! $__tpl__ !!}
</body>
</html>
