function dir_select_tree(obj, input_obj, data_str, title, width, height, is_parent, fun) {
    var input_name = input_obj.attr("name");
    var input_val = input_obj.val().replace(/\\/g, "/");
    var js_dir_flag = "js__dir__" + input_name;

    if(data_str === undefined){
        data_str = obj.attr("data-json");
    }
    var data;
    if(typeof data_str === 'string'){
        data = eval("(" + data_str + ")");
    }else{
        data = data_str;
    }
    if(width === undefined){
        width = '800px';
    }

    if(height === undefined){
        height = '500px';
    }

    var _parent;
    if(is_parent === undefined || is_parent === true){
        _parent = parent;
    }else{
        _parent = self;
    }

    var layeropen = _parent.layer.open({
        type: 1
        , title: title
        , area: [width, height]
        , shade: 0.1
        , maxmin: true
        , offset: 'auto'
        , content: '<div class="css_dir" id="' + js_dir_flag + '"></div>'
    });
    _parent.dbclick_close(layeropen);

    var tree_src_list = input_val.split("/");
    var tree_list = [];
    for (var i = 0; i < tree_src_list.length; i ++){
        var tsl = tree_src_list[i].trim();
        if(tsl !== ''){
            tree_list.push(tsl);
        }
    }
    input_val = tree_list.join("/");
    var tl_len = tree_list.length;
    var tmp_data = data;
    var tstr = "";
    for(var i = 0; i < tl_len - 1; i ++){
        if(tstr === ""){
            tstr = tree_list[i];
        }else{
            tstr += "/" + tree_list[i];
        }
        var is_break = true;
        for(var j in tmp_data){
            var td = tmp_data[j];
            if(td.id === tstr){
                is_break = false;
                tmp_data[j]['spread'] = true;
                tmp_data = tmp_data[j]['children'];
                break;
            }
        }
        if(is_break){
            break;
        }
    }
    //生成树结构
    layui.tree.render({
        elem: _parent.$('#' + js_dir_flag)
        ,data: data
        ,onlyIconControl: true
        ,click: function(_obj){
            if(typeof fun === 'function'){
                var ret = fun(_obj.data, input_name);
                if(ret === false){
                    return false;
                }
            }
            input_obj.val(_obj.data.id);
            _parent.layer.close(layeropen);
        }
    });

    var input_val_lower = input_val.toLowerCase();
    _parent.$(".layui-tree-set").each(function () {
        var ivl = $(this).attr("data-id").toLowerCase();
        if(ivl === input_val_lower){
            $(this).find(">.layui-tree-entry>.layui-tree-main>.layui-tree-txt").css("color", "#080");
        }
    });
    return layeropen;
}

function dir_select_field(obj, input_obj, data_list, width, height, is_parent, btn_name, fun, is_close) {
    var input_name = input_obj.attr("name");
    var input_val = input_obj.val().trim().toLowerCase();
    var js_dir_flag = "js_dir_" + input_name;

    var data_str = data_list[0];
    var table_name = data_list[1];
    if(data_str === undefined){
        data_str = obj.attr("data-json");
    }
    var data = eval("(" + data_str + ")");
    if(width === undefined){
        width = '800px';
    }

    if(height === undefined){
        height = '500px';
    }

    var _parent;
    if(is_parent === undefined || is_parent === true){
        _parent = parent;
    }else{
        _parent = self;
    }

    var html = "";

    for(var i in data){
        var iv = data[i];
        var _id = iv['id'].replace(/"/gi, "&quot;");
        var _title = iv['title'].replace(/"/gi, "&quot;");
        var chked = "";
        if(_id.trim().toLowerCase() === input_val){
            chked = ' checked="checked"';
        }
        var remark = "";
        if(iv['remark'] !== undefined && iv['remark'] !== ''){
            remark = ' title="' + iv['remark'] + '"';
        }
        html += '<div class="js_field_radio" ' + remark + '><input type="radio" name="radio_name" value="' + _id + '" title="' + _title + '"' + chked + '></div>';
    }

    var is_btn = false;
    var btns;
    if(btn_name !== undefined && btn_name !== ''){
        if(typeof fun === 'function'){
            is_btn = true;
        }
    }

    if(is_btn){
        btns = ['选择', btn_name, '取消'];
    }else{
        btns = ['选择', '取消'];
    }

    var config = {
        type: 1
        , title: "选择 <span class=\"next_name_flag\">&gt;</span> " + obj.parent().parent().find('.layui-form-label').html()
        , area: [width, height]
        , shade: 0.1
        , maxmin: true
        , offset: 'auto'
        , content: '<div class="layui-form layui-form-pane tpl_field_radio_in" lay-filter="' + js_dir_flag + '">' + html + '</div>'
        , btn: btns
        , yes: function (index) {
            var layer_obj = $("#layui-layer" + index);
            var chk = layer_obj.find(".layui-form-radioed");
            if(chk.size() > 0){
                input_obj.val(chk.parent().find("input").val());
                _parent.layer.close(index);
            }else{
                _parent.layer.msg("未选择数据", {icon: 0});
            }
        }
    };

    if(is_btn){
        config['btn2'] = function (index) {
            var layer_obj = $("#layui-layer" + index);
            var chk = layer_obj.find(".layui-form-radioed");
            if(chk.size() > 0){
                fun(chk.parent().find("input").val());
            }
            if(is_close) {
                _parent.layer.close(index);
            }else{
                return false;
            }
        };
    }

    var layeropen = _parent.layer.open(config);

    if(table_name !== undefined) {
        $("#layui-layer" + layeropen + ' .layui-layer-btn').append('<span>表名：' + table_name + '</span>').find('span').css({
            'float': 'left',
            'line-height': '40px'
        });
    }
    layui.use(['element', 'form'], function() {
        var form = _parent.layui.form;
        form.render(null, js_dir_flag);
    });
    _parent.dbclick_close(layeropen);
    return layeropen;
}

function dir_select_field_yes(input_obj, data_list, width, height, is_parent, bt_name, fun, is_close) {
    var js_dir_flag = "js_dir_" + input_obj.attr('name');
    var input_val = input_obj.val().toLowerCase();

    var data_str = data_list[0];
    var table_name = data_list[1];
    var data = eval("(" + data_str + ")");
    if(width === undefined){
        width = '800px';
    }

    if(height === undefined){
        height = '500px';
    }

    var _parent;
    if(is_parent === undefined || is_parent === true){
        _parent = parent;
    }else{
        _parent = self;
    }

    var html = "";

    for(var i in data){
        var iv = data[i];
        var _id = iv['id'].replace(/"/gi, "&quot;");
        var _title = iv['title'].replace(/"/gi, "&quot;");
        var chked = "";
        if(_id.trim().toLowerCase() === input_val){
            chked = ' checked="checked"';
        }
        var remark = "";
        if(iv['remark'] !== undefined && iv['remark'] !== ''){
            remark = ' title="' + iv['remark'] + '"';
        }
        html += '<div class="js_field_radio" ' + remark + '><input type="radio" name="radio_name" value="' + _id + '" title="' + _title + '"' + chked + '></div>';
    }

    if(bt_name === undefined){
        bt_name = '选择';
    }

    var config = {
        type: 1
        , title: "选择字段"
        , area: [width, height]
        , shade: 0.1
        , maxmin: true
        , offset: 'auto'
        , content: '<div class="layui-form layui-form-pane tpl_field_radio_in" lay-filter="' + js_dir_flag + '">' + html + '</div>'
        , btn: [bt_name, '取消']
        , yes: function (index) {
            var layer_obj = $("#layui-layer" + index);
            var chk = layer_obj.find(".layui-form-radioed");
            if(chk.size() > 0){
                var v = chk.parent().find("input").val();
                input_obj.val(v);
                if(chk.size() > 0 && typeof fun === 'function'){
                    fun(v);
                }
                if(is_close) {
                    _parent.layer.close(index);
                }else{
                    return false;
                }
            }else{
                _parent.layer.msg("未选择数据", {icon: 0});
            }
        }
    };

    var layeropen = _parent.layer.open(config);

    if(table_name !== undefined) {
        $("#layui-layer" + layeropen + ' .layui-layer-btn').append('<span>表名：' + table_name + '</span>').find('span').css({
            'float': 'left',
            'line-height': '40px'
        });
    }
    layui.use(['element', 'form'], function() {
        var form = _parent.layui.form;
        form.render(null, js_dir_flag);
    });
    _parent.dbclick_close(layeropen);
    return layeropen;
}

/**
 * 设置目录的选择状态
 * @param dirs
 * @param set_list
 */
function dir_set_checked(dirs, set_list) {
    for(var i in dirs){
        var iv = dirs[i];
        var iv_children = iv['children'];
        if(iv_children !== undefined && iv_children !== null){
            dir_set_checked(iv_children, set_list);
        }else{
            var iv_checked = iv['checked'];
            if(iv_checked){
                set_list[iv['id']] = true;
            }else{
                set_list[iv['id']] = false;
            }
        }
    }
}