layui.use(['table', 'form'], function(){
    var table = layui.table;
    var dirs = $('#list').attr("data-dirs");
    var dirsjson = {};
    if(dirs != undefined){
        dirsjson = $.parseJSON(dirs);
    }

    var cols = [
        {field: 'dir', title: '目录', width: 150, templet: '#TplDir'}
        , {field: 'name', title: '名称'}
    ];
    for (var i in fields) {
        cols.push(fields[i]);
    }

    cols.push({field: 'help', title:'帮助文档', width:80, align: 'center', templet: '#TplHelp'});

    table.render({
        elem: '#list'
        ,cols: [cols]
        ,data: dirsjson
        ,limit: 10000
        ,height: 'full-' + 100
        ,size: 'sm'
    });

    //监听行单击事件（双击事件为：rowDouble）
    table.on('tool(list)', function(obj){
        if(obj.event === 'help') {
            var data = obj.data;
            var title = data.name;
            if (title === undefined || title === '') {
                title = data.dir;
            }
            var layeropen = layer.open({
                type: 2
                , title: title
                , area: ['600px', '400px']
                , shade: 0.1
                , maxmin: true
                , content: '/help/plugins/' + data.full
                ,success: function(data, index){
                    setTimeout(function () {
                        var child_obj = document.getElementById("layui-layer-iframe" + index).contentWindow;
                        if (child_obj.vue_help !== undefined) {
                            child_obj.vue_help.resize();
                        }
                    }, 10);
                }
            });
            layer.full(layeropen);
            dbclick_close(layeropen);
        }
    });

    $(".js_btn_flush").click(function () {
        $().urlparam().run();
        return false;
    });

    var top = $("#top");
    top.parent().find("dl.layui-anim dd").click(function () {
        var _this = $(this);
        var value = _this.attr("lay-value");
        if (top.attr('data-value') !== value) {
            $().urlparam().set("top", value).run();
        }
    });
});