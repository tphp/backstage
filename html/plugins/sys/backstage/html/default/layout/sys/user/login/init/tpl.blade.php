<!DOCTYPE html>
@php
    $title = '密码初始化设置';
    $color = $_DC_['color'];
    if(empty($color[1])){
        $c = "2e99d4";
        $cd = "4176e7";
    }else{
        $c = $color[1];
        $cd = $color[0];
    }
    $plu->css([
        'js/layui/css/layui.css',
        'admin/css/admin.css',
        'admin/css/login.css',
        'admin/css/font-awesome.min.css',
    ])->js([
        '@js/layui/layui.js',
        '@js/jquery/jquery.min.js'
    ]);

    $bUsername = $_DC_['backstageusername'];
    if (empty($bUsername) || !is_string($bUsername)) {
        $bUsername = '用户名';
    }

@endphp
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>{{preg_replace("/<[^>]*>/is", "", $title)}}</title>
    <meta name="renderer" content="webkit" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=0" />
    <style>
        .layadmin-user-login-header h2 span{
            color: #F33;
            margin-left: 10px;
        }
    </style>
</head>
<body class="layui-layout-body">
<div id="LAY_app">
    <div class="layadmin-user-login" id="LAY-user-login" style="display: none;">

        <div class="layadmin-user-login-main">
            <div class="layadmin-user-login-box layadmin-user-login-header">
                <h2 style="color:#{{$cd}}">{!!$title!!}</h2>
            </div>
            <div class="layadmin-user-login-box layadmin-user-login-body layui-form">
                <form class="layui-form" id="login">
                    <div class="layui-form-item">
                        <label class="layadmin-user-login-icon layui-icon layui-icon-username" for="LAY-user-login-username"></label>
                        <input type="text" lay-verify="required" autocomplete="off" placeholder="{{ $bUsername }}" class="layui-input" value="admin" disabled="disabled">
                    </div>
                    <div class="layui-form-item">
                        <label class="layadmin-user-login-icon layui-icon layui-icon-password" for="LAY-user-login-password"></label>
                        <input type="password" name="password" lay-verify="required" autocomplete="off" placeholder="密码" class="layui-input">
                    </div>
                    <div class="layui-form-item">
                        <label class="layadmin-user-login-icon layui-icon layui-icon-password" for="LAY-user-login-password"></label>
                        <input type="password" name="password_confirm" lay-verify="required" autocomplete="off" placeholder="确定密码" class="layui-input">
                    </div>
                    <div class="layui-form-item">
                        <button class="layui-btn layui-btn-fluid" lay-submit="" lay-filter="login" style="background-color: #{{$c}}">设置并登入</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

</div>

<script>
    //如果是子窗口则从父窗口跳转到登录页面
    if(parent.layer != undefined){
        parent.window.location.href = window.location.href;
    }
    layui.use(['layer', 'form'], function () {
        var layer = layui.layer,
            $ = layui.jquery,
            form = layui.form;
        $(window).on('load', function () {
            form.on('submit(login)', function () {
                $.ajax({
                    url: "/sys/user/login/init",
                    data: $('#login').serialize(),
                    type: 'post',
                    async: false,
                    success: function (res) {
                        layer.msg(res.msg, {offset: '50px', anim: 1});
                        if (res.code == 1) {
                            setTimeout(function () {
                                location.href = res.url;
                            }, 1500);
                        } else {
                            $('#captcha').click();
                        }
                    }
                })
                return false;
            });
            var uname = $("input[name='username']");
            var password = $("input[name='password']");
            var password_confirm = $("input[name='password_confirm']");
            if (uname.val() == "") {
                uname.focus();
            } else if(uname.val() == "") {
                $("input[name='password']").focus();
            }
        });
    });
</script>

<div class="layui-layer-move"></div>
</body>
</html>
