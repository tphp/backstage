layui.use(['form', 'laydate', 'element'], function() {
    var laydate = layui.laydate;
    $(".js_input_time").each(function () {
        laydate.render({
            elem: '#' + $(this).attr("id"),
            type: 'datetime'
        });
    });
});