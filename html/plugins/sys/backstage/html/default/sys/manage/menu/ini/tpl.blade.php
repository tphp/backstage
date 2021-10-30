@php
    $plu->js([
        '@js/ace/ace.js',
        '@js/ace/ext-language_tools.js',
    ]);
@endphp
<div class="layui-collapse" style="border-top: none; border-left: none; border-right: none">
@foreach($_ as $key=>$val)
    <div class="layui-colla-item">
        <h2 class="layui-colla-title" style="color:#000; background-color: #FFF;">{{$val['_name_']}}</h2>
        <div class="layui-colla-content layui-show">
        @php
            $valArgs = $val['_args_'];
            $valFlag = $val['_flag_'];
            $filePath = "/".$sysNote[$valFlag]['path']
        @endphp
        @if(!empty($valFlag))
            <div class="css-main-inner">
                <p>调用名称： {{$valFlag}}</p>
                <p>路径： {{$filePath}}</p>
                @if(is_string($valArgs))
                    <p>{{$valArgs}}</p>
                @elseif(is_array($valArgs))
                    @foreach($valArgs as $kk=>$vv)
                        @if(is_numeric($kk))
                            <p>{{$vv}}</p>
                        @elseif(is_string($vv))
                            <p>{{$kk}}: {{$vv}}</p>
                        @elseif(is_array($vv))
                            <p>{{$kk}}: </p>
                            @foreach($vv as $kkk=>$vvv)
                                <p>&nbsp;&nbsp;&nbsp;&nbsp;{{$kkk}}: @if(is_bool($vvv)) @if($vvv) true @else false @endif @else {{$vvv}} @endif</p>
                            @endforeach
                        @endif
                    @endforeach
                @endif
                <p><a class="js_view_code" data-name="{{$valFlag}}" data-md5-name="{{substr(md5($valFlag), 12, 8)}}" href="javascript:;">点击查看代码</a></p>
            </div>
        @endif
        @if(!empty($val['_next_']) && is_array($val['_next_']))
            <div class="layui-collapse">
            @foreach($val['_next_'] as $k=>$v)
                @php
                    $vArgs = $v['_args_'];
                    $vFlag = $v['_flag_'];
                    $filePath = "/".$sysNote[$vFlag]['path']
                @endphp
                @if(!empty($vFlag))
                    <div class="layui-colla-item">
                        <h2 class="layui-colla-title">{{$v['_name_']}} @if(!empty($v['_next_'])) <a class="js_view_next" data-next-url="{{$filePath}}" href="javascript:;">更多&gt;&gt;</a> @endif</h2>
                        <div class="layui-colla-content">
                            <p>调用名称： {{$vFlag}}</p>
                            <p>路径： {{$filePath}}</p>
                            @if(is_string($vArgs))
                                <p>{{$vArgs}}</p>
                            @elseif(is_array($vArgs))
                                @foreach($vArgs as $kk=>$vv)
                                    @if(is_numeric($kk))
                                        <p>{{$vv}}</p>
                                    @elseif(is_string($vv))
                                        <p>{{$kk}}: {{$vv}}</p>
                                    @elseif(is_array($vv))
                                        <p>{{$kk}}: </p>
                                        @foreach($vv as $kkk=>$vvv)
                                            <p>&nbsp;&nbsp;&nbsp;&nbsp;{{$kkk}}: @if(is_bool($vvv)) @if($vvv) true @else false @endif @else {{$vvv}} @endif</p>
                                        @endforeach
                                    @endif
                                @endforeach
                            @endif
                            <p><a class="js_view_code" data-name="{{$vFlag}}" data-md5-name="{{substr(md5($vFlag), 12, 8)}}" href="javascript:;">点击查看代码</a></p>
                        </div>
                    </div>
                @endif
            @endforeach
            </div>
        @endif
        </div>
    </div>
@endforeach
</div>
<script>
    var code_url = "{{$codeUrl}}";
    var is_menu_ini = true;
</script>