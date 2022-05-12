var set_html_value, set_html_affer, set_html_handle, set_square, resize_init, tree_value_alls = {}, base_url;
$(function () {
    var body = $('body');
    base_url = body.attr("data-base-url"); // 根页面
    var oper_title = body.attr("data-oper-title"); //操作显示标题
    var handle_width = body.attr("data-handle-width"); // 弹框宽度
    var handle_height = body.attr("data-handle-height"); //弹框高度
    var handle_fixed = body.attr("data-handle-fixed"); // 弹框是固定宽高
    var handle_ismax = body.attr("data-handle-ismax"); // 弹框是否全屏
    var is_tree = body.attr("data-istree"); // 列表是否是树状结构
    var is_fixed = body.attr("data-isfixed"); // 列表是否是树状结构
    var menu_id = body.attr('data-menu-id'); //菜单目录ID
    var tpl_type = body.attr('data-tpl-type'); //菜单目录ID
    var g_tree_width = 17; //树状结构缩进
    var tree = eval("(" + body.attr("data-tree") + ")"); // 列表是否是树状结构
    var tree_value = '';
    var g_fd = eval("(" + body.attr("data-fd") + ")");
    var g_field = get_field(); // 列表是否是树状结构
    var is_shift = false; //shift按键触发
    var is_alt = false;
    var is_alt_td = false;
    var is_alt_keydown = false;
    var alt_td;
    var g_tmp_layout_id = 0;
    var g_tmp_layout_load = 0;
    var g_tmp_layout_loadok = false;
    var g_class = ".layui-table-body";
    var g_table = g_class + ">table";
    var g_tbody = g_table + ">tbody";
    var g_tr = g_tbody + ">tr";
    var g_up = $().urlparam();
    var g_type_src = g_up.get('__type__');
    var g_type = "__type__=" + g_type_src;
    var g_params = g_up.getParamArray(['p', 'psize'], {"__type__": g_type_src});
    var g_params_str = g_up.getParamString(g_params);
    var g_otype = g_up.get('otype', true);
    var g_pk = g_up.get('pk', true);
    var export_flag = "_@export@_";
    var g_next_name_flag = " <span class='next_name_flag'>&gt;</span> ";
    var js_search_submit = $(".js_search_submit");
    if (tree !== undefined) {
        tree_value = tree.value === undefined ? '' : tree.value + '';
    }

    $(document).mousedown(function (e) {
        var e = e || window.event;
        var elem = e.target || e.srcElement;
        var is_jte = false;
        var is_te = false;
        var is_texp = false;
        var is_exp = false;
        while (elem) {
            var jte_has = $(elem).hasClass('js_tools_ext');
            if(jte_has || $(elem).hasClass('xm-select-dl')){
                is_jte = true;
                if(jte_has){
                    is_te = jte_has;
                }
                break;
            }
            var jtexp_has = $(elem).hasClass('js_tools_export');
            if(jtexp_has || $(elem).hasClass('js_tools_export_box')){
                is_texp = true;
                if(jtexp_has){
                    is_exp = jtexp_has;
                }
                break;
            }
            elem = elem.parentNode;
        }
        //字段搜索功能
        var jx = $(".js_tools_ext_box .xm-select-dl");
        var is_show_box = jx.css("display");
        var is_hide_for = false;
        if(is_te){
            if(is_show_box === 'block'){
                is_hide_for = true;
            }else{
                jx.show();
                if($(".js_tools_ext_box").attr('data-old-value') === undefined){
                    $(".js_tools_ext_box").attr('data-old-value', string_to_arr_sort($("input[name=_field_set_args_]").val()));
                }
            }
        }else if(!is_jte){
            if(is_show_box === 'block'){
                is_hide_for = true;
            }
        }
        if(is_hide_for){
            jx.hide();
            var fsav = string_to_arr_sort($('input[name=_field_set_args_]').val());
            if($(".js_tools_ext_box").attr("data-old-value") !== fsav){
                layer.load(1, {
                    shade: [0.1, '#fff']
                });
                $.ajax({
                    type: "post",
                    url: base_url + ".sys?type=menu_field",
                    data: {
                        id: menu_id,
                        type: $().urlparam().get('__type__'),
                        field: fsav
                    },
                    async: false,
                    success: function () {
                        $().urlparam().run();
                    }
                });
            }
        }

        //导出功能
        var jteb = $('.js_tools_export_box');
        var is_show_box = jteb.css("display");
        if(is_exp){
            if(is_show_box === 'block'){
                jteb.hide();
            }else{
                jteb.show(200);
            }
        }else if(!is_texp){
            jteb.hide();
        }
    });

    function string_to_arr_sort(str) {
        if(str === undefined || str === ''){
            return '';
        }
        var arr = str.split(",").sort();
        return arr.join(",");
    }

    //去除导入导出参数
    function t_urlparam(url){
        if(url === undefined) {
            return $().urlparam().remove(export_flag);
        }else{
            return $().urlparam().setUrl(url).remove(export_flag);
        }
    }

    // 获取纯文本
    function get_div_text(t_html) {
        if(t_html === undefined){
            return t_html;
        }

        return t_html.replace(/<.*?>/g, "").replace(/</g, "&lt;").replace(/>/g, "&gt;");
    }

    // 设置返回信息
    function set_result_handle(result, is_copy, data) {
        var dt = result.data;
        if(is_tree === 'true'){
            var dt_edit = {};
            dt_edit['src'] = {};
            dt_edit['show'] = {};
            var dt_new = {};
            dt_new['pks'] = {};
            dt_new['src'] = {};
            dt_new['show'] = {};
            var pkslist = dt['pks'];
            if(pkslist === undefined) pkslist = [];
            var pks = {};
            for(var i in pkslist){
                pks[pkslist[i]['md5']] = pkslist[i]['pk'];
            }
            var src = dt['src'];
            var show = dt['show'];
            var tree_parent = tree['parent'];
            var pval = "";
            for(var i in src) {
                var pkobj = $(g_tr + '[pkmd5=' + i + ']');
                var pkobjsize = $(g_tr + '[pkmd5=' + i + ']').size();
                var pkparent = pkobj.attr("pkparent");
                var parentid;
                var pk = pkobj.attr("pk");
                if(pk === undefined){
                    pk = pks[i];
                }
                if(pkparent === undefined || pkparent === "") {
                    parentid = tree['value'];
                }else{
                    parentid = $(g_tr + '[pkmd5=' + pkparent + ']').attr('vchild');
                }
                if(src[i][tree_parent] === parentid && pkobjsize > 0){
                    dt_edit['src'][i] = src[i];
                    dt_edit['show'][i] = show[i];
                }else{
                    dt_new['pks'][i] = pk;
                    dt_new['src'][i] = src[i];
                    dt_new['show'][i] = show[i];
                    if (!is_copy && data !== undefined) {
                        for (var ii in data) {
                            tree_remove(data[ii]);
                        }
                        tree_remove(i);
                    }
                }
                if(pval === "") pval = src[i][tree_parent];
            }
            Object.keys(dt_edit['src']).length > 0 && set_html_value(dt_edit);
            if(Object.keys(dt_new['src']).length > 0){
                if(pval === undefined || pval === ""){
                    set_html_affer(dt_new);
                }else {
                    var parentobj = $(g_tr + '[vchild="' + pval + '"]');
                    set_html_affer(dt_new, parentobj);
                    set_square(parentobj);
                }
            }
        }else {
            set_html_value(dt);
            layer.msg(result.msg, {icon: 1, time: 1000});
        }
    }

    // 根据pkmd5重新获取数据
    set_html_handle = function(pkmd5) {
        if (pkmd5 === undefined && typeof pkmd5 !== 'string') {
            return;
        }

        var pk_obj = $(g_tr + "[pkmd5='" + pkmd5 + "']");
        if (pk_obj.length <= 0) {
            return;
        }

        var pk = pk_obj.attr('pk');

        var posturl = base_url + ".edit?pk=" + encodeURI(JSON.stringify([pk])) + "&" + g_up.getParamString(g_params, ['pk']);

        $.post(posturl, {
            '_#get#_': 'yes'
        }, function (result) {
            if (result.code === 1) {
                set_result_handle(result);
            }
        });
    }

    //设置列表数据
    set_html_value = function(obj){
        var src = obj.src;
        var show = obj.show;
        var replace = obj.replace;

        if (replace !== undefined) {
            for (var i in replace) {
                var iv = replace[i];
                var iv_obj = $(g_tr + "[pkmd5='" + iv.md5 + "']");
                iv_obj.attr('pk', iv.value);
                iv_obj.attr('pkmd5', i);
                iv_obj.attr("data-posturl", base_url + ".edit?pk=" + encodeURI(JSON.stringify([iv.value])) + "&" + g_up.getParamString(g_params, ['pk']));
            }
        }

        for(var md5 in show){
            var objv = show[md5];
            $(g_tr + "[pkmd5='" + md5 + "'] td").each(function () {
                var _this = $(this);
                var key = _this.attr("key");
                if(objv[key] !== undefined) {
                    var type = $(".layui-table-header th[data-field='" + key + "']").attr("data-type");
                    if(type === undefined || type === '') {
                        var title = $().html_decode(objv[key]).trim();
                        // var smk = $.trim(src[md5][key]);
                        // if(smk !== title && smk !== ''){
                        //     if(title == ''){
                        //         title = smk;
                        //     }else {
                        //         title = smk + "\n" + title;
                        //     }
                        // }
                        var objv_html = objv[key];
                        if (typeof objv_html === 'string') {
                            objv_html = objv_html.replace(/<script/gi, "<span").replace(/<\/script/gi, "</span");
                        }
                        _this.find(".layui-table-cell").attr("value", src[md5][key]).attr("title", title).find(".value").html(objv_html);
                    }else if(type === 'status'){
                        var name = key + '[' + md5 + ']';
                        var data = {};
                        var status = src[md5][key];
                        if(status === '1'){
                            status = 1;
                        }else if(status === '0'){
                            status = 0;
                        }
                        data[name] = status;
                        layui.form.val('table-body', data);
                    }
                }
            });
        }
    }

    /**
     * 滚动时删除弹出提示框S
     */
    $(".layui-table-body").scroll(function () {
        $(".layui-layer-tips").remove();
    });

    function get_field() {
        var fds = [];
        for(var i in g_fd){
            if(g_fd[i]['hidden'] === undefined || g_fd[i]['hidden'] === false) fds.push(i);
        }
        return fds;
    }

    function tips_error(info, obj) {
        var msg;
        if (typeof info === 'object') {
            msg = info.msg;
        } else {
            msg = info;
        }
        layer.tips(msg, obj,{
            tips: [1, '#F80']
        });
    }

    function alert_error(info, is_box) {
        var msg;
        if (typeof info === 'object') {
            msg = info.msg;
        } else {
            msg = info;
        }
        if (is_box === true) {
            dbclick_close(layer.alert(
                msg,
                {
                    title: '数据格式错误',
                    area: [handle_width + 'px', handle_height + 'px']}
                )
            );
        } else {
            dbclick_close(layer.alert(msg, {icon: 2, title: '错误'}));
        }
    }

    /**
     * 触发展开按钮
     * @param obj
     * @param vchild
     * @param pkmd5
     */
    function set_fa_click(obj, vchild, pkmd5) {
        var _tfso = $(obj);
        var _tfso_p = _tfso.parent().parent().parent();
        var _tfso_isopen = _tfso.attr("isopen");
        if(_tfso_isopen === undefined){
            _tfso_isopen = 'false';
            if(typeof tree_value_alls === 'object' && typeof tree_value_alls[vchild] === 'object'){
                var tva = tree_value_alls[vchild];
                set_html_affer(tva, _tfso_p);
            }else {
                $.ajax({
                    type: "post",
                    url: window.location.href,
                    data: {
                        value: vchild
                    },
                    async: false,
                    success: function (result) {
                        var code = result.code;
                        if (code === 1) {
                            var data = result.data;
                            set_html_affer(data, _tfso_p);
                        } else {
                            alert_error(result);
                        }
                    }
                });
            }
        }

        if(_tfso_isopen === 'true'){
            _tfso.attr("isopen", "false");
            $(obj).addClass("fa-plus-square-o").removeClass("fa-minus-square-o");
            tree_close(pkmd5);
        }else{
            _tfso.attr("isopen", "true");
            $(obj).addClass("fa-minus-square-o").removeClass("fa-plus-square-o");
            tree_open(pkmd5);
        }
        $(this).resize();
    }

    //设置展开状态
    set_square = function(obj) {
        var ltc = obj.find("td[key='" + oper_title + "'] .layui-table-cell");
        var pkmd5 = obj.attr('pkmd5');
        var level = parseInt(obj.attr("level"));
        var mleft = (level * g_tree_width) + "px";
        var fa = ltc.find(".js_tree_fa");
        var child = ltc.find(".child");
        var vobj = ltc.find(".value");
        var childsize = parseInt(obj.attr("child"));
        if(child.size() <= 0){
            vobj.after('<span class="child"></span>');
            child = ltc.find(".child");
        }

        if(fa.size() <= 0){
            vobj.before('<span class="js_tree_fa fa fa-plus-square-o" style="margin-left: ' + mleft + '"></span>');
            fa = ltc.find(".js_tree_fa");
            fa.click(function () {
                set_fa_click(this, obj.attr("vchild"), pkmd5);
            });
        }

        var isopen = fa.attr("isopen");

        if(pkmd5 !== undefined && pkmd5 !== ""){
            var pkparent = $(g_tr + '[pkparent="' + pkmd5 + '"]');
            var pkparentsize = pkparent.size();
            if(isopen === undefined){
                pkparent.remove();
                childsize = childsize + pkparentsize;
            }else if(isopen === 'false'){
                pkparent.css("display", "none");
                childsize = pkparentsize;
            }else{
                childsize = pkparentsize;
            }
            obj.attr("child", childsize);
        }

        if(childsize > 0){
            child.css("display", "").html('(' + childsize + ')');
            fa.css("display", "");
            vobj.css("margin-left", "0px");
        }else{
            child.css("display", "none").html('(0)');
            fa.css("display", "none");
            vobj.css("margin-left", mleft);
        }
    }

    //动态列表设置
    set_html_affer = function(data, obj) {
        var show = data.show;
        var src = data.src;
        var pks = data.pks;
        var htmlstr = "";
        var pkmd5list = [];
        var pkmd5, level;
        if(obj === undefined) {
            pkmd5 = 0;
            level = -1;
        }else{
            pkmd5 = obj.attr("pkmd5");
            if(pkmd5 === undefined){
                pkmd5 = 0;
                level = -1;
            }else{
                level = parseInt(obj.attr("level"));
            }
        }
        var sort = data.sort;
        if(sort === undefined){
            sort = [];
            for(var i in pks){
                sort.push(i);
            }
        }
        var frist_parent = undefined;
        for(var si in sort){
            var i = sort[si];
            pkmd5list.push(i);
            if (frist_parent === undefined) {
                frist_parent = src[i][tree['parent']] + '';
            }
            htmlstr += '<tr pk=\'' + pks[i] + '\' pkmd5="' + i + '"  child="' + src[i]['@child'] + '" vchild="' + src[i][tree['child']] + '" pkparent="' + pkmd5 + '" level="' + (level + 1) + '" >';
            for(var j in g_field){
                var sval = src[i][g_field[j]];
                var vval = show[i][g_field[j]];
                if(sval === undefined){
                    sval = "";
                }
                if(vval === undefined){
                    vval = "";
                }
                htmlstr += '<td>';
                htmlstr += '<s>' + sval + '</s>';
                htmlstr += '<v>' + vval + '</v>';
                htmlstr += '</td>';
            }
            htmlstr += '</tr>';
        }
        if(obj === undefined || obj.length <= 0) {
            if (frist_parent === tree_value) {
                if($(g_tr).length <= 0){
                    $(g_tbody).html(htmlstr);
                } else {
                    $(g_tr + ':first-child').before(htmlstr);
                }
            }
        }else{
            obj.after(htmlstr);
        }
        add_table_tr(pkmd5list);
        set_html_value(data);
        for(var i in pks){
            operclick($("tr[pkmd5='" + i + "']"));
        }
        resizeset();
    }

    function set_html_affer_all(data){
        $(".js_tree_fa").each(function () {
            var _this = $(this);
            if(_this.attr('isopen') === undefined){
                var obj = $(this).parent().parent().parent();
                var pkmd5 = obj.attr('pkmd5');
                if(pkmd5 !== undefined && data[pkmd5] !== undefined){
                    _this.addClass("fa-minus-square-o").removeClass("fa-plus-square-o");
                    set_html_affer(data[pkmd5], obj);
                }
                _this.attr('isopen', 'true');
            }
        });
        var is_next = false;
        $(".js_tree_fa").each(function () {
            var _this = $(this);
            if(_this.attr('isopen') === undefined){
                is_next = true;
                return false;
            }
        });
        if(is_next){
            set_html_affer_all(data);
        }
    }

    //树状展开
    function tree_open(pkmd5){
        $(g_tbody + ">tr[pkparent='" + pkmd5 + "']").each(function () {
            var _this = $(this);
            _this.css("display", "");
            if(parseInt(_this.attr("child")) > 0) {
                var isopen = _this.find(".layui-table-cell .js_tree_fa").attr("isopen");
                var tpkmd5 = _this.attr("pkmd5");
                if(isopen === undefined || isopen === 'false') {
                    tree_close(tpkmd5);
                }else{
                    tree_open(tpkmd5);
                }
            }
        });
    }

    //树状关闭
    function tree_close(pkmd5){
        $(g_tbody + ">tr[pkparent='" + pkmd5 + "']").each(function () {
            var _this = $(this);
            _this.css("display", "none");
            if(parseInt(_this.attr("child")) > 0) {
                tree_close($(this).attr("pkmd5"));
            }
        });
    }

    //树状移除所有
    function tree_remove_all(pkmd5){
        $(g_tr + "[pkmd5='" + pkmd5 + "']").each(function () {
            $(g_tr + "[pkparent='" + pkmd5 + "']").each(function () {
                var tpkmd5 = $(this).attr('pkmd5');
                tree_remove_all(tpkmd5);
            });
        }).remove();
    }

    //树状移除所有
    function tree_remove(pkmd5){
        var pp = $(g_tr + "[pkmd5='" + pkmd5 + "']").attr("pkparent");
        tree_remove_all(pkmd5);
        var size = $(g_tr + "[pkparent='" + pp + "']").size();
        var ltc = $(g_tr + "[pkmd5='" + pp + "'] td[key='" + oper_title + "'] .layui-table-cell");
        ltc.find(".child").html("(" + size + ")");
        if(size <= 0) {
            var fa = ltc.find(".js_tree_fa");
            fa.css("display", "none");
            ltc.find(".child").css("display", "none");
            ltc.find(".value").css("margin-left", fa.css("margin-left"));
        }
    }

    //alt键进行编辑和退出编辑切换
    $(document).keyup(function (event) {
        var code = event.keyCode;
        if(code === 18) {
            if (!is_alt) {
                if(is_alt_td){
                    is_alt_td = false;
                }else{
                    if(alt_td === undefined){
                        alt_td = $(".layui-table-body td[data-edit='true']:eq(0) .layui-table-cell");
                    }
                    if(is_alt_keydown && $(".layui-layer-iframe").size() <= 0 && $(".layui-layer-dialog").size() <= 0) alt_td.trigger("click");
                }
            }
            is_alt_keydown = false;
        }
    }).keydown(function (event) {
        var code = event.keyCode;
        if(code === 18) {
            is_alt_keydown = true;
        }
    });

    function add_table_tr(tr_md5s) {
        if(tr_md5s === undefined) tr_md5s = [];
        var tr_md5s_len = 0;
        if(tr_md5s !== undefined && tr_md5s instanceof Array) tr_md5s_len = tr_md5s.length;
        var td_first_str = "";
        if(tr_md5s_len <= 0){
            td_first_str = g_tr + ">td:nth-child(1)";
        }else{
            var td_firsts = [];
            for(var i in tr_md5s){
                td_firsts.push(g_tr + "[pkmd5='" + tr_md5s[i] + "']>td:nth-child(1)");
            }
            td_first_str = td_firsts.join(",");
        }
        var td_first = $(td_first_str);

        td_first.parent().each(function () {
            $(this).attr("data-posturl", base_url + ".edit?pk=" + encodeURI(JSON.stringify([$(this).attr("pk")])) + "&" + g_up.getParamString(g_params, ['pk']));
        });

        //th初始化
        $(".layui-table thead tr th").each(function (item) {
            var _this = $(this);
            var sort = _this.attr("lay-sort");
            var dfield = _this.attr("data-field");
            var type = _this.attr("data-type");
            var th_is_set = _this.attr("is-set");
            var tval = _this.html();
            var td_all_str = g_tr + ">td:nth-child(" + (item + 1) + ")";
            var td_select_str = "";
            var th_click = {}, th_click_attr = _this.attr("data-click");
            if(th_click_attr !== undefined && th_click_attr !== "") th_click = eval("(" + th_click_attr + ")");
            var th_click_len = Object.keys(th_click).length;

            if(tr_md5s_len <= 0){
                td_select_str = g_tr + ">td:nth-child(" + (item + 1) + ")";
            }else{
                var tmptr_md5s = [];
                for(var i in tr_md5s){
                    tmptr_md5s.push(g_tr + "[pkmd5='" + tr_md5s[i] + "']>td:nth-child(" + (item + 1) + ")");
                }
                td_select_str = tmptr_md5s.join(",");
            }

            if(g_fd !== undefined && g_fd[dfield] !== undefined && g_fd[dfield]['align'] !== undefined){
                var align = g_fd[dfield]['align'];
                if(align !== ""){
                    var alstr = "td_align_" + align;
                    _this.addClass(alstr);
                    $(td_select_str).addClass(alstr);
                }
            }

            if (type === undefined || type === '' || type === 'status') {   //数据类型未设置则是数据库数据
                var edit = _this.attr("data-edit");
                var is_edit = false;
                if (edit !== undefined && edit === 'true') {
                    tval = '<i><div class="layui-icon" style="color:#F88; float: left; margin-right: -20px;"></div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</i>' + tval;
                    $(td_select_str).attr("data-edit", "true");
                    is_edit = true;
                }

                if (sort === undefined) {
                    if(th_is_set === undefined){
                        _this.html('<div class="layui-table-cell">' + tval + '</div>');
                    }
                } else {
                    if(th_is_set === undefined) {
                        //th设置
                        var laysort = "";
                        if (sort === 'asc' || sort === 'desc') {
                            laysort = 'lay-sort="' + sort + '"';
                        }
                        var html = '<div class="layui-table-cell"><span>' + tval + '</span><span class="layui-table-sort layui-inline" ' + laysort + '>';

                        if (sort === 'asc') {
                            html += '<i class="layui-edge layui-table-sort-asc" data-remove=\'["_sort", "_order"]\'></i>';
                        } else {
                            html += '<i class="layui-edge layui-table-sort-asc" data-set=\'{"_sort":"' + dfield + '", "_order":"asc"}\'></i>';
                        }
                        if (sort === 'desc') {
                            html += '<i class="layui-edge layui-table-sort-desc" data-remove=\'["_sort", "_order"]\'></i>';
                        } else {
                            html += '<i class="layui-edge layui-table-sort-desc" data-set=\'{"_sort":"' + dfield + '", "_order":"desc"}\'></i>';
                        }
                        html += '</span></div>';
                        _this.html(html);
                    }
                }

                //固定宽度设置
                if(th_is_set === undefined) {
                    var fixedwidth = _this.attr("width");
                    if(!isNaN(fixedwidth)){
                        _this.find(".layui-table-cell").width(fixedwidth);
                    }
                }

                var lay_text = _this.attr("data-text");
                if(lay_text === undefined) lay_text = "";

                var treestr = "";
                if(dfield === oper_title && is_tree === 'true'){
                    treestr = '<span class="js_tree_fa fa fa-plus-square-o"></span>';
                }

                //td设置
                $(td_select_str).each(function () {
                    var _tt = $(this);
                    var _tt_is_set = _tt.attr("is-set");
                    var _tt_parent = _tt.parent("tr");
                    var pkmd5 = _tt_parent.attr("pkmd5");
                    var child = parseInt(_tt_parent.attr("child"));
                    var level = parseInt(_tt_parent.attr("level"));
                    var vchild = _tt_parent.attr("vchild");
                    var tabpx = level * g_tree_width;
                    if(_tt_is_set !== undefined) return true;
                    _tt.attr("key", dfield);
                    if(th_click_len > 0) _tt.addClass("js_oper_click");

                    var tv = _tt.find(">v").html();
                    if (tv === undefined){
                        tv = "";
                    } else {
                        tv = $().show_html(tv);
                    }
                    var ts = _tt.find(">s").html();
                    if (ts === undefined) {
                        ts = "";
                    } else {
                        ts = $().html_decode(ts).replace(/&/g, "&amp;").replace(/"/g, '&quot;');
                    }
                    var tv_encode = $().html_decode(tv).replace(/"/g, '&quot;');
                    var t_tit = "";
                    ts = $.trim(ts);
                    tv_encode = $.trim(tv_encode);
                    if(ts === tv_encode){
                        t_tit = ts;
                    }else if(ts === ''){
                        t_tit = tv_encode;
                    }else if(tv_encode === ''){
                        t_tit = ts;
                    }else{
                        t_tit = ts + '\n' + tv_encode;
                    }
                    if(type === 'status'){
                        var htmlstr = '<div class="layui-table-cell layui-form"><input type="checkbox" name="' + dfield + '[' + pkmd5 + ']" ';
                        if(ts === '1'){
                            htmlstr += 'checked="" ';
                        }
                        htmlstr += 'lay-skin="switch" lay-text="' + lay_text + '" lay-filter="status"></div>';
                        _tt.html(htmlstr);
                    }else if(child > 0){
                        _tt.html('<div class="layui-table-cell" value="' + ts + '" title="' + t_tit + '">' + treestr + '<span class="value">' + tv + '</span></div>');
                        _tt.find(".js_tree_fa").parent().append("<span class='child'>(" + child + ")</span>");
                        _tt.find(".js_tree_fa").css("margin-left", tabpx +"px");
                        _tt.find(".js_tree_fa").click(function () {
                            set_fa_click(this, vchild, pkmd5);
                        });
                    }else{
                        _tt.html('<div class="layui-table-cell" value="' + ts + '" title="' + t_tit + '"><span class="value">' + tv + '</span></div>');
                        if(treestr !== ""){
                            _tt.find(".value").css("margin-left", tabpx +"px");
                        }
                    }
                    _tt.attr("is-set", "yes");
                    if (is_edit) {
                        _tt.find('.layui-table-cell').attr('title', tv_encode);
                        _tt.click(function () {
                            var _t = $(this);
                            alt_td = _t;
                            var _posturl = _t.parent().attr("data-posturl");
                            var flag = _t.attr("data-flag");
                            if (flag === undefined || flag === 'false') {
                                var ltc = _t.find(".layui-table-cell");
                                var v = ltc.attr("value");
                                if (v === undefined || v === null) {
                                    v = '';
                                    ltc.attr("value", "")
                                }
                                _t.append('<input class="layui-input layui-table-edit">');
                                _t.attr("data-flag", "true");
                                var edt = _t.find(".layui-table-edit");
                                edt.focus().val(v).blur(function () {
                                    var _tv = $(this).val();
                                    if (ltc.attr("value") === _tv) {
                                        edt.remove();
                                        _t.attr("data-flag", "false");
                                    } else {
                                        var data = {};
                                        data[dfield] = _tv;
                                        $.post(_posturl, data, function (result) {
                                            edt.remove();
                                            if (result.code === 1) {
                                                set_html_value(result.data);
                                                var retval = result.data.src[_tt.parent("tr").attr("pkmd5")][_tt.attr("key")] + '';
                                                if(retval !== _tv){
                                                    tips_error("格式有误，数据已调整！", _tt);
                                                }
                                            } else {
                                                tips_error(result, _tt);
                                            }
                                            _t.attr("data-flag", "false");
                                            return result;
                                        });
                                    }
                                }).keydown(function (event) {
                                    var code = event.keyCode;
                                    //18:alt为退出  9:tab    13:回车
                                    if (code === 18 || code === 9 || code === 13) { //esc退出按钮不做任何操作并还原
                                        var _tv = $(this).val();
                                        if (code === 9 || code === 13) {
                                            var nxt, nxttr;
                                            if (is_shift) {
                                                nxt = _t.prev("td");
                                            } else {
                                                nxt = _t.next("td");
                                            }
                                            var i = 0;
                                            if (code === 9) {
                                                while (nxt.html() !== undefined) {
                                                    var nxtv = nxt.attr("data-edit");
                                                    var display = nxt.parent("tr").css('display');
                                                    if (nxtv !== undefined && nxtv === "true" && display !== "none") {
                                                        i++;
                                                        break;
                                                    }

                                                    if (is_shift) {
                                                        nxt = nxt.prev("td");
                                                    } else {
                                                        nxt = nxt.next("td");
                                                    }
                                                }
                                                if (i <= 0) {
                                                    if (is_shift) {
                                                        nxt = _t.parent("tr").prev().find(">td:last-child");
                                                    } else {
                                                        nxt = _t.parent("tr").next().find(">td:first-child");
                                                    }
                                                    while (true) {
                                                        if(nxt.length <= 0) break;
                                                        var nxtwhile = nxt;
                                                        while (nxtwhile.html() !== undefined) {
                                                            var nxtv = nxtwhile.attr("data-edit");
                                                            var display = nxtwhile.parent("tr").css('display');
                                                            if (nxtv !== undefined && nxtv === "true" && display !== "none") {
                                                                i++;
                                                                break;
                                                            }
                                                            if (is_shift) {
                                                                nxtwhile = nxtwhile.prev("td");
                                                            } else {
                                                                nxtwhile = nxtwhile.next("td");
                                                            }
                                                        }
                                                        if(i > 0){
                                                            nxt = nxtwhile;
                                                            break;
                                                        }

                                                        if (is_shift) {
                                                            nxt = nxt.parent("tr").prev().find(">td:last-child");
                                                        } else {
                                                            nxt = nxt.parent("tr").next().find(">td:first-child");
                                                        }
                                                        //alert(nxt.html());
                                                    }

                                                }

                                            } else {
                                                if (is_shift) {
                                                    nxttr = _t.parent("tr").prev();
                                                } else {
                                                    nxttr = _t.parent("tr").next();
                                                }
                                                nxt = nxttr.find(">td[key='" + dfield + "']");
                                                if (nxt.size() > 0 && nxttr.css("display") !== 'none') {
                                                    i++;
                                                }else{
                                                    var nxtwhile = _t.parent("tr");
                                                    var nxtwhiletd;
                                                    while (true) {
                                                        if (is_shift) {
                                                            nxtwhile = nxtwhile.prev();
                                                        }else{
                                                            nxtwhile = nxtwhile.next();
                                                        }
                                                        nxtwhiletd = nxtwhile.find("td");
                                                        if(nxtwhiletd.length <= 0) break;
                                                        var tnxt = nxtwhile.find(">td[key='" + dfield + "']");
                                                        if(nxtwhile.css('display') === 'none'){
                                                            nxt = tnxt;
                                                            continue;
                                                        }
                                                        if(tnxt.length > 0){
                                                            i ++;
                                                            nxt = tnxt;
                                                            break;
                                                        }
                                                    }
                                                }
                                            }
                                            if (i > 0) {
                                                if (ltc.attr("value") === _tv) {
                                                    edt.remove();
                                                    _t.attr("data-flag", "false");
                                                    nxt.trigger("click");
                                                } else {
                                                    var data = {};
                                                    data[dfield] = _tv;
                                                    $.post(_posturl, data, function (result) {
                                                        edt.remove();
                                                        if (result.code === 1) {
                                                            set_html_value(result.data);
                                                            var retvalobj = result.data.src[_tt.parent("tr").attr("pkmd5")];
                                                            if(retvalobj === undefined){
                                                                tips_error("格式错误转化", _tt);
                                                            }else {
                                                                var retval = retvalobj[_tt.attr("key")];
                                                                if (retval === undefined || retval + "" !== _tv + "") {
                                                                    tips_error("格式错误转化", _tt);
                                                                }
                                                            }
                                                        } else {
                                                            tips_error(result, _tt);
                                                        }
                                                        _t.attr("data-flag", "false");
                                                        nxt.trigger("click");
                                                        return result;
                                                    });
                                                }
                                            }
                                            return false;
                                        } else {
                                            edt.remove();
                                            _t.attr("data-flag", "false");
                                            is_alt_td = true;
                                            is_alt = false;
                                        }
                                    }
                                    if (code === 16) {
                                        is_shift = true;
                                    }
                                }).keyup(function (event) {
                                    is_alt = false;
                                    if (event.keyCode === 16) {
                                        is_shift = false;
                                    }
                                });
                            }
                        });
                    }else if(_tt.attr("class") === undefined && type !== 'status'){
                        _tt.mouseover(function () {
                            var _this = $(this);
                            var is_on = _this.attr("is-on");
                            var ltc = _this.find(".layui-table-cell")[0];
                            if((is_on === undefined || is_on === 'no') && ltc.scrollWidth > ltc.offsetWidth) {
                                _this.attr("is-on", "yes");
                                _tt.append('<div class="layui-table-grid-down"><i class="layui-icon layui-icon-down"></i></div>');
                                _tt.find(".layui-table-grid-down").click(function () {
                                    $(".layui-layer-tips").remove();
                                    var _tt_l = _tt.offset().left;
                                    var _tt_t = _tt.offset().top;
                                    var _tt_w = _tt.width();
                                    var _tt_h = _tt.parent().height();
                                    var def_w = $('body').width() / 2;
                                    var body_w = body.width();
                                    var body_h = body.height();
                                    var _self = $(this);
                                    var ctext = _self.parent().find(".layui-table-cell>span.value").html();
                                    if(def_w < 300) def_w = 300;
                                    _self.attr("is-click", "true");
                                    body.append('' +
                                        '<div class="layui-layer layui-layer-tips layui-table-tips" style="z-index: 9999; position: absolute; max-width: ' + def_w + 'px; top: ' + (_tt_t - 1) + 'px;">' +
                                        '<div id="" class="layui-layer-content copy" style="position: absolute;"><div class="layui-table-tips-main">复制</div></div><div class="layui-layer-content" style="width: 100%"><div class="layui-table-tips-main"><pre>' +
                                        ctext +
                                        '</pre></div>' +
                                        '<i class="layui-icon layui-table-tips-c layui-icon-close"></i>' +
                                        '</div>' +
                                        '<span class="layui-layer-setwin"></span>' +
                                        '</div>');
                                    var ltt_h = $(".layui-table-tips").height();
                                    var ltt_w = $(".layui-table-tips").width();

                                    var add_w = _tt_l + _tt_w - body_w + 5;
                                    if(_tt_l > ltt_w && _tt_l + ltt_w > body_w){
                                        _tt_l = _tt_l - ltt_w + _tt_w + 2;
                                    }
                                    if(add_w > 0){
                                        _tt_l = _tt_l - add_w;
                                    }
                                    $(".layui-table-tips").css("left", _tt_l + "px");
                                    if(_tt_t + ltt_h > body_h){
                                        var top = _tt_t - ltt_h + _tt_h + 1;
                                        $(".layui-table-tips").css("top", top + "px");
                                    }
                                    $(".layui-icon-close").click(function () {
                                        $(".layui-layer-tips").remove();
                                    });

                                    $(".layui-table-tips").click(function () {
                                        _self.attr("is-click", "true");
                                    });

                                    $(".layui-table-tips .copy").click(function () {
                                        var textarea = $('#WindowCopy');
                                        if(ctext === ''){
                                            layer.msg("未复制，数据为空", {icon: 2});
                                        }else {
                                            textarea.copy_text("复制成功", ctext);
                                        }
                                    });

                                    body.unbind().click(function () {
                                        if(_self.attr("is-click") === 'false') {
                                            $(".layui-layer-tips").remove();
                                        }else{
                                            _self.attr("is-click", "false");
                                        }
                                    });
                                });
                            }
                        }).mouseleave(function () {
                            $(this).attr("is-on", "no");
                            _tt.find(".layui-table-grid-down").remove();
                        });
                    }else{
                        _tt.find('.layui-table-cell').attr('title', tv_encode);
                    }
                });
            } else if (type === 'numbers') { //序号
                if(th_is_set === undefined) {
                    //th设置
                    _this.attr("width", "1%");
                    _this.html('<div class="layui-table-cell laytable-cell-numbers" style="width: 35px;"></div>');
                }

                //td设置
                td_first.each(function () {
                    var __t = $(this);
                    if(__t.parent().find(">td[data-type='" + type + "']").length <= 0) {
                        __t.before('<td data-type="' + type + '"><div class="layui-table-cell laytable-cell-numbers" style="text-align: center"></div></td>');
                    }
                });
                $(td_all_str).find(".laytable-cell-numbers").each(function (i) {
                    $(this).html(i + 1);
                });
            } else if (type === 'checkbox') { //复选框
                if(th_is_set === undefined) {
                    //th设置
                    _this.attr("width", "1%");
                    _this.html('<div class="layui-table-cell laytable-cell-checkbox" style="width: 50px;">' +
                        '<div class="layui-form-checkbox" lay-skin="primary" bool="false"><i class="layui-icon layui-icon-ok"></i></div>' +
                        '</div>');
                }

                //td设置
                td_first.each(function () {
                    var __t = $(this);
                    if(__t.parent().find(">td[data-type='" + type + "']").length <= 0) {
                        __t.before('<td data-type="' + type + '"><div class="layui-table-cell laytable-cell-checkbox">' +
                            '<div class="layui-form-checkbox" lay-skin="primary" bool="false"><i class="layui-icon layui-icon-ok"></i></div>' +
                            '</div></td>');
                    }
                });


                //单个选择
                $(td_select_str).find(".layui-form-checkbox").click(function () {
                    var t = $(this);
                    //单选、全选效果
                    var td_checkbox = $(g_tr + ">td[data-type='checkbox']");
                    var td_checkbox_size = td_checkbox.size();
                    if (t.attr("bool") === "false") {
                        t.addClass("layui-form-checked");
                        t.attr("bool", "true");
                    } else {
                        t.removeClass("layui-form-checked");
                        t.attr("bool", "false");
                    }

                    var bture = td_checkbox.find(".layui-form-checkbox[bool='true']");
                    if (bture.size() === td_checkbox_size) {
                        _this.find(".layui-form-checkbox").attr("bool", "true").addClass("layui-form-checked");
                    } else {
                        _this.find(".layui-form-checkbox").attr("bool", "false").removeClass("layui-form-checked");
                    }
                });

                if(th_is_set === undefined) {
                    //批量选择
                    _this.find(".layui-form-checkbox").click(function () {
                        var t = $(this);
                        //单选、全选效果
                        var td_checkbox = $(g_tr + ">td[data-type='checkbox']");
                        if (t.attr("bool") === "false") {
                            t.addClass("layui-form-checked");
                            t.attr("bool", "true");
                            td_checkbox.find(".layui-form-checkbox").attr("bool", "true").addClass("layui-form-checked");
                        } else {
                            t.removeClass("layui-form-checked");
                            t.attr("bool", "false");
                            td_checkbox.find(".layui-form-checkbox").attr("bool", "false").removeClass("layui-form-checked");
                        }
                    });
                }
            } else if (type === 'oper') { //操作
                if(th_is_set === undefined) {
                    //th设置
                    var tw = _this.attr("width");
                    _this.attr("width", "1%");
                    _this.html('<div class="layui-table-cell" style="width: ' + tw + 'px; text-align: center;">' + tval + '</div>');
                }

                var djson = eval("(" + _this.attr("data-json") + ")");
                //td设置
                td_first.each(function () {
                    var __t = $(this);
                    var __tp = __t.parent();
                    if(__tp.find(">td[data-type='" + type + "']").length <= 0) {
                        var strhtml = '<td data-type="' + type + '"><div class="layui-table-cell" style="text-align: center;">';
                        for(var i in djson){
                            var iobj = djson[i];
                            var iname = "";
                            var objkey = "";
                            var opername = i;
                            if(iobj instanceof Object){
                                if(['add', 'handle', 'delete'].indexOf(i) < 0){
                                    opername = "url";
                                }else{
                                    iobj.url = i;
                                }
                            }
                            if (iobj.type !== undefined && ['isbind', 'unbind'].indexOf(iobj.type) >= 0) {
                                opername = iobj.type;
                            }
                            strhtml += '<button class="layui-btn layui-btn-primary layui-btn-xs js_oper_' + opername + '" lay-oper="" lay-filter="' + opername + '" ';
                            if(iobj instanceof Object){
                                var data_type = 'normal';
                                iname = iobj.name;

                                if (iobj.bind instanceof Object) {
                                    iobj.url = iobj.bind.extends;
                                    data_type = 'bind';
                                } else if (iobj.extends instanceof Object) {
                                    iobj.url = iobj.extends.extends;
                                    data_type = 'extends';
                                }

                                strhtml += 'data-url="' + iobj.url + '" data-click="true" ' + '" data-type="' + data_type + '" ';
                                objkey = iobj.key;
                                if(objkey !== undefined && objkey !== ""){
                                    strhtml += 'data-key="' + iobj.key + '"';
                                }
                                if(iobj.ismax !== undefined){
                                    strhtml += 'data-ismax="' + iobj.ismax + '"';
                                }
                                if(iobj.method !== undefined){
                                    strhtml += 'data-method="' + iobj.method + '"';
                                }
                                if(iobj.width !== undefined){
                                    strhtml += 'data-width="' + iobj.width + '"';
                                }
                                if(iobj.height !== undefined){
                                    strhtml += 'data-height="' + iobj.height + '"';
                                }
                                if(iobj.confirm !== undefined){
                                    strhtml += 'data-confirm="' + iobj.confirm + '"';
                                }
                            }else{
                                iname = iobj;
                            }
                            strhtml += '>' + iname + '</button>';
                        }
                        strhtml += '</div></td>';
                        __tp.append(strhtml);
                    }
                });
            }
            if(th_is_set === undefined) _this.attr("is-set", "yes");
        });
    }

    add_table_tr();

    layui.use(['form'], function() {
        //监听指定开关
        var form = layui.form;
        form.on('switch(status)', function () {
            var td = $(this).parent().parent();
            var tr = td.parent();
            var posturl = tr.attr("data-posturl");
            var key = td.attr("key");
            var value = this.checked ? 1 : 0;

            var data = {};
            data[key] = value;
            $.post(posturl, data, function (result) {
                if (result.code === 1) {
                    set_html_value(result.data);
                } else {
                    tips_error(result, td);
                }
                return result;
            });
        });
    });


    var layui_change_tout = "";

    function layui_change_getheight(obj) {
        return (obj.size() * (38 + 15)) + obj.find('textarea').size() * 62 + obj.parent().find("fieldset").size() * 58;
    }

    var layui_change_i = 0;
    var layui_change_time = 100;
    var layui_change_fixed;
    function layui_change() {
        clearTimeout(layui_change_tout);
        var lli = window["layui-layer-iframe" + g_tmp_layout_id];
        var is_loop = false;
        if(lli === undefined){
            layer.close(g_tmp_layout_load);
        } else {
            if (g_tmp_layout_loadok) {
                var llm = $(".layui-layer-max").parent().parent();
                if ($(".layui-layer-loading").size() > 0) {
                    layer.close(g_tmp_layout_load);
                    is_loop = true;
                } else if (lli.$ === undefined) {
                    layui_change_i += layui_change_time;
                    if(layui_change_i > 500){
                        llm.css('display', '');
                    }else {
                        is_loop = true;
                    }
                } else {
                    var hf;
                    if(layui_change_fixed === undefined){
                        hf = handle_fixed;
                    }else{
                        hf = layui_change_fixed;
                    }
                    if(hf !== 'true') {
                        var sobj;
                        var lti_obj = lli.$(".layui-tab-item");
                        var _h_height = 0;
                        var _max_height = 0;
                        if (lti_obj.size() > 1) {
                            lti_obj.each(function () {
                                var tlfi = $(this).find('.layui-form-item');
                                var mheight = layui_change_getheight(tlfi);
                                if(mheight > _max_height){
                                    sobj = tlfi;
                                    _max_height = mheight;
                                }
                            });
                            _h_height = 40;
                        } else {
                            sobj = lli.$('.layui-form-item');
                            _max_height = layui_change_getheight(sobj);
                        }
                        if (_max_height > 0) {
                            if (lli.$('select').size() > 0 && _max_height <= 200) {
                                _max_height = 200;
                            }
                            var height = _max_height - 15;
                            if (lli.$('.js_btn_right').size() > 0) {
                                height += 90;
                            }
                            height += 40 + _h_height;

                            if (height < handle_height) {
                                llm.find(".layui-layer-content iframe").height(height);
                                if ($("#layui-layer" + g_tmp_layout_id + " .layui-layer-btn").size() > 0) {
                                    llm.height(height + 109);
                                } else {
                                    llm.height(height);
                                }
                            }
                            $(window).resize();
                        }
                    }
                    llm.css('display', '');
                    g_tmp_layout_loadok = false;
                }
            } else {
                is_loop = true;
            }
        }

        if(is_loop){
            layui_change_tout = setTimeout(layui_change, layui_change_time);
        }
    }

    function dbclick_closes(layui_id, fixed) {
        g_tmp_layout_id = layui_id;
        $(".layui-layer-max").parent().parent().css('display', 'none');
        g_tmp_layout_load = layer.load(1, {
            shade: [0.1, '#fff'] //0.1透明度的白色背景
        });
        layui_change_i = 0;
        if(fixed === true || fixed === 'true'){
            layui_change_fixed = 'true';
        }else{
            layui_change_fixed = undefined;
        }
        layui_change();
        dbclick_close(layui_id);
    }

    //删除操作
    function layui_delete(posturl, title, checklist, config, md5list){
        dbclick_closes(layer.confirm(title, {
            shade: 0.1
        }, function(index){
            $.post(posturl, {
                data: checklist
            }, function (result) {
                if (result.code === 1) {
                    if(is_tree === 'true') {
                        for(var i in md5list){
                            tree_remove(md5list[i]);
                        }

                        layer.msg(result.msg, {icon:1, time:1500}, function () {
                            layer.close(index);
                        });
                    }else{
                        t_urlparam().alert(result.msg).run();
                    }
                } else {
                    alert_error(result);
                }
            });
        }));
    }

    //清空所有数据操作
    function layui_clear(posturl){
        dbclick_closes(layer.confirm('<span style="color:#F00">确定清空所有数据？</span>', {
            shade: 0.1
        }, function(index){
            $.post(posturl, {
                bool: true
            }, function (result) {
                if (result.code === 1) {
                    t_urlparam().alert(result.msg).run();
                } else {
                    alert_error(result);
                }
            });
        }));
    }

    var leuout = "";
    var leuobj;
    function layui_edit_url_tout() {
        clearTimeout(leuout);
        if($(".layui-layer-loading").size() > 0){
            leuout = setTimeout(layui_edit_url_tout, 50);
        }else{
            if(leuobj !== undefined){
                leuobj.show();
                var llif = leuobj.find("iframe");
                var height = leuobj.height() - leuobj.find(".layui-layer-title").height() - 1;
                llif.css("height", height + "px");
            }
        }
    }

    //编辑操作
    function layui_edit(posturl, title, type, config, data){
        if(type === undefined) type = 'edit';
        var layeropen;
        var sort = $.fn.urlparam().get("_sort");
        var order = $.fn.urlparam().get("_order");
        if(sort !== "" && order !== ""){
            posturl = $.fn.urlparam().setUrl(posturl).set("_sort", sort).set("_order", order).getUrl();
        }
        
        if (type === 'url') {
            if (config['method'] !== undefined) {
                type = config['method'];
            }
        }
        
        if(type === 'view'){
            if(window.event.ctrlKey){
                window.open(posturl, '_blank');
                return true;
            }else {
                layeropen = layer.open({
                    type: 2
                    , title: title
                    , area: [handle_width + 'px', handle_height + 'px']
                    , shade: 0.1
                    , maxmin: true
                    , offset: 'auto'
                    , content: posturl
                    , success: function () {
                        g_tmp_layout_loadok = true;
                    }
                    , btn: ['关闭']
                });
            }
        } else if(type === 'url') {
            if(window.event.ctrlKey){
                window.open(posturl, '_blank');
                return true;
            }else {
                layeropen = layer.open({
                    type: 2
                    , title: title
                    , area: [handle_width + 'px', handle_height + 'px']
                    , shade: 0.1
                    , maxmin: true
                    , offset: 'auto'
                    , content: posturl
                    , success: function (obj, index) {
                        g_tmp_layout_loadok = true;
                        leuobj = $("#layui-layer" + index);
                        leuobj.hide();
                        layui_edit_url_tout();
                    }
                    , yes: function (index) {
                        layer.close(index);
                    }
                });
            }
        }else if(type === 'confirm'){
            layeropen = layer.confirm(title, {
                shade: 0.1
            }, function(){
                $.post(posturl, config, function (result) {
                    if (result.code === 1) {
                        t_urlparam().alert(result.msg).run();
                    } else {
                        alert_error(result);
                    }
                });
            });
        } else {
            var t_width = handle_width;
            var is_copy = false;
            if (type == 'copy') {
                t_width = 600;
                is_copy = true;
            }

            var btn = ['保存', '取消', '还原'];
            var type_src = type;
            if (type === 'bind' || type === 'extends' || type === 'copy') {
                type = 'edit';
                btn = ['确定', '取消'];
            }

            g_tmp_layout_load = layer.load(1, {
                shade: [0.1, '#fff'] //0.1透明度的白色背景
            });
            layeropen = layer.open({
                type: 2
                , title: title
                , area: [t_width + 'px', handle_height + 'px']
                , shade: 0.1
                , maxmin: true
                , offset: 'auto'
                , content: posturl
                , btn: btn
                , show_data: function (result, index) {
                    if(typeof result === 'string'){
                        alert_error(result, true);
                        return;
                    }else if (result === false) {
                        return;
                    }
                    if (result.code === 1) {
                        layer.close(index);
                        var dt = result.data;
                        if(is_tree === 'true'){
                            set_result_handle(result, is_copy, data);
                        }else {
                            if (type === 'edit') {
                                set_result_handle(result, is_copy, data);
                            } else if (type === 'add') {
                                t_urlparam().alert(result.msg).run();
                            }
                        }
                    } else {
                        alert_error(result);
                    }
                }
                , success:function(){
                    g_tmp_layout_loadok = true;
                }
                , yes: function (index) {
                    var _this = this;
                    if (type_src === 'extends') {
                        $.post(posturl, {
                            '_#get#_': 'yes'
                        }, function (result) {
                            _this.show_data(result, index);
                        });
                        return;
                    }

                    var result;
                    try{
                        result = window["layui-layer-iframe" + index].submit();
                    }catch (e) {
                        layer.msg('操作无效', {icon: 2});
                        return;
                    }
                    this.show_data(result, index);
                }
                , btn2: function (index) {
                    layer.close(index);
                }
                , btn3: function (index) {
                    try{
                        window["layui-layer-iframe" + index].reset();
                    }catch (e) {

                    }
                    return false;
                }
            });
        }

        dbclick_closes(layeropen);

        var ismax;
        if(handle_ismax === 'true'){
            ismax = true;
        }else{
            ismax = false;
        }
        if(config !== undefined && config.ismax !== undefined){
            if(config.ismax === 'true'){
                ismax = true;
            }else{
                ismax = false;
            }
        }

        //是否弹出时全屏
        if(ismax) {
            layer.full(layeropen);
        }

        $(".layui-layer-max").click(function () {
            $(window).resize();
        });
    }

    //批量获取选择列表
    function postCheckList(type, obj){
        if(type === undefined) type= "";
        var checklist = [];
        var md5list = [];
        $(g_tr + ">td[data-type='checkbox']").each(function () {
            var find = $(this).find(".layui-form-checkbox[bool='true']");
            if(find.size() > 0){
                checklist.push($(this).parent("tr").attr("pk"));
                md5list.push($(this).parent("tr").attr("pkmd5"));
            }
        });

        if(type === 'export'){
            if (md5list.length <= 0) {
                layer.msg('未选择数据', {icon:0});
                return false;
            }
            return md5list.join(",");
        }

        if(type !== 'add' && type !== 'clear') {
            if (checklist.length <= 0) {
                layer.msg('未选择数据', {icon:0});
                return false;
            }
        }
        var checkjsonstr = JSON.stringify(checklist);

        if(type === 'deletes'){
            layui_delete(base_url + "." + type + "?" + g_params_str, '确定删除所选？', checklist, [], md5list);
        }else if(type === 'clear'){
            layui_clear(base_url + ".clear?" + g_params_str);
        }else if(type === 'edits'){
            layui_edit(base_url + ".edit?handle_id=" + $(obj).attr("data-hdkey") + "&pks=" + encodeURI(checkjsonstr) + "&" + g_up.getParamString(g_params, ['pks', 'handle_id']), $(obj).html());
        }else if(type === 'add'){
            layui_edit(base_url + ".add" + "?" + g_up.getParamString(g_params, ['gpk', 'bind']) + "&gpk=" + encodeURI(g_pk) + "&bind=" + tpl_type, "新增", type);
        }
        return checklist;
    }

    function getRealUrl(url) {
        url = url.replace("\\", "/");
        if (url[0] === '.' && url[1] !== '.') {
            return base_url + url;
        }
        
        return base_url + "/" + url;
    }

    //获取单个列表
    function postCheckOper(obj, type){
        if(type === undefined) type= "";
        var checklist = [];
        var md5list = [];
        var tit = "";
        var o = $(obj);
        o.parent().parent().parent().each(function () {
            checklist.push($(this).attr("pk"));
            md5list.push($(this).attr("pkmd5"));
            tit = get_div_text($(this).find("td[key='" + oper_title + "'] .layui-table-cell").attr("value"));
        });

        if (checklist.length <= 0) {
            layer.msg('未选择数据', {icon:0});
            return false;
        }

        var config = {};
        if(o.attr('data-ismax') !== undefined){
            config['ismax'] = o.attr('data-ismax');
        }
        if(o.attr('data-method') !== undefined){
            config['method'] = o.attr('data-method');
        }
        if(o.attr('data-confirm') !== undefined){
            config['confirm'] = o.attr('data-confirm');
        }
        if(o.attr('data-key') !== undefined){
            config['key'] = o.attr('data-key');
        }

        var checkjsonstr = JSON.stringify(checklist);

        if(type === 'delete'){
            layui_delete(base_url + "." + type + "?" + g_params_str, '确定删除（' + tit + '）？', checklist, config, md5list);
        }else if(type === 'add') {
            var titin = '新增';
            if(tit !== ''){
                titin += g_next_name_flag + tit;
            }
            layui_edit(base_url + ".add?pk=" + encodeURI(checkjsonstr) + "&gpk=" + encodeURI(g_pk) + "&bind=" + tpl_type + "&" + g_up.getParamString(g_params, ['gpk', 'bind']), titin, 'add', config);
        }else if(type === 'edit'){
            var titin = '编辑';
            if(tit !== ''){
                titin += g_next_name_flag + tit;
            }
            layui_edit(base_url + ".edit?pk=" + encodeURI(checkjsonstr) + "&" + g_up.getParamString(g_params, ['pk']), titin, 'edit', config, md5list);
        }else if(type === 'copy'){
            var titin = '复制';
            if(tit !== ''){
                titin += g_next_name_flag + tit;
            }
            layui_edit(base_url + ".copy?pk=" + encodeURI(checkjsonstr) + "&" + g_up.getParamString(g_params, ['pk']), titin, 'copy', config, md5list);
        }else if(type === 'view'){
            var titin = '查看';
            if(tit !== ''){
                titin += g_next_name_flag + tit;
            }
            layui_edit(base_url + ".view?pk=" + encodeURI(checkjsonstr) + "&" + g_up.getParamString(g_params, ['pk']), titin, type, config);
        }else if(type === 'url'){
            if(checklist.length > 0) {
                try {
                    var pks = eval("(" + checklist[0] + ")");
                }catch (e) {
                    var pks = checklist[0];
                }
                var title = o.html() + g_next_name_flag + tit;
                var durl = o.attr("data-url");
                var data_type = o.attr("data-type");
                var reg = /http(s)?:\/\/([\w-]+\.)+[\w-]+(\/[\w- .\/?%&=]*)?/;
                var exp=new RegExp(reg);
                if(exp.test(durl.toLowerCase()) === false && durl[0] !== '/'){
                    if (data_type === 'bind') {
                        durl = base_url + ".bind?__type__=" + durl + "&__otype__=" + g_type_src;
                        type = 'bind';
                    } else if (data_type === 'extends') {
                        durl = base_url + ".extends?__type__=" + durl + "&__otype__=" + g_type_src;
                        type = 'extends';
                    } else {
                        durl = getRealUrl(durl);
                    }
                }

                var urlparam = t_urlparam(durl);
                if (data_type === 'bind' || data_type === 'extends') {
                    urlparam.set('pk', JSON.stringify([JSON.stringify(pks)]));
                    urlparam.set('_mid_', menu_id);
                } else {
                    var key = o.attr("data-key");
                    var pks_type = typeof pks;
                    if (key === undefined) key = "";
                    if (key === "") {
                        if (pks_type !== 'string') {
                            for (var i in pks) urlparam.set(i, pks[i]);
                        }
                    } else {
                        if (pks_type === 'string' || pks_type === 'number') {
                            urlparam.set(key, pks);
                        } else {
                            for (var i in pks) urlparam.set(key, pks[i]);
                        }
                    }
                }
                urlparam.set('pkmd5', md5list[0]);
                var url = urlparam.getUrl();
                var confirm = o.attr('data-confirm');
                var hw = handle_width;
                var hh = handle_height;
                var dw = o.attr('data-width');
                var dh = o.attr('data-height');
                var dw_bool = false;
                var dh_bool = false;
                if(dw !== undefined && dw !== ""){
                    dw_bool = true;
                    handle_width = dw;
                }
                if(dh !== undefined && dh !== ""){
                    dh_bool = true;
                    handle_height = dh;
                }
                if(confirm === 'true'){
                    layui_edit(url, "确定" + o.html() + " （" + tit + "）？", 'confirm', config);
                }else{
                    layui_edit(url, title, type, config);
                }
                if(dw_bool){
                    handle_width = hw;
                }
                if(dh_bool){
                    handle_height = hh;
                }
            }
        } else if(type === 'isbind' || type === 'unbind') {
            g_tmp_layout_load = layer.load(1, {
                shade: [0.1, '#fff']
            });
            $.ajax({
                type: "post",
                url: base_url + "." + type + "?pk=" + encodeURI(g_pk) + "&__type__=" + g_type_src + "&__otype__=" + g_otype,
                data: {
                    src: checkjsonstr
                },
                success: function (res) {
                    if (typeof res !== 'object') {
                        alert_error(res);
                        layer.close(g_tmp_layout_load);
                        return;
                    }

                    if (res.code === 0) {
                        alert_error(res.msg);
                        layer.close(g_tmp_layout_load);
                        return;
                    }

                    if (type == 'isbind') {
                        parent.$("#unbind").attr('data-load', 'false');
                    } else {
                        parent.$("#isbind").attr('data-load', 'false');
                    }
                    t_urlparam().run();
                }
            });
        }
        return checklist;
    }

    //单个元素操作
    function postCheckClick(obj){
        var o = $(obj);
        var objparent = o.parent();
        var checklist = [];
        var tit = "";
        var o_title = o.find(".layui-table-cell").attr("title");
        objparent.each(function () {
            checklist.push($(this).attr("pk"));
            tit = get_div_text($(this).find("td[key='" + oper_title + "'] .layui-table-cell").attr("value"));
        });

        if (checklist.length <= 0) {
            layer.msg('未选择数据', {icon:0});
            return false;
        }

        if(tit === undefined) tit = "";
        var key = o.attr('key');
        var th = $(".layui-table thead tr th[data-field='" + key + "']");
        var th_click = eval("(" + th.attr("data-click") + ")");
        var th_click_url = th_click.url;
        var h_w = handle_width;
        var h_h = handle_height;
        if(th_click.width !== undefined){
            h_w = th_click.width;
        }
        if(th_click.height !== undefined){
            h_h = th_click.height;
        }
        var ismax;
        if(handle_ismax === 'true' || handle_ismax === true){
            ismax = true;
        }else{
            ismax = false;
        }

        var okey = o.attr('key');
        var title = $("table.layui-table>thead th[data-field='" + okey + "']>div").html();
        if(title === undefined || title === ''){
            title = '设置'
        }
        if(o_title !== undefined && o_title !== ''){
            title = title + g_next_name_flag + o_title;
        }
        if(tit !== undefined && tit !== ''){
            title = title + g_next_name_flag + tit;
        }

        title = get_div_text(title);

        if(th_click.ismax !== undefined){
            ismax = th_click.ismax;
            if(ismax === 'true'){
                ismax = true;
            }else if(!ismax){
                ismax = false;
            }
        }
        if(th_click_url !== undefined && th_click_url !== "") {
            var th_click_key = th_click.key;
            var th_click_value = "";
            if(th_click_key === undefined || th_click_key === ""){//如果key未设置则取当前字段
                th_click_key = key;
                th_click_value = o.find(".layui-table-cell").attr("value");
            }else{ //如果key已设置则取td对应的列表字段
                th_click_value = objparent.find("td[key='" + th_click_key + "'] .layui-table-cell").attr("value");
            }

            if(th_click_value === undefined) th_click_value = "";

            if(th_click_url.indexOf("?") === -1) {
                var cflag = '?';
            }else{
                var cflag = '&';
            }
            var clickurl = "";
            if (th_click.bind === true) {
                clickurl = th_click_url + cflag + g_type;
                if (checklist.length === 1) {
                    clickurl = th_click_url + cflag + "&pk=" + encodeURI(JSON.stringify([checklist[0]]));
                }
            } else {
                clickurl = th_click_url + cflag + g_type + "&key=" + encodeURI(th_click_key) + "&value=" + encodeURI(th_click_value);
                if (checklist.length === 1) {
                    clickurl += '&pk=' + encodeURI(checklist[0]);
                }
            }
            var _blank = th_click._blank;
            if(_blank !== undefined && (_blank === 'true' || _blank === true)){
                window.open(clickurl, '_blank');
                return false;
            }
            var checkjsonstr = JSON.stringify(checklist);
            var posturl = base_url + ".edit?pk=" + encodeURI(checkjsonstr) + "&" + g_up.getParamString(g_params, ['pk']);
            var layerconfig = {
                type: 2
                , title: title
                , area: [h_w + 'px', h_h + 'px']
                , shade: 0.1
                , maxmin: true
                , offset: 'auto'
                , content: clickurl
                , success:function(){
                    g_tmp_layout_loadok = true;
                }
                , yes: function (index) {
                    var data = {};
                    window["layui-layer-iframe" + index].$("input, select, textarea").each(function () {
                        var _t = $(this);
                        var n = _t.attr("name");
                        if(n !== undefined && n !== ""){
                            data[n] = _t.val();
                        }
                    });
                    if(Object.keys(data).length > 0){
                        $.ajax({
                            type: "post",
                            url: posturl,
                            data: data,
                            success: function (result) {
                                layer.close(index);
                                set_html_value(result.data);
                                layer.msg(result.msg, {icon: 1, time: 1000});
                            }
                        });
                    }else{
                        layer.close(index);
                    }
                }
            };

            var button = th_click.button;
            if(button === undefined || button === 'true' || button === true){
                layerconfig['btn'] = ['确定', '取消'];
            }

            var layeropen = layer.open(layerconfig);

            dbclick_closes(layeropen, th_click.fixed);

            //是否弹出时全屏
            if(ismax) {
                layer.full(layeropen);
            }

            $(".layui-layer-max").click(function () {
                $(window).resize();
            });
        }
    }

    $(".js_batch_open_close").click(function () {
        var tval = $(this).html();
        if(tval === '全部展开'){
            var is_ajax = false;
            $(".js_tree_fa").each(function () {
                if($(this).attr('isopen') === undefined){
                    is_ajax = true;
                    return false;
                }
            });
            if(is_ajax) {
                $.ajax({
                    type: "post",
                    url: window.location.href,
                    data: {
                        type: 'all'
                    },
                    async: false,
                    success: function (result) {
                        var code = result.code;
                        if (code === 1) {
                            var data = result.data;
                            if(data !== undefined){
                                set_html_affer_all(data);
                            }
                        } else {
                            alert_error(result);
                        }
                    }
                });
            }else{
                $(".js_tree_fa[isopen=false]").trigger("click");
            }
            $(this).html('全部收缩');
        }else{
            $(".js_tree_fa[isopen=true]").trigger("click");
            $(this).html('全部展开');
        }
    });

    $(".js_batch_delete").click(function () {
        postCheckList("deletes");
    });

    $(".js_batch_clear").click(function () {
        postCheckList("clear");
    });

    $(".js_batch_handle").click(function () {
        postCheckList("edits", this);
    });

    $(".js_batch_add").click(function () {
        postCheckList("add");
    });

    $(".js_flush").click(function () {
        t_urlparam().run();
    });

    //导出本页
    $(".js_batch_export_this").click(function () {
        $().urlparam().set(export_flag, 'this').run();
    });

    //导出所选
    $(".js_batch_export_checked").click(function () {
        var check_list = postCheckList("export");
        if(check_list !== false) {
            $().urlparam().set(export_flag, 'checked').set('_@check@_', check_list).run();
        }
    });

    //导出全部
    $(".js_batch_export_all").click(function () {
        $().urlparam().set(export_flag, 'all').run();
    });

    $(".js_tools_print").click(function () {
        var jtp_url = $().urlparam().set("_@export@_", "print").getUrl();
        var newWin= window.open("");
        $.ajax({
            url: jtp_url + "&" + g_type,
            success: function (html) {
                newWin.document.write(html);
                newWin.document.close();
                setTimeout(function () {
                    newWin.focus();
                    newWin.print();
                    newWin.close();
                }, 100);
            }
        });
    });

    function operclick(obj) {
        obj.find(".js_oper_add").click(function () {
            postCheckOper(this, "add");
        });

        obj.find(".js_oper_delete").click(function () {
            postCheckOper(this, "delete");
        });

        obj.find(".js_oper_handle").click(function () {
            postCheckOper(this, "edit");
        });

        obj.find(".js_oper_copy").click(function () {
            postCheckOper(this, "copy");
        });

        obj.find(".js_oper_view").click(function () {
            postCheckOper(this, "view");
        });

        obj.find(".js_oper_url").click(function () {
            postCheckOper(this, "url");
        });

        obj.find(".js_oper_isbind").click(function () {
            postCheckOper(this, "isbind");
        });

        obj.find(".js_oper_unbind").click(function () {
            postCheckOper(this, "unbind");
        });

        obj.find(".js_oper_click").click(function () {
            postCheckClick(this);
        });
    }

    operclick($(g_tr));

    //搜索功能>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
    layui.use(['form', 'laydate'], function() {
        var form = layui.form;
        var laydate = layui.laydate;
        $(".js_search_time").each(function () {
            laydate.render({
                elem: '#' + $(this).attr("id")
            });
        });

        var up = t_urlparam();

        //选择
        form.on('select(change)', function (e) {
            var e_name = $(e.elem).attr('name');
            if (e_name === undefined) {
                return;
            }

            var e_value = e.value;
            var gp_value = g_params[e_name];

            if (e_value === '') {
                if (gp_value === undefined || gp_value === '') {
                    return;
                }
            } else if (e_value === gp_value) {
                return;
            }
            js_search_submit.trigger('click');
        });

        //提交
        form.on('submit(search)', function (data) {
            var df = data.field;
            for(var i in df){
                var v = $.trim(df[i]);
                if(v === undefined ||v === ''){
                    up.remove(i);
                }else{
                    up.set(i, v);
                }
            }
            //跳转到第一页
            up.set("p", "1").run();
            return false;
        });

        //重置
        form.on('submit(reset)', function (data) {
            for(var i in data.field){
                up.remove(i);
            }
            //跳转到第一页
            up.set("p", "1").run();
            return false;
        });
    });



    //页面自适应效果开始>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
    //列表滚动条出现时补充框效果
    $(".layui-table thead tr").append('<th class="layui-table-patch" style="display: none;"><div class="layui-table-cell" style="width: 15px;"></div></th>');
    //自定义网页跳转配置
    $(".layui-table-view").urlparam();

    //标题和列表滚动条同步设置
    function setoffset() {
        $(".layui-table-header").scrollLeft($(g_class).scrollLeft());
    }

    $(g_class).scroll(function () {
        setoffset();
    });

    //宽度自适应设置
    var fixwidth = -1;
    //列表最小高度设置
    var minheight = 295;
    function getScrollbarWidth() {
        var oP = document.createElement('p'), styles = {
            width: '100px',
            height: '100px',
            overflowY: 'scroll',
        }, i, scrollbarWidth;
        for (i in styles){
            oP.style[i] = styles[i];
        }
        document.body.appendChild(oP);
        scrollbarWidth = oP.offsetWidth - oP.clientWidth;
        $(oP).remove();
        return scrollbarWidth;
    }
    var scrollbarwidth = getScrollbarWidth();
    var tmout = "";
    //页面宽高自适应
    function resizeset(flag){
        clearTimeout(tmout);
        if(flag === undefined) flag= true;
        $(".layui-table-view").each(function () {
            var ltv_this = $(this);
            var heightful = ltv_this.attr("layui-height-ful");
            if(heightful === undefined || heightful === ""){
                heightful = 0;
            }else{
                heightful = parseInt(heightful);
                if(isNaN(heightful)) heightful = 0;
            }

            var offset = ltv_this.position().top;
            var dheight = $(window).height();
            var height = dheight - offset + heightful - 25;
            if(height < minheight) height = minheight;
            ltv_this.height(height);
            var seth = height;
            if(ltv_this.attr("layui-isunder") === "true"){
                seth -= 70;
            }else{
                seth -= 30;
            }
            ltv_this.find(g_class).height(seth);
        });
        var w = fixwidth;
        if(w < 0) w = $(".layui-table-header").width();
        var ltbh = $(g_class).height();
        var ltbth = $(g_table).height();
        var wadd = -scrollbarwidth + 1;
        if(ltbh > ltbth){
            wadd = 1;
        }
        if(is_fixed === 'true'){
            w = 1;
        }else{
            w = w + wadd;
        };
        $(".layui-table-header table").width(w - 2);


        var wlist = [];
        $(".layui-table-header table tr th").each(function () {
            wlist.push($(this).find(".layui-table-cell").width());
        });


        $(g_tr).each(function () {
            var i = 0;
            $(this).find("td .layui-table-cell").each(function () {
                $(this).width(wlist[i]);
                i ++;
            });
        });

        var lthw = $(".layui-table-view").width();
        var ltbw = $(g_table).width();
        //$("#test").html(ltbw + "|" + (lthw + wadd + 1)  + "<BR>" + ltbh  + "|" + ltbth);
        if(ltbw > lthw + wadd + 1 && ltbh < ltbth){
            $(".layui-table-patch").css("display", "block");
        }else{
            $(".layui-table-patch").css("display", "none");
        }

        setoffset();
        $(".layui-layer-tips").remove();

        if(flag){
            resizeset(false);
        }
    }

    //初始化页面宽高
    resize_init = function() {
        $(".web_loadding").css("display", "none");
        var ltv = $(".layui-table-view");
        ltv.css("display", "block");
        var w = ltv.attr("layui-width");
        if(w !== undefined && w !== ""){
            w = parseInt(w);
            if(isNaN(w)){
                w = -1;
            }
        }else{
            w = -1;
        }

        fixwidth = w;

        $(window).resize(function () {
            clearTimeout(tmout);
            tmout = setTimeout(resizeset, 10);
        });

        resizeset();

        //分页设置
        var jumppage = t_urlparam();
        layui.use(['laypage', 'layer', 'table'], function() {
            var laypage = layui.laypage
                , layer = layui.layer
                , elem = "pageinfo";
            var elemobj = $("#" + elem);
            var limits = [10, 15, 20, 30, 50, 100];
            var limit = elemobj.attr("data-pagesize");
            var limitdef = elemobj.attr("data-pagesizedef");
            if(limitdef !== undefined && limitdef !== ""){
                limitdef = parseInt(limitdef);
                if(isNaN(limitdef)){
                    limitdef = -1;
                }
            }else{
                limitdef = -1;
            }
            if(limitdef > 0){
                if($.inArray(limitdef, limits) < 0){
                    limits.push(limitdef);
                    limits.sort(function(a,b){
                        return a-b;
                    });
                }
            }

            var color = $("#" + elem).attr("data-color");
            if(color === undefined || color === ''){
                color = '1E9FFF';
            }
            laypage.render({
                elem: elem
                ,count: elemobj.attr("data-count")
                ,curr : elemobj.attr("data-page")
                ,limit : limit
                ,limits : limits
                ,theme : '#' + color
                ,groups : 7
                ,layout : ['count', 'prev', 'page', 'next', 'limit', 'refresh', 'skip']
                ,jump: function(obj, first){
                    if(!first) {
                        jumppage.set("p", obj.curr).set("psize", obj.limit).run();
                    }
                }
            });
        });
        layui.form.render();
    }

    layui.use(['form', 'laydate'], function() {
        if (parent.$(".layui-layer-loading").size() > 0) {
            var timeout = "";

            function iframe_show() {
                clearTimeout(timeout);
                if (parent.$(".layui-layer-loading").size() <= 0) {
                    resize_init();
                    tmout = setTimeout(resizeset, 20);
                } else {
                    timeout = setTimeout(iframe_show, 10);
                }
            }

            iframe_show();
        } else {
            resize_init();
        }
    });
    //页面自适应效果结束>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
});
