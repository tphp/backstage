$.fn.copy_text_tips = function(msg, str, id){
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
                layer.tips('已复制', '#' + id);
            }
        } catch (e) {
        }
    }
}

$(function () {
    var js_input = $("input.js_input");
    var name = js_input.attr("name");
    if (name === 'copy') {
        layui.use(['element', 'layer'], function () {});
    }
    var copy = $("#copy");
    if(name != undefined && name != "") {
        $(".group-body i").click(function () {
            $(".group-body i").removeClass("select");
            var title = $(this).attr("title");
            js_input.val(title);
            $(this).addClass("select");

            if (name === 'copy') {
                copy.copy_text_tips("复制成功", title, $(this).attr("id"));
            }
        });

        var value = js_input.attr("value");
        if(value != undefined && value != ""){
            $(".group-body i[title='" + value + "']").addClass("select");
        }
    }

    $('_CLASS_').css("display", "block");

    function resizeset(){
        var gb = $(".group-body");
        var gbwidth = gb.width();
        var len = parseInt(gbwidth / 40);
        var setwidth = gbwidth / len;
        setwidth -= 21;
        gb.find("i").width(setwidth).height(setwidth);
    }

    resizeset();

    $(window).resize(function () {
        resizeset();
    });
});