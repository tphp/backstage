<script>
    var g_fieldstr = $('body').attr('data-field');
    var g_field = {};
    if(g_fieldstr == undefined || g_fieldstr == ''){
        g_field = {};
    }else{
        g_field = eval('(' + g_fieldstr + ')');
    }
</script>
@php
    if ($types['article']) {
        $plu->call('load', 'ueditor');
        $plu->js([
            ':handle/article'
        ]);
    }

    if ($types['markdown']) {
        $plu->runJs('handle/markdown', $staticDomainPath);
    }

    if ($types['time']) {
        $plu->js([
            ':handle/time'
        ]);
    }

    if ($types['selects']) {
        $plu->js([
            ':handle/selects'
        ]);
    }

    if ($types['trees']) {
        $plu->runJs('handle/trees', $staticDomainPath);
    }

    if ($types['image'] || $types['file']) {
        $plu->js([
            ':handle/file'
        ]);
    }

    if ($types['dir']) {
        $plu->js([
            ':handle/dir'
        ]);
    }
@endphp