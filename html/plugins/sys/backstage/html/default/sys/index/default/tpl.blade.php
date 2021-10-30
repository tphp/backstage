@if(!empty($list) && is_array($list))
<fieldset class="layui-elem-field layui-field-title"><legend>快捷访问入口</legend></fieldset>

@foreach($list as $key=>$val)
    <a class="btn btn-medium btn-radius btn-normal" href="{{$key}}" target="_blank">{!! $val !!}</a>
@endforeach
@endif

<fieldset class="layui-elem-field layui-field-title"><legend>系统状态</legend></fieldset>

<div class="sys_info">
{!! $sysInfo !!}
</div>
