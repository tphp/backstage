$(function () {
    layui.use(['form', 'layedit', 'laydate'], function () {
        $("textarea.js_ueditor").each(function () {
            var _this = $(this);
            var height = _this.height();
            var id = _this.attr('id');
            var config = {};
            if (height > 0) {
                config = {initialFrameHeight: height};
            }
            if (_this.val() == '') {
                var fname = _this.attr("name");
                if (g_field[fname] != undefined) {
                    _this.val(g_field[fname]);
                }
            }
            var editor = UE.getEditor(id, config);
            var disabled = _this.attr('disabled');
            if (disabled === 'disabled' || disabled === '') {
                editor.ready(function() {
                    editor.setDisabled();
                });
            }
        });
    });
});