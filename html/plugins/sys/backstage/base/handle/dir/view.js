/**
 * @static admin/js/dir.js
 */

$(function () {
    layui.use(['tree', 'util'], function(){
        $(".js_btn_dir").unbind().click(function () {
            var _t = $(this);
            var disabled = _t.attr('disabled');
            if (disabled === 'disabled' || disabled === '') {
                return;
            }
            var _t_parent = _t.parent();
            var _t_top = _t_parent.parent();
            var _t_lfl = _t_top.find(".layui-form-label");
            var _t_jnr = _t_lfl.find(".js_name_remark");
            var _t_title = '';
            if(_t_jnr.size() > 0){
                _t_title = _t_jnr.html();
            }else{
                _t_title = _t_lfl.html();
            }
            if(typeof dir_fun === 'function'){
                dir_select_tree(_t, _t_parent.find("input"), undefined, _t_title, undefined, undefined, undefined, dir_fun);
            }else{
                dir_select_tree(_t, _t_parent.find("input"), undefined, _t_title);
            }
        });
    });
});