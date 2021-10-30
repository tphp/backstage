<form class="layui-form" action="">
    <div class="layui-form-item">
        <div class="lfi-in">
            <label class="layui-form-label">根目录</label>
            <div class="layui-input-inline" style="width: 300px;">
                <select id="top" lay-skin="switch" lay-filter="switchTest" data-value="{{$top}}">
                    @foreach($tops as $key => $val)
                    <option value="{{$key}}" @if($top == $key) selected="selected" @endif>{{$val}}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="layui-inline">
            <button class="layui-btn layui-btn-primary layui-btn-xs js_btn_flush">刷新</button>
        </div>
    </div>
</form>
<div class="layui-table-view" style="margin: 10px; border: none">
    @if(empty($childs))
        <div style="color: #666">目录 {{$top}} 无插件</div>
    @else
        <table class="layui-hide" id="list" data-dirs='{!! json__encode($childs) !!}' lay-filter="list"></table>
    @endif
</div>
@php
@endphp
<script type="text/html" id="TplDir">
    @{{#  if(d.help){ }}
        <a href="/help/plugins/@{{ d.full }}" target="_blank">@{{ d.dir }}</a>
    @{{#  } else { }}
        @{{ d.dir }}
    @{{#  } }}
</script>
@foreach($fields as $fd)
<script type="text/html" id="Tpl{{ $fd['title'] }}">
    {!! "@{{#  if(d.{$fd['field']}){ }}" !!}
    <i class="css_icon fa fa-check"></i>
    @{{#  } }}
</script>
@endforeach
<script type="text/html" id="TplHelp">
    @{{#  if(d.help){ }}
    <button class="layui-btn layui-btn-primary layui-btn-xs js_help" lay-event="help">查看</button>
    @{{#  } }}
</script>

<script>
    var fields = {!! json__encode($fields) !!};
</script>