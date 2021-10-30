@php
    $color = $_DC_['color'];
    list($borwserName) = plu('sys.default')->call('tools:getBrowser');
    if(!empty($color) && is_array($color) && count($color) >= 2){
        $c2 = $color[1];
    }else{
        $c2 = '2e99d4';
    }

    if (empty($fieldCount)) {
        $fieldCount = 0;
    } else {
        $fieldCount ++;
    }

    if ($fieldCount > 8) {
        $fieldCount = 8;
    }

    $plu->css([
        'admin/vim/css/index.css',
    ]);

    if ($borwserName === 'IE') {
        $plu->js([
            'js/ie/babel.min.js',
        ]);
    }
@endphp
<style>
    .layui-table-cell .js_tree_fa,
    .layui-tab-brief>.layui-tab-title .layui-this,
    .layui-form-radio>i:hover,
    .layui-form-radioed>i,
    .layui-elem-field legend{
        color:#{{$c2}} !important;
    }
    .layui-form-radio{
        margin: 4px 10px 0 0;
    }
    .layui-form-select dl{
        width: 100%;
    }
    .layui-form-select dl dd.layui-this,
    .layui-btn-normal,
    .layui-btn{
        background-color:#{{$c2}} !important;
    }
    .layui-select-option-first dl.layui-anim dd.layui-this:first-child,
    .layui-btn-primary,
    .layui-laydate .layui-this{
        background-color:#FFF !important;
    }
    .layui-form-onswitch,
    .layui-layer-btn .layui-layer-btn0,
    .layui-form-checked[lay-skin=primary] i{
        border-color: #{{$c2}} !important;
        background-color: #{{$c2}} !important;
    }
    .layui-form-checkbox[lay-skin=primary]:hover i{border-color:#{{$c2}} !important;}
    .layui-tab-brief>.layui-tab-more li.layui-this:after,
    .layui-tab-brief>.layui-tab-title .layui-this:after{
        border-color:#{{$c2}} !important;
    }
    div[xm-select-skin=default] dl dd:not(.xm-dis-disabled) i{
        border-color:#{{$c2}} !important;
    }
    div[xm-select-skin=default] dl dd.xm-select-this:not(.xm-dis-disabled) i{
        color:#{{$c2}} !important;
    }

    .layui-transfer .layui-transfer-active button{
        background-color: #{{$c2}} !important;
    }

    .layui-transfer .layui-transfer-active button.layui-btn-disabled{
        background-color: #FFF !important;
    }

    .js_tools_ext_box .xm-select-dl{
        height: {{ $fieldCount * 36 + 12 }}px !important;
    }
</style>
@if($borwserName !== 'Chrome')
<style>
    .layui-table-view .layui-table-body{
        margin-right: -3px;
    }
    .layui-table-view .layui-table-cell{
        display: flex;
    }
    .layui-table-view .laytable-cell-numbers, .layui-table-view .laytable-cell-checkbox{
        display: table;
    }
</style>
@endif