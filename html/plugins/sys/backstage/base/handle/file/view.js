/**
 * @static js/jquery/upload/vendor/jquery.ui.widget.js
 * @static js/jquery/upload/jquery.iframe-transport.js
 * @static js/jquery/upload/jquery.fileupload.js
 */

$(function () {
    layui.use(['form', 'laydate', 'element'], function() {
        function getImageWidth(url,callback){
            var img = new Image();
            img.src = url;

            // 如果图片被缓存，则直接返回缓存数据
            if(img.complete){
                callback(img.width, img.height);
            }else{
                // 完全加载完毕的事件
                img.onload = function(){
                    callback(img.width, img.height);
                }
            }
        }

        var chars = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
        function getRadom(n) {
            var res = "";
            for(var i = 0; i < n; i++) {
                var id = Math.ceil(Math.random() * 35);
                res += chars[id];
            }
            return res;
        }

        $(".js_btn_image_or_file").unbind().click(function () {
            var _this = $(this);
            var parent = _this.parent();
            var ipt = parent.find(".js_image_or_file");
            var iss = parent.parent().find(".js_image_show");
            var issa = iss.find(">a");
            var isss = iss.find(">span");
            var file_upload = parent.find(".file_upload");
            var isss_text = isss.html();
            var type = _this.attr("data-type");
            if(type == 'image') { //上传图片
                file_upload.fileupload({
                    dataType: 'json',
                    //设置进度条
                    progressall: function () {
                        layer.load(1);
                    },
                    //上传完成之后的操作，显示在img里面
                    done: function (e, data) {
                        layer.closeAll('loading');
                        if(data.result.code == 0){
                            isss.html(isss_text);
                            if(ipt.val().trim() === '') {
                                iss.hide();
                            }
                            layer.msg(data.result.msg, {icon: 2});
                        }else {
                            var texts = data.result.data;
                            var i = 0;
                            for (var key in texts) {
                                var text = texts[key];
                                if (i <= 0) {
                                    parent.find(".js_image_or_file").val(text);
                                    issa.attr('href', text).find("img").attr('src', text + "?t=" + getRadom(6));

                                    getImageWidth(text, function (m_w, m_h) {
                                        if (m_w > 0 && m_h > 0) {
                                            iss.show();
                                            isss.html(m_w + " x " + m_h + " pixels");
                                        }else{
                                            iss.hide();
                                        }
                                    });
                                }
                                $("#" + key).val(text);
                                i++;
                            }
                        }
                    },
                    fail: function () {
                        layer.closeAll('loading');
                        if(ipt.val().trim() === '') {
                            iss.hide();
                        }
                        layer.msg("上传错误，请重试！", {icon: 2});
                    }
                });
            }else{ //上传文件
                file_upload.fileupload({
                    dataType: 'json',
                    //设置进度条
                    progressall: function () {
                        layer.load(1);
                    },
                    //上传完成之后的操作，显示在img里面
                    done: function (e, data) {
                        if(data.result.code == 0){
                            layer.msg(data.result.msg, {icon: 2});
                        }else {
                            var texts = data.result.data;
                            for (var key in texts) {
                                var text = texts[key];
                                parent.find(".js_image_or_file").val(text);
                                break;
                            }
                        }
                        layer.closeAll('loading');
                    },
                    fail: function () {
                        layer.closeAll('loading');
                        layer.msg("上传错误，请重试！", {icon: 2});
                    }
                });
            }
            file_upload.trigger('click');
            return false;
        });

        function setImage(obj, is_init_url) {
            var name = $(obj).attr("name");
            var iof = $(".js_image_or_file[name='" + name + "']");
            var dval = iof.attr("data-value");
            var val = "";
            if(is_init_url) {
                val = g_field[name];
            }else{
                val = iof.val();
            }
            iof.attr("data-value", val);
            var iss = iof.parent().parent().find(".js_image_show");
            if(val != dval) {
                var issa = iss.find(">a");
                var isss = iss.find(">span");
                iss.hide();
                if(val != ''){
                    issa.attr('href', val).find("img").attr('src', val + "?t=" + getRadom(6));
                    getImageWidth(val, function (m_w, m_h) {
                        if (m_w > 0 && m_h > 0) {
                            iss.show();
                            isss.html(m_w + " x " + m_h + " pixels");
                            issa.show();
                        }
                    });
                }
            }
        }
        $(".js_btn_image_or_file").each(function () {
            var _this = $(this);
            if(_this.attr('data-type') == 'image'){
                var jiof = _this.parent().find(".js_image_or_file");
                jiof.each(function () {
                    setImage(this, true);
                });
                jiof.keyup(function () {
                    setImage(this);
                }).mouseup(function () {
                    setImage(this);
                });
            }
        });
    });
});