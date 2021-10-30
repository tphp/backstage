function set_trees_config(id, domain_path) {
    layui.config({
        base: domain_path + 'js/layui/lay/modules/',
    }).extend({
        authtree: 'authtree',
    });

    function set_json_disabled(json) {
        for (var i in json) {
            json[i]['disabled'] = true;
            if (json[i].list !== undefined) {
                set_json_disabled(json[i].list);
            }
        }
    }

    layui.use(['jquery', 'authtree', 'form', 'layer', 'element'], function () {
        var $ = layui.jquery;
        var authtree = layui.authtree;
        $(".js_input_trees").each(function () {
            var _this = $(this);
            var id = _this.attr("id");
            var name = _this.attr("name");
            var json = JSON.parse($(this).attr("data-json"), true);
            var idstr = "#" + id;
            var disabled = _this.attr('disabled');
            if (disabled === 'disabled' || disabled === '') {
                set_json_disabled(json);
            }
            authtree.render(idstr, json, {
                inputname: name + '[]',
                layfilter: 'lay-check-auth',
                autowidth: true,
                openall: true
            });
        });
    });
}