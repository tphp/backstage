@php
    $keyName = $_GET['key'];
    if ($keyName == 'copy') {
        $plu->css('js/layui/css/layui.css')->js([
            '@js/jquery/jquery.min.js',
            '@js/jquery.index.js',
            '@js/layui/layui.js',
        ]);
    }
    $idInc = 1;
@endphp
<textarea id="copy"></textarea>
<input class="js_input" type="hidden" name="{{$keyName}}" value="{{$_GET['value']}}" />
@foreach($_ as $key=>$val)
    <div class="group-main">
        <div class="group-title">{{$key}}</div>
        <div class="group-body">@foreach($val as $k=>$v)<i id="{!! "id_" . $idInc ++ !!}" class="fa fa-{{$v}}" title="{{$v}}"></i>@endforeach</div>
    </div>
@endforeach
