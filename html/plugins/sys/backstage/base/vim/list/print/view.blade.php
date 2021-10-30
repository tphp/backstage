<!DOCTYPE html>
@php
    $plu->css([
        'js/layui/css/layui.css',
    ])->js([
        '@js/jquery/jquery.min.js',
    ]);
@endphp
<html>
<head>
    <meta charset="utf-8" />
    <title>{{$title}}</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <style>
        table{
            display: none;
        }
        .layui-table tbody tr:hover{
            background-color: #FFFFFF !important;
        }
    </style>
</head>
<body>
<table class="layui-table" lay-size="sm" style="display: none;">
    <thead>
    <tr>
        @foreach($field as $key=>$val)<th style="width: {{is_numeric($fieldKv[$key]) ? $fieldKv[$key]."px" : "calc(".$fieldKv[$key]." - {$cotWidth}px)"}};">{{$val}}</th>@endforeach
    </tr>
    </thead>
    <tbody>
    @foreach($list as $key=>$val)
    <tr>
        @foreach($field as $fKey=>$fVal)<td>{!! $plu->call("tools:delScript", $val[$fKey]) !!}</td>@endforeach
    </tr>
    @endforeach
    </tbody>
</table>
<script>
    $(function () {
        $('table').show();
    });
</script>
</body>
</html>
