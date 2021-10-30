@php
    $listLen = count($list);
@endphp
@if(!$isPost)
<div class="nav">
    <button class="layui-btn layui-btn-primary layui-btn-xs layui-git js_git_status">查看GIT状态</button>
    <button class="layui-btn layui-btn-primary layui-btn-xs layui-git js_git_pull">GIT下拉</button>
    <button class="layui-btn layui-btn-primary layui-btn-xs js_flush">刷新</button>
</div>
<div class="main">
    <div class="text">
@endif
@foreach($list as $name => $clist)
    <div class="text_in">
        @if($listLen > 1) <fieldset class="layui-elem-field layui-field-title"><legend>{{$name}}</legend></fieldset> @endif
        @foreach($clist as $text)
            <p>{!! $text !!}</p>
        @endforeach
    </div>
@endforeach
@if(!$isPost)
    </div>
</div>
@endif