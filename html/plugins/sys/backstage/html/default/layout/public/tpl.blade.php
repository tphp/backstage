<!DOCTYPE html>
@php
    $isBackstage = false;
    if(isset(\Tphp\Config::$domain)){
        $dc = \Tphp\Config::$domain;
        isset($dc['backstage']) && $isBackstage = $dc['backstage'];
        empty($title) && isset($dc['title']) && $title = $dc['title'];
    }

    $plu->css([
        'admin/css/font-awesome.min.css',
    ])->js([
        '@js/jquery/jquery.min.js',
    ]);
@endphp
<html lang="zh-CN">
<head>
    <meta name="keywords" content="{{$keywords}}" />
    <meta name="description" content="{{$description}}" />
    <title>{{$title}}</title>
    @if($isBackstage)
    {!! $plu->view('vim.style') !!}
    @endif
</head>
<body>
{!! $__tpl__ !!}
</body>
</html>
