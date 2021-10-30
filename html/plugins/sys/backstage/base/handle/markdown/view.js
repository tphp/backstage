/**
 * @static js/markdown/css/editormd.min.css
 * @import css: handle/markdown
 * @static js/markdown/editormd.min.js
 */

function set_markdown_config(id, domain_path) {
    $(function() {
        function iframe_div() {
            layui.use(['form', 'element'], function () {
                handle();
            });
        }

        if(parent.$(".layui-layer-loading").size() > 0) {
            var timeout = "";
            function iframe_show() {
                clearTimeout(timeout);
                if (parent.$(".layui-layer-loading").size() <= 0) {
                    iframe_div();
                } else {
                    timeout = setTimeout(iframe_show, 10);
                }
            }
            iframe_show();
        }else{
            iframe_div();
        }

        var lfi = $(".layui-form-item");
        var md_size = 0;
        var mdeditors = [];
        var others = [];
        var default_h = 300;
        var tab_h;
        if($("ul.layui-tab-title").size() > 0){
            tab_h = 25;
        }else{
            tab_h = 60;
        }
        function md_resize() {
            var win_h = $(window).height();
            var other_h = 0;
            for(var o in others){
                other_h += others[o].height() + 15;
            }
            var set_h = win_h - other_h - tab_h;
            if(md_size > 0){
                set_h = parseInt(set_h / md_size);
            }
            if(set_h < default_h){
                set_h = default_h;
            }
            for(var md in mdeditors){
                try{
                    mdeditors[md].height(set_h);
                }catch (e) {
                    // TODO
                }
            }
        }
        function md_resize_width() {
            for(var md in mdeditors){
                try{
                    mdeditors[md].width("calc(100% - 2px)");
                }catch (e) {
                    // TODO
                }
            }
        }

        function handle() {
            layui.use(['form', 'layedit', 'laydate', 'element'], function () {
                lfi.each(function () {
                    var _this = $(this);
                    var js_markdown = _this.find(".js_markdown");
                    if(js_markdown.size() > 0) {
                        var id = js_markdown.attr('id');
                        var disabled = _this.find("textarea").attr('disabled');
                        var read_only = false;
                        if (disabled === 'disabled' || disabled === '') {
                            read_only = true;
                        }
                        var mdeditor = editormd(id, {
                            width: "calc(100% - 2px)",
                            height: default_h,
                            syncScrolling: "single",
                            path: domain_path + "js/markdown/lib/",
                            toolbarAutoFixed: false,
                            imageUpload : true,
                            imageFormats : ["jpg", "jpeg", "gif", "png", "bmp", "webp"],
                            readOnly: read_only,
                            imageUploadURL : "/sys/upload/markdown",
                            onfullscreen : function() {
                                md_resize_width();
                            },
                            onfullscreenExit : function() {
                                md_resize_width();
                            }
                        });
                        mdeditors.push(mdeditor);
                        md_size ++;
                    }else{
                        others.push(_this);
                    }
                });
                if(md_size > 0) {
                    md_resize();
                    $(window).resize(function () {
                        md_resize();
                    });
                }
            });
        }
    });
}