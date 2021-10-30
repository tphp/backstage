/**
 * URL跳转设定
 * @returns {{url: jQuery, change: (function(*, *): $.fn.urlparam), gourl: gourl}}
 */
$.fn.urlparam = function(){
    var _this = $(this);
    var turl = _this.attr("url");
    if(turl == undefined || turl == "") turl = window.location.href;
    var ind = turl.indexOf('#');
    if (ind > 0) {
        turl = turl.substring(0, ind);
    }
    var up = {
        url : turl,
        timeout : 1500,
        msg : "",
        is_load: false,
        //url参数增加或替换
        set : function (name, value, is_encode) {
            if(typeof name === "object"){
                for(var i in name){
                    this.set(i, name[i], is_encode);
                }
                return this;
            }
            var url = this.url;
            var newUrl="";
            var reg1 = new RegExp("(^|)([?])"+ name +"=([^&]*)(|$)");
            var reg2 = new RegExp("(^|)([&])"+ name +"=([^&]*)(|$)");
            if(value === undefined){
                value = '';
            }else if(is_encode !== false){
                value = encodeURIComponent(value);
            }
            var tmp = name + "=" + value;
            if(url.match(reg1) != null){
                newUrl= url.replace(eval(reg1), "?" + tmp);
            }else if(url.match(reg2) != null) {
                newUrl= url.replace(eval(reg2), "&" + tmp);
            }else{
                if(url.match("[\?]")){
                    newUrl= url + "&" + tmp;
                }else{
                    newUrl= url + "?" + tmp;
                }
            }
            newUrl = newUrl.replace("?&", "?").replace(/&&/g, "&");
            this.url = newUrl;
            return this;
        },
        //获取URL参数
        get : function(name, is_unescape) {
            var reg = new RegExp("(^|&)"+ name +"=([^&]*)(&|$)");
            var r = window.location.search.substr(1).match(reg);
            if(r != null){
                if (is_unescape) {
                    return unescape(r[2]);
                }
                return r[2];
            }
            return "";
        },

        getDeletes: function(deletes) {
            var dlt = {};
            var type = typeof deletes;
            if (type === 'string') {
                dlt[deletes] = true;
            } else if (type === 'object') {
                for (var i in deletes) {
                    var iv = deletes[i];
                    if (typeof iv === 'string') {
                        dlt[iv] = true;
                    }
                }
            }
            return dlt;
        },
        
        getParamArray : function(deletes, add_data) {
            var search = window.location.search.substr(1).split("&");
            var dlt = this.getDeletes(deletes);
            var ret = {};
            for (var i in search) {
                var ivs = search[i].split("=");
                if (ivs.length < 2) {
                    continue;
                }
                if (ivs[0] === '' || ivs[1] === '' || dlt[ivs[0]]) {
                    continue;
                }
                ret[ivs[0]] = ivs[1];
            }
            
            if (typeof add_data === 'object') {
                for (var i in add_data) {
                    if (typeof i !== 'string') {
                        continue;
                    }
                    ret[i] = add_data[i];
                }
            }
            return ret;
        },

        getParamString : function(data, deletes) {
            if (typeof data !== 'object') {
                return '';
            }
            
            var dlt = this.getDeletes(deletes);
            var ret = [];
            for (var i in data) {
                if (dlt[i]) {
                    continue;
                }

                ret.push(i + '=' + data[i]);
            }
            
            return ret.join("&");
        },
        
        //设置url
        setUrl : function(url){
            if(url === undefined){
                url = '';
            }
            if(url.replace(/:\/\//g, "").indexOf('/') < 0){
                url += '/';
            }
            this.url = url;
            return this;
        },
        //获取URL
        getUrl : function(){
            return this.url;
        },
        //url参数删除
        remove : function () {
            var url = this.url;
            for(var i in arguments) {
                var name = arguments[i];
                var newUrl = "";
                var reg1 = new RegExp("(^|)([?])" + name + "=([^&]*)(|$)");
                var reg2 = new RegExp("(^|)([&])" + name + "=([^&]*)(|$)");
                var tmp = "#";
                if (url.match(reg1) != null) {
                    newUrl = url.replace(eval(reg1), "?" + tmp);
                } else if (url.match(reg2) != null) {
                    newUrl = url.replace(eval(reg2), tmp);
                }
                if(newUrl != "") url = newUrl.replace("?#&", "?").replace(/#&/g, "&").replace("?#", "").replace(/#/g, "");
            }
            this.url = url;
            return this;
        },
        //打印消息
        alert : function(msg, timeout){
            if(msg == undefined) msg = "";
            if(timeout == undefined) timeout = 0;
            this.msg = msg;
            if(timeout > 0) this.timeout = timeout;
            return this;
        },
        //数据初始化
        datainit : function (obj){
            var o = $(obj);
            var set = o.attr("data-set"); //修改URL参数
            var remove = o.attr("data-remove"); //删除URL参数
            var e = eval;
            if(set != undefined && set != ""){
                try {
                    jobj = e('(' + set + ')');
                    for (var i in jobj) {
                        up.set(i, jobj[i]);
                    }
                } catch (e) {
                    // None
                    // console.log(e.valueOf());
                }
            }
            if(remove != undefined && remove != ""){
                try {
                    jobj = e('(' + remove + ')');
                    for (var i in jobj) {
                        up.remove(jobj[i]);
                    }
                } catch (e) {
                    // None
                    // console.log(e.valueOf());
                }
            }
        },
        loading : function(){
            if(!this.is_load) {
                if (layer !== undefined) {
                    layer.load(1);
                }
                this.is_load = true;
            }
            return this;
        },
        run : function (obj) {
            if(obj != undefined){
                this.datainit(obj);
            }
            var jmpurl = this.url;
            if(this.msg == "") {
                window.location.href = jmpurl;
            }else{
                var info = {icon:1};
                if(this.timeout > 0){
                    info["time"] = this.timeout;
                }
                layer.msg(this.msg, info, function () {
                    window.location.href = jmpurl;
                });
            }
        },
        // 不刷新页面更改浏览器参数
        run_from_url: function () {
            window.history.pushState({}, 0, this.url);
        }
    };

    //父节点单击事件
    var dcp = _this.attr("data-click-parent");
    if(dcp != undefined && dcp != ""){
        _this.parent(dcp).click(function () {
            up.run(this);
        });
    }

    //子节点单击事件
    var dcf = _this.attr("data-click-find");
    if(dcf != undefined && dcf != ""){
        _this.find(dcf).click(function () {
            up.run(this);
        });
    }


    //当前单击事件
    var dct = _this.attr("data-click-this");
    if(dct != undefined){
        dct = dct.toLowerCase();
        if(dct == 'yes' || dct == 'true' || dct == 'y'){
            _this.click(function () {
                up.run(this);
            });
        }
    }

    //不定元素单击事件
    var dc = _this.attr("data-click");
    if(dc != undefined && dc != ""){
        $(dc).click(function () {
            up.run(this);
        });
    }

    return up;
};

// 批量加载JS文件
$.JS = function(lists, func){
    $(function () {
        var ls = [];
        var tp = typeof lists;
        if(tp === 'string'){
            lists = lists.trim();
            lists !== '' && ls.push(lists.trim());
        }else if(tp === 'object'){
            for(var i in lists){
                var iv = lists[i];
                if(typeof iv === 'string'){
                    iv = iv.trim();
                    !ls.includes(iv) && iv !== '' && ls.push(iv.trim());
                }
            }
        }

        ls = ls.reverse();

        function _load_script(src, callback) {
            var script = document.createElement('script'),
                head = document.getElementsByTagName('head')[0];
            script.type = 'text/javascript';
            script.charset = 'UTF-8';
            script.src = src;
            if (script.addEventListener) {
                script.addEventListener('load', function () {
                    callback('load');
                }, false);
                script.addEventListener('error', function () {
                    callback('error');
                }, false);
            } else if (script.attachEvent) {
                script.attachEvent('onreadystatechange', function () {
                    var target = window.event.srcElement;
                    if (target.readyState === 'loaded') {
                        callback('load');
                    }
                });
            }
            head.appendChild(script);
        }

        var ok = [];
        var error = [];
        function _loops_(){
            var _t = ls.pop();
            _load_script(_t, function (state) {
                if(state === 'load'){
                    ok.push(_t);
                }else{
                    error.push(_t);
                }
                _loops_start();
            });
        }

        function _loops_start() {
            if (ls.length > 0) {
                _loops_();
            }else if(typeof func === 'function'){
                func(ok, error);
            }
        }

        _loops_start();
    });
};

//编码
$.fn.html_encode = function(str) {
    return $('<div>').text(str).html();
};

//解码
$.fn.html_decode = function(str) {
    return $('<div>').html(str).text();
};

//复制数据
$.fn.copy_text = function(msg, str){
    if (msg === undefined) {
        msg = '复制成功';
    }
    var _this = $(this);
    if(str !== undefined){
        _this.html(str);
    }
    if(_this.val() === ''){
        if(layer !== undefined){
            layer.msg("未复制，数据为空", {icon: 2});
        }
    }else {
        _this.focus();
        _this.select();
        try {
            document.execCommand('copy');
            this.focus();
            if(layer !== undefined){
                layer.msg(msg, {icon: 1});
            }
        } catch (e) {
        }
    }
}

//清除javascript
$.fn.clear_script = function (html) {
    html = html.replace(/refresh/g, "#refresh").replace(/target/g, "t").replace(/href=/g, "target='_blank' href=");
    //过滤所有javascript标签
    var reg = /<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi;
    while (reg.test(html)) {
        html = html.replace(reg, "");
    }
    return html;
};

$.fn.get_date = function (time) {
    if(time == undefined){
        time = new Date().getTime();
    }
    var datetime = new Date();
    datetime.setTime(time);
    var year = datetime.getFullYear();
    var month = datetime.getMonth() + 1 < 10 ? "0" + (datetime.getMonth() + 1) : datetime.getMonth() + 1;
    var date = datetime.getDate() < 10 ? "0" + datetime.getDate() : datetime.getDate();
    var hour = datetime.getHours()< 10 ? "0" + datetime.getHours() : datetime.getHours();
    var minute = datetime.getMinutes()< 10 ? "0" + datetime.getMinutes() : datetime.getMinutes();
    var second = datetime.getSeconds()< 10 ? "0" + datetime.getSeconds() : datetime.getSeconds();
    return year + "-" + month + "-" + date+" "+hour+":"+minute+":"+second;
};

//获取表单数据并自动转化为二维关联
$.fn.get_form_array = function(){
    var ret = {};
    var json;
    try {
        json = $(this).serializeArray();
        if(json == undefined){
            return ret;
        }
    }catch (e) {
        return ret;
    }

    for(var i in json){
        var jv = json[i];
        var jv_name = jv.name;
        var jv_value= jv.value;
        var j_len = jv_name.length;
        var i_l = jv_name.indexOf('[');
        var i_r = jv_name.indexOf(']');
        if(i_l > 0 && i_r > i_l && i_r + 1 == j_len){
            var key = jv_name.substring(0, i_l);
            var index = jv_name.substring(i_l + 1, i_r);
            if(ret[key] == undefined){
                ret[key] = {};
            }
            ret[key][index] = jv_value;
        }else{
            ret[jv_name] = jv_value;
        }
    }
    return ret;
}

//在光标处插入内容
$.fn.insert = function(val, t) {
    var $t = $(this)[0];
    if (document.selection) { // ie
        this.focus();
        var sel = document.selection.createRange();
        sel.text = val;
        this.focus();
        sel.moveStart('character', -l);
        var wee = sel.text.length;
        if (arguments.length === 2) {
            sel.moveEnd("character", wee + t);
            t <= 0 ? sel.moveStart("character", wee - 2 * t - val.length) : sel.moveStart( "character", wee - t - val.length);
            sel.select();
        }
    } else if ($t.selectionStart || $t.selectionStart === 0) {
        var startPos = $t.selectionStart;
        var endPos = $t.selectionEnd;
        var scrollTop = $t.scrollTop;
        $t.value = $t.value.substring(0, startPos)
            + val
            + $t.value.substring(endPos,$t.value.length);
        this.focus();
        $t.selectionStart = startPos + val.length;
        $t.selectionEnd = startPos + val.length;
        $t.scrollTop = scrollTop;
        if (arguments.length === 2) {
            $t.setSelectionRange(startPos - t,
                $t.selectionEnd + t);
            this.focus();
        }
    } else {
        this.value += val;
        this.focus();
    }
}

// 展示动态页面，防止页面布局混乱
$.fn.show_html = function (str) {
    str = this.html_decode(str);
    str = str.replace(/&amp;/g, "&");
    if (str.indexOf("<") < 0) {
        return str;
    }
    var arr = str.split('<');
    for (var i in arr) {
        var iv = arr[i];
        var pos = iv.indexOf(">");
        if (pos < 0) {
            continue;
        }
        var left_str = iv.substring(0, pos);
        var pos_null = left_str.indexOf(" ");
        if (pos_null > 0) {
            pos = pos_null;
            left_str = left_str.substring(0, pos);
        }
        var is_end = '';
        if (left_str[0] == '/') {
            is_end = '/';
            left_str = left_str.substring(1);
        }
        if (['a', 'span', 'pre', 'p'].indexOf(left_str) >= 0) {
            continue;
        }
        var right_str = iv.substring(pos);
        arr[i] = is_end + 'span' + right_str;
    }
    return arr.join("<");
}

function window_close() {
    var llc = parent.$(".layui-layer-close:last");
    if(llc.size() <= 0){
        window.opener=null;
        window.open('','_self');
        window.close();
    }else{
        llc.trigger("click");
    }
}

function dbclick_close(layui_id, obj, is_click_one) {
    var _layer, _$;
    if(obj !== undefined && obj !== null && typeof obj === 'object'){
        _layer = obj.layer;
        _$ = obj.$;
    }else{
        _layer = layer;
        _$ = $;
    }

    if(_layer === undefined){
        _layer = layer;
    }
    var _o = _$("#layui-layer-shade" + layui_id);
    if(is_click_one){
        //单击阴影部分关闭窗口
        _o.click(function () {
            _layer.close(layui_id);
        });
    }else{
        //双击阴影部分关闭窗口
        _o.dblclick(function () {
            _layer.close(layui_id);
        });
    }
}

//ajax错误提醒
function xml_http_request_err(XMLHttpRequest, textStatus, is_return, obj) {
    var text = "";
    try{
        var json = JSON.parse(XMLHttpRequest.responseText);
        if(json.message != undefined){
            text = json.message;
            var tpos = text.indexOf(':')
            if(tpos > 0){
                text = text.substring(tpos + 1);
            }
        }else{
            text = XMLHttpRequest.responseText;
        }
    }catch (e) {
        text = XMLHttpRequest.responseText;
    }
    if(text == undefined || text == ''){
        text = "页面无法访问或超时";
    }
    var info_msg = textStatus + ":" + text;
    if(is_return){
        return info_msg;
    }
    var _layer;
    if(obj !== undefined && typeof obj === 'object'){
        _layer = obj.layer;
    }else{
        _layer = layer;
    }
    dbclick_close(_layer.alert(info_msg, {icon: 2, title: '错误', shade: 0.1}), obj);
}

$(function () {
    function resize() {
        $(".layui-layer-maxmin").parent().parent().width("100%").height("100%").each(function () {
            var _ts = $(this);
            var _tsh = _ts.height();
            if($(".layui-layer-btn").size() > 0){
                _tsh = _tsh - 102;
            }else{
                _tsh = _tsh - 43;
            }
            var _ts_iframe = _ts.find("iframe");
            if(_ts_iframe.size() > 0) {
                _ts_iframe.height(_tsh);
            }else{
                _ts.find(".layui-layer-content").height(_tsh);
            }
            _ts.css("top", 0).css("left", 0);
        });
    }

    $(window).resize(function () {
        resize();
    });

    $("body").on("click", ".layui-layer-max", function(){
        if($(".layui-layer-maxmin").size() == 0){
            var move = $(this).parent().parent();
            var m_w = move.width();
            var m_h = move.height();
            var win = $(window);
            var w_w = win.width();
            var w_h = win.height();
            var left = (w_w - m_w) / 2;
            var top = (w_h - m_h) / 2;
            move.css('left', left).css('top', top);
        }
    });
    resize();

    $("input[type='text'], textarea").attr("spellcheck", "false");
    $('input[type="password"]').attr("autocomplete", "new-password");
    $(".js_password_readonly").click(function () {
        if($(this).attr('readonly') !== undefined) {
            $(this).removeAttr('readonly');
        }
    });
});

// ajax处理
function __ajax(option, error_msg, is_msg) {
    if(typeof option !== 'object'){
        return;
    }

    var obj = option['object'];
    var _layer;
    if(obj !== undefined && typeof obj === 'object'){
        _layer = obj.layer;
    }else{
        _layer = layer;
    }

    var is_load = option['loading'];
    if(is_load !== false){
        is_load = true;
    }

    var layout_load;
    if(is_load){
        layout_load = _layer.load(1, {
            shade: [0.1, '#fff'] //0.1透明度的白色背景
        });
    }
    option['error'] = function (XMLHttpRequest, textStatus){
        if(is_load) {
            _layer.close(layout_load);
        }
        xml_http_request_err(XMLHttpRequest, textStatus, false, obj);
    };
    var success = option['success'];
    option['success'] = function (result) {
        if(is_load) {
            _layer.close(layout_load);
        }
        var cf = {icon:2, shade: 0.1};
        if(typeof result === 'string'){
            cf['area'] = ['600px', '400px'];
            dbclick_close(_layer.alert(result, cf), obj);
        }else{
            var msg = result.msg;
            if(msg === undefined || msg === ''){
                msg = 'Error';
            }
            if(result.code === 1 || result.code === '1' || result.code === true){
                if(typeof success === 'function'){
                    success(msg, result.data, _layer);
                }
            }else{
                var e_func = function (_msg) {
                    if(_msg !== undefined && _msg !== ''){
                        cf['title'] = _msg;
                    }
                    if(is_msg === undefined || is_msg === false) {
                        dbclick_close(_layer.alert(msg, cf), obj);
                    }else{
                        _layer.msg(msg, {icon:2});
                    }
                }

                if(typeof error_msg === 'function'){
                    error_msg(msg, result.code, e_func);
                }else{
                    e_func(error_msg);
                }
            }
        }
    };
    $.ajax(option);
}

function get_attr_json(json_str) {
    if(json_str === undefined){
        return [];
    }
    return eval("(" + json_str.replace(/&apos;/gi, "'") + ")");
};

function get_attr_json_decode(json) {
    return JSON.stringify(json).replace(/'/gi, "&apos;");
};

// 设置ace编辑器值
function set_ace_editor_value(ace_editor, value) {
    var cursor = ace_editor.getSelection().cursor;
    var cursor_row = cursor.row;
    var cursor_column = cursor.column;
    ace_editor.setValue(value);
    ace_editor.clearSelection();
    ace_editor.moveCursorTo(cursor_row, cursor_column);
    ace_editor.resize();
}

function get_ace_themes() {
    return {
        //暗主题
        ambiance:"ace/theme/ambiance",
        chaos:"ace/theme/chaos",
        clouds_midnight:"ace/theme/clouds_midnight",
        cobalt:"ace/theme/cobalt",
        dracula:"ace/theme/dracula",
        gob:"ace/theme/gob",
        gruvbox:"ace/theme/gruvbox",
        idle_fingers:"ace/theme/idle_fingers",
        kr_theme:"ace/theme/kr_theme",
        merbivore:"ace/theme/merbivore",
        mono_industrial:"ace/theme/mono_industrial",
        monokai:"ace/theme/monokai",
        pastel_on_dark:"ace/theme/pastel_on_dark",
        solarized_dark:"ace/theme/solarized_dark",
        solarized_light:"ace/theme/solarized_light",
        terminal:"ace/theme/terminal",
        tomorrow_night:"ace/theme/tomorrow_night",
        tomorrow_night_blue:"ace/theme/tomorrow_night_blue",
        tomorrow_night_bright:"ace/theme/tomorrow_night_bright",
        tomorrow_night_eighties:"ace/theme/tomorrow_night_eighties",
        twilight:"ace/theme/twilight",
        vibrant_ink:"ace/theme/vibrant_ink",

        //亮主题
        chrome:"ace/theme/chrome",
        clouds:"ace/theme/clouds",
        crimson_editor:"ace/theme/crimson_editor",
        dawn:"ace/theme/dawn",
        dreamweaver:"ace/theme/dreamweaver",
        eclipse:"ace/theme/eclipse",
        github:"ace/theme/github",
        iplastic:"ace/theme/iplastic",
        katzenmilch:"ace/theme/katzenmilch",
        kuroir:"ace/theme/kuroir",
        merbivore_sort:"ace/theme/merbivore_sort",
        sqlserver:"ace/theme/sqlserver",
        textmate:"ace/theme/textmate",
        tomorrow:"ace/theme/tomorrow",
        xcode:"ace/theme/xcode",
    };
}