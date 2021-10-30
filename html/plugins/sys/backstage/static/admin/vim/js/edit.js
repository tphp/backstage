var submit;
var reset;
var body;
var base_url; // 根页面
$(function () {
    body = $('body');
    base_url = body.attr("data-base-url");


    var data_md5 = $('.js_tab_head').find('.layui-this').attr('data-md5');
    var iframeFirst = $(".js_tab_body[data-md5=" + data_md5 + "] iframe");

    if (iframeFirst.length > 0) {
        var timeOut = '';

        function setSrc() {
            clearTimeout(timeOut);
            if (iframeFirst.height() > 0) {
                iframeFirst.attr('src', iframeFirst.attr('data-src'));
            } else {
                timeOut = setTimeout(setSrc, 10);
            }

        }

        setSrc();
    }

    $(".js_tab_head>li").click(function () {
        var that = this;
        var data_md5 = $(this).attr('data-md5');
        $(".js_tab_body[data-md5=" + data_md5 + "] iframe").each(function () {
            var _this = $(this);
            var src = _this.attr('src');
            var data_load = _this.attr("data-load");
            _this.attr("data-load", "true");
            if (src === undefined) {
                _this.attr('src', $(this).attr('data-src'));
            } else if (data_load === 'false') {
                window[_this.attr('name')].window.location.reload();
            }
        });

        setTimeout(function () {
            $(that).resize();
        }, 1);
    });

    layui.use(['form', 'layedit', 'laydate'], function(){
        var form = layui.form
            ,layedit = layui.layedit;
        var fieldstr = $('body').attr('data-field');
        var field = {};
        if(fieldstr === undefined || fieldstr === ''){
            field = {};
        }else{
            field = eval('(' + fieldstr + ')');
        }

        //创建一个编辑器
        //var editIndex = layedit.build('LAY_demo_editor');

        //系统验证规则
        var default_verify = {
            title: function(value){
                if(value.length < 5){
                    return '标题至少得5个字符啊';
                }
            }
            ,pass: [/(.+){6,12}$/, '密码必须6到12位']
            ,content: function(value){
                layedit.sync(editIndex);
            }
            ,md5: function (value, obj) {
                var name = $(obj).attr("name");
                var nobj = $("input[data-name='" + name + "']");
                var nval = nobj.val();
                if(nval !== '' || value !== '') {
                    if (nval !== value) {
                        return "密码输入不一致";
                    }
                }
            }
            ,'@number': function(value){
                if(value === undefined || value === ''){
                    return false;
                }
                if(!value || isNaN(value)) return '只能填写数字'
            }
        };

        //自定义验证规则
        if(typeof layui_form_verify === 'function'){
            var verify = layui_form_verify();
            if(verify !== undefined){
                for (var i in verify){
                    if(default_verify[i] === undefined){
                        default_verify[i] = verify[i];
                    }
                }
            }
        }

        form.verify(default_verify);

        //status控件数值转换
        $(".js_status").each(function () {
            var name = $(this).attr("name");
            if(field[name] === undefined || field[name] === '' || field[name] === '0' || field[name] === 0){
                field[name] = '';
            }else{
                field[name] = 'on';
            }
        });

        //表单初始赋值
        form.val('main', field);

        //树状结构加入下一个节点
        function tree_insert_html(obj, key, value, inputkey, notvalues, disabled, dfunction){
            var keyflag = inputkey + "_" + key;
            var html = '<div class="layui-input-inline layui-form" lay-filter="' + keyflag + '" data-key="' + key + '"><select ' + disabled + '>';
            html += '<option value="' + key + '">-- ' + value['name'] + ' --</option>';
            var list = value['list'];
            var listmore = value['listmore'];
            var next = value['next'] + '';
            if(next === undefined) next = "";
            for(var j in list){
                var k = list[j]['key'];
                var v = list[j]['value'];
                if(listmore !== undefined && listmore.hasOwnProperty(k) && listmore[k] > 0) v = "+ " + v;
                if(k + '' === next){
                    html += '<option value="' + k + '" selected="">' + v + '</option>';
                }else{
                    html += '<option value="' + k + '">' + v + '</option>';
                }
            }
            html += '</select></div>';
            obj.append(html);
            form.render("select", keyflag);
            obj.find('.layui-input-inline[lay-filter="' + keyflag + '"]').each(function () {
                var _t = $(this);
                var listmore = value['listmore'];
                _t.find("dl.layui-anim dd").click(function () {
                    var _tt = $(this);
                    var _ttval = _tt.attr("lay-value");
                    if (typeof dfunction === 'function') {
                        dfunction(_ttval);
                    }
                    _t.nextAll(".layui-input-inline").remove();
                    $("input[name='" + inputkey + "']").val(_ttval);
                    if(listmore !== undefined && listmore.hasOwnProperty(_ttval)) {
                        var url = base_url + ".selectTree";
                        $.ajax({
                            type: "post",
                            url: url,
                            data: {
                                key: inputkey,
                                value: _ttval,
                                notvalues: notvalues
                            },
                            success: function (result) {
                                if (typeof result === 'object') {
                                    if (result.code === 1) {
                                        for (var i in result.data) {
                                            tree_insert_html(obj, i, result.data[i], inputkey, notvalues, disabled, dfunction);
                                        }
                                    } else {
                                        dbclick_close(layer.alert(result.msg, {icon: 2, title: '错误'}));
                                    }
                                } else {
                                    dbclick_close(layer.alert(result, {icon: 2, title: '错误'}));
                                }
                            }
                        });
                    }
                });
            });
        }

        function set_tree_init() {
            //树状结构
            $(".js_tree").each(function () {
                var _this = $(this);
                var djson = _this.attr("data-json");
                var dfunction = _this.attr("data-function");
                var dnotvalues = _this.attr("data-notvalues");
                if (djson === undefined || djson === 'null' || djson === '') {
                    djson = '{}';
                }
                if (dnotvalues === undefined || dnotvalues === 'null' || dnotvalues === '') {
                    dnotvalues = '{}';
                }
                var json = $.parseJSON(djson);
                var notvalues = $.parseJSON(dnotvalues);
                var isview = _this.attr("data-isview");
                var disabled = '';
                if (isview === 'true') disabled = 'disabled=""';
                var key = _this.attr("data-key");
                var ii = 0;
                if (dfunction !== undefined) {
                    dfunction = window[dfunction];
                }
                for (var i in json) {
                    tree_insert_html(_this, json[i]['key'], json[i]['list'], key, notvalues, disabled, dfunction);
                    ii++;
                }
                if (ii <= 0) {
                    tree_insert_html(_this, "", {"name": "顶级"}, key, notvalues, disabled, dfunction);
                }

                var last_value = _this.find("select").last().find('option:selected').val();
                if (last_value !== undefined) {
                    $("input[name='" + key + "']").val(last_value);
                }
            });
        }

        set_tree_init();

        var dfield = [];
        var issubmit = false;

        //监听提交
        form.on('submit(submit)', function(data){
            dfield = data.field;
            issubmit = true;
            return false;
        });

        function setData(){
            $(".js_status").each(function () {
                var name = $(this).attr("name");
                if(dfield[name] === undefined || dfield[name] === ''){
                    dfield[name] = '0';
                }else{
                    dfield[name] = '1';
                }
            });
            $(".js_checkbox").each(function () {
                var size = $(this).find("input[type='checkbox']:checked").size();
                if(size <= 0){
                    dfield[$(this).attr("data-key")] = '';
                }
            });
        }

        //提交
        submit = function() {
            dfield = [];
            issubmit = false;
            $(".layui-btn-submit").click();
            if(issubmit) {
                var retdata;
                setData();
                $.ajax({
                    type: "post",
                    url: window.location.href,
                    data: dfield,
                    async: false,
                    error: function (XMLHttpRequest, textStatus){
                        xml_http_request_err(XMLHttpRequest, textStatus);
                    },
                    success: function (result) {
                        retdata = result;
                    }
                });
                return retdata;
            }else{
                // setTimeout(function () {
                //     console.log($('.layui-form-danger').parent().html());
                // }, 10);
                // console.log($('.layui-form-danger').parent().html());
                var lfd_index = 0;
                $(".layui-form-main>.layui-tab-item").each(function () {
                    lfd_index ++;
                    if($(this).find('.layui-form-danger').size() > 0){
                        return false;
                    }
                });
                if(lfd_index > 0) {
                    $("ul.layui-tab-title>li:nth-child(" + lfd_index + ")").trigger('click');
                }
                return false;
            }
        };

        var reset_time_out = "";

        //重置
        reset = function() {
            $(".layui-btn-reset").trigger('click');
            $(".layui-input-inline").remove();
            form.val('main', field);

            clearTimeout(reset_time_out);
            reset_time_out = setTimeout(set_tree_init, 50);
        };

        //保存数据
        $(".js_btn_save").click(function () {
            var result = submit();
            if(result !== false){
                if (typeof result === 'object') {
                    if(result.code === 1){
                        layer.msg(result.msg, {icon: 1, time: 1000});
                    }else{
                        dbclick_close(layer.alert(result.msg, {icon: 2, title: '错误'}));
                    }
                } else {
                    dbclick_close(layer.alert(result, {icon: 2, title: '错误'}));
                }
            }
        });

        //还原数据
        $(".js_btn_reset").click(function () {
            reset();
        });

        //刷新当前页面
        $(".js_btn_flush").click(function () {
            $().urlparam().run();
        });

        $(".js_name_remark").mouseover(function () {
            var tips = $(this).parent().find(".layui-layer-tips");
            if(tips.hasClass('layui-layer-tips-first')){
                tips.css("top",  '43px');
            }else{
                var height = tips.height() + 5;
                tips.css("top", -height + 'px');

            }
            tips.css("display", "block");
        }).mouseleave(function () {
            $(this).parent().find(".layui-layer-tips").css("display", "none");
        });
    });
});
