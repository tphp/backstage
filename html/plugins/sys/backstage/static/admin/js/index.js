var bootstrap = function (a, b) {
    var c = {
        init: function () {
            this.load();
            this.bind()
        }, load: function () {
            var u = b.clientdata.get(["modulesTree"]);
            var v = "0";
            var t = u[v] || [];
            var e = a('<ul class="first-menu-list"></ul>');
            for (var n = 0, q = t.length; n < q; n++) {
                var o = t[n];
                var d = a("<li></li>");
                if (!!o.description) {
                    d.attr("title", o.description)
                }
                var s = '<a id="' + o.id + '" href="javascript:void(0);" class="menu-item">';
                s += '<i class="fa fa-' + o.icon + ' menu-item-icon"></i>';
                s += '<span class="menu-item-text">' + o.name + "</span>";
                s += "</a>";
                d.append(s);
                var z = u[o.id] || [];
                var g = a('<ul class="second-menu-list"></ul>');
                var y = false;
                for (var p = 0, A = z.length; p < A; p++) {
                    var w = z[p];
                    y = true;
                    var f = a("<li></li>");
                    if (!!w.description) {
                        f.attr("title", w.description)
                    }
                    var x = '<a id="' + w.id + '" href="javascript:void(0);" class="menu-item" >';
                    x += '<i class="fa fa-' + w.icon + ' menu-item-icon"></i>';
                    x += '<span class="menu-item-text">' + w.name + "</span>";
                    x += "</a>";
                    f.append(x);
                    var E = u[w.id] || [];
                    var k = a('<ul class="three-menu-list"></ul>');
                    var D = false;
                    for (var r = 0, F = E.length; r < F; r++) {
                        var B = E[r];
                        D = true;
                        var h = a("<li></li>");
                        h.attr("title", B.name);
                        var C = '<a id="' + B.id + '" href="javascript:void(0);" class="menu-item" >';
                        C += '<i class="fa fa-' + B.icon + ' menu-item-icon"></i>';
                        C += '<span class="menu-item-text">' + B.name + "</span>";
                        C += "</a>";
                        h.append(C);
                        k.append(h)
                    }
                    if (D) {
                        f.addClass("meun-had");
                        f.find("a").addClass("open").append('<span class="menu-item-arrow"><i class="fa fa-angle-left"></i></span>');
                        f.append(k)
                    }
                    g.append(f)
                }
                if (y) {
                    g.attr("data-value", o.id);
                    a("#second_menu_wrap").append(g)
                }
                e.append(d)
            }
            a("#frame_menu").html(e)
        }, bind: function () {
            a("#frame_menu").mCustomScrollbar({axis: "x", theme: "minimal-dark"});
            a("#second_menu_wrap").mCustomScrollbar({theme: "minimal-dark"});
            a("#frame_menu .menu-item").on("click", function () {
                var d = a(this);
                var g = d.attr("id");
                var f = b.clientdata.get(["modulesMap", g]);
                switch (f.target) {
                    case"iframe":
                        if (b.validator.isNotNull(f.url).code) {
                            b.frameTab.open(f)
                        }
                        break;
                    case"expand":
                        if (!d.hasClass("active")) {
                            a("#frame_menu .menu-item.active").removeClass("active");
                            d.addClass("active");
                            var e = a("#second_menu_wrap");
                            e.find(".second-menu-list").hide();
                            e.find('.second-menu-list[data-value="' + g + '"]').show()
                        }
                        break
                }
            });
            a("#second_menu_wrap .menu-item").on("click", function () {
                var d = a(this);
                var g = d.attr("id");
                var f = b.clientdata.get(["modulesMap", g]);
                switch (f.target) {
                    case"iframe":
                        if (b.validator.isNotNull(f.url).code) {
                            b.frameTab.open(f)
                        }
                        break;
                    case"expand":
                        var e = d.next();
                        if (e.is(":visible")) {
                            e.slideUp(500, function () {
                                d.removeClass("open")
                            })
                        } else {
                            e.slideDown(300, function () {
                                d.addClass("open")
                            })
                        }
                        break
                }
            });


            var a0 = a(".first-menu-list>li").eq(0).find("a");
            var f = b.clientdata.get(["modulesMap", a(a0).attr("id")]);
            if (f.target === 'expand') {
                a0.trigger("click");
            }

            a("#frame_menu_btn").on("click", function () {
                var d = a("body");
                var ck;
                if (d.hasClass("menu-closed")) {
                    d.removeClass("menu-closed");
                    ck = 'open';
                } else {
                    d.addClass("menu-closed");
                    ck = 'close';
                }
                $.cookie('menu_status', ck);
            });
            a("#second_menu_wrap a").hover(function () {
                if (a("body").hasClass("menu-closed")) {
                    var d = a(this).attr("id");
                    var e = a("#" + d + ">span").text();
                    layer.tips(e, a(this))
                }
            }, function () {
                if (a("body").hasClass("menu-closed")) {
                    layer.closeAll("tips")
                }
            })
        }
    };
    c.init()
};


$.rootUrl = root_url;

top.functiontpl = {};
(function (a, c) {
    var b = {};
    c.frameTab = {
        iframeId: "", init: function () {
            c.frameTab.bind()
        }, bind: function () {
            a(".frame-tabs-wrap").mCustomScrollbar({axis: "x", theme: "minimal-dark"})
        }, open: function (h, i) {
            var g = a("#frame_tabs_ul");
            var d = a("#frame_main");
            if (b[h.id] == undefined || b[h.id] == null) {
                if (c.frameTab.iframeId != "") {
                    g.find("#tab_" + c.frameTab.iframeId).removeClass("active");
                    d.find("#iframe_" + c.frameTab.iframeId).removeClass("active");
                    b[c.frameTab.iframeId] = 0
                }
                var j = c.frameTab.iframeId;
                c.frameTab.iframeId = h.id;
                b[c.frameTab.iframeId] = 1;
                var f = a('<li class="frame-tabItem active" id="tab_' + h.id + '" parent-id="' + j + '"  ><span><i class="fa fa-' + h.icon + '"></i>&nbsp;' + h.name + "</span></li>");
                if (!i) {
                    f.append('<span class="reomve" title="关闭窗口"></span>')
                }
                var e = a('<iframe class="frame-iframe active" id="iframe_' + h.id + '" frameborder="0" src="' + a.rootUrl + h.url + '"></iframe>');
                g.append(f);
                d.append(e);
                a(".frame-tabs-wrap").mCustomScrollbar("update");
                a(".frame-tabs-wrap").mCustomScrollbar("scrollTo", f);
                f.on("click", function () {
                    var k = a(this).attr("id").replace("tab_", "");
                    c.frameTab.focus(k)
                });
                f.find(".reomve").on("click", function () {
                    var k = a(this).parent().attr("id").replace("tab_", "");
                    c.frameTab.close(k);
                    return false
                });
                if (!!c.frameTab.opencallback) {
                    c.frameTab.opencallback()
                }
            } else {
                c.frameTab.focus(h.id)
            }
        }, focus: function (d) {
            if (b[d] == 0) {
                a("#tab_" + c.frameTab.iframeId).removeClass("active");
                a("#iframe_" + c.frameTab.iframeId).removeClass("active");
                b[c.frameTab.iframeId] = 0;
                a("#tab_" + d).addClass("active");
                a("#iframe_" + d).addClass("active");
                c.frameTab.iframeId = d;
                b[d] = 1;
                a(".frame-tabs-wrap").mCustomScrollbar("scrollTo", a("#tab_" + d));
                if (!!c.frameTab.opencallback) {
                    c.frameTab.opencallback()
                }
            }
        }, close: function (f) {
            delete b[f];
            var e = a("#tab_" + f);
            var d = e.prev();
            if (d.length < 1) {
                d = e.next()
            }
            e.remove();
            a("#iframe_" + f).remove();
            if (f == c.frameTab.iframeId && d.length > 0) {
                var g = d.attr("id").replace("tab_", "");
                d.addClass("active");
                a("#iframe_" + g).addClass("active");
                c.frameTab.iframeId = g;
                b[g] = 1;
                a(".frame-tabs").css("width", "10000px");
                a(".frame-tabs-wrap").mCustomScrollbar("update");
                a(".frame-tabs").css("width", "100%");
                a(".frame-tabs-wrap").mCustomScrollbar("scrollTo", d)
            } else {
                if (d.length < 1) {
                    c.frameTab.iframeId = ""
                }
                a(".frame-tabs").css("width", "10000px");
                a(".frame-tabs-wrap").mCustomScrollbar("update");
                a(".frame-tabs").css("width", "100%")
            }
            if (!!c.frameTab.closecallback) {
                c.frameTab.closecallback()
            }
        }, opencallback: false, closecallback: false
    };
    c.frameTab.init()
})(window.jQuery, top.functiontpl);

(function (a, b) {
    b.validator = {
        isNotNull: function (c) {
            var d = {code: true, msg: ""};
            c = a.trim(c);
            if (c == null || c == undefined || c.length == 0) {
                d.code = false;
                d.msg = "不能为空"
            }
            return d
        }
    }
})(window.jQuery, top.functiontpl);
(function (a, b) {
    a.extend(b, {
        layerConfirm: function (d, c) {
            top.layer.confirm(d, {btn: ["确认", "取消"], title: "提示", icon: 0, skin: "layer", shade: 0.1}, function (e) {
                c(true, e)
            }, function (e) {
                c(false, e);
                top.layer.close(e)
            })
        }
    })
})(window.jQuery, top.functiontpl);
(function (a, d) {
    var c = {success: 200, fail: 400, exception: 500};
    var b = {code: c.exception, info: "通信异常，请联系管理员！"};
    a.extend(d, {
        httpErrorLog: function (e) {
            d.log(e)
        }, httpCode: c, httpAsyncGet: function (f, e) {
            a.ajax({
                url: f, type: "GET", dataType: "json", async: true, cache: false, success: function (g) {
                    if (g.code == d.httpCode.exception) {
                        d.httpErrorLog(g.info);
                        g.info = "系统异常，请联系管理员！"
                    }
                    e(g)
                }, error: function (i, h, g) {
                    d.httpErrorLog(h);
                    e(b)
                }, beforeSend: function () {
                }, complete: function () {
                }
            })
        }
    })
})(window.jQuery, top.functiontpl);
(function (a, g) {
    var h = {no: -1, yes: 1, ing: 0, fail: 2};
    var d = {};
    var b = {};
    var c = {};

    function f(k) {
        var m = h.yes;
        for (var l in d) {
            var j = d[l];
            if (j.state == h.fail) {
                m = h.fail;
                break
            } else {
                if (j.state == h.no) {
                    m = h.ing;
                    j.init()
                } else {
                    if (j.state == h.ing) {
                        m = h.ing
                    }
                }
            }
        }
        if (m == h.yes) {
            k(true)
        } else {
            if (m == h.fail) {
                k(false)
            } else {
                setTimeout(function () {
                    f(k)
                }, 100)
            }
        }
    }

    function e(l, j) {
        var n = "";
        var m = j.length;
        if (m == undefined) {
            n = j[l]
        } else {
            for (var k = 0; k < m; k++) {
                if (l(j[k])) {
                    n = j[k];
                    break
                }
            }
        }
        return n
    }

    g.clientdata = {
        init: function (j) {
            f(function (k) {
                j(k);
                if (k) {
                    b.company.init()
                }
            })
        }, get: function (m) {
            var n = "";
            if (!m) {
                return n
            }
            var l = m.length;
            var j = c;
            for (var k = 0; k < l; k++) {
                n = e(m[k], j);
                if (n != "" && n != undefined) {
                    j = n
                } else {
                    break
                }
            }
            n = n || "";
            return n
        }
    };
    d.modules = {
        state: h.no, init: function () {
            d.modules.state = h.ing;
            g.httpAsyncGet(a.rootUrl + "/sys/json/menu", function (j) {
                if (j.code == g.httpCode.success) {
                    c.modules = j.data;
                    d.modules.toMap();
                    d.modules.state = h.yes
                } else {
                    c.modules = [];
                    d.modules.toMap();
                    d.modules.state = h.fail
                }
            })
        }, toMap: function () {
            var n = {};
            var m = {};
            var k = c.modules.length;
            for (var l = 0; l < k; l++) {
                var j = c.modules[l];
                //if (j.F_EnabledMark == 1) {
                    n[j.parent_id] = n[j.parent_id] || [];
                    n[j.parent_id].push(j);
                    m[j.id] = j
                //}
            }
            c.modulesTree = n;
            c.modulesMap = m
        }
    };
    d.userinfo = {
        state: h.no, init: function () {
            d.userinfo.state = h.ing;
            d.userinfo.state = h.fail;
        }
    };
    var i = {
        get: function (j) {
            if (localStorage) {
                return JSON.parse(localStorage.getItem(j)) || {}
            } else {
                return c[j] || {}
            }
        }, set: function (k, j) {
            if (localStorage) {
                localStorage.setItem(k, JSON.stringify(j))
            } else {
                return c[k] = j
            }
        }
    };
    b.company = {
        states: h.no, init: function () {
            if (b.company.states == h.no) {
                b.company.states = h.ing;
            }
        }, get: function (k) {
            b.company.init();
            if (b.company.states == h.ing) {
                setTimeout(function () {
                    b.company.get(k)
                }, 100)
            } else {
                var j = i.get("companyData").data || {};
                k.callback(j[k.key] || {}, k)
            }
        }, getAll: function (k) {
            if (b.company.states == h.ing) {
                setTimeout(function () {
                    b.company.getAll(k)
                }, 100)
            } else {
                var j = i.get("companyData").data || {};
                k.callback(j, k)
            }
        }
    };
    b.department = {
        states: h.no, init: function () {
            if (b.department.states == h.no) {
                b.department.states = h.ing;
            }
        }, get: function (k) {
            b.department.init();
            if (b.department.states == h.ing) {
                setTimeout(function () {
                    b.department.get(k)
                }, 100)
            } else {
                var j = i.get("departmentData").data || {};
                k.callback(j[k.key] || {}, k)
            }
        }, getAll: function (k) {
            if (b.department.states == h.ing) {
                setTimeout(function () {
                    b.department.getAll(k)
                }, 100)
            } else {
                var j = i.get("departmentData").data || {};
                k.callback(j, k)
            }
        }
    };
    b.user = {
        states: h.no, init: function () {
            if (b.user.states == h.no) {
                b.user.states = h.ing;
            }
        }, get: function (k) {
            b.user.init();
            if (b.user.states == h.ing) {
                setTimeout(function () {
                    b.user.get(k)
                }, 100)
            } else {
                var j = i.get("userData").data || {};
                k.callback(j[k.key] || {}, k)
            }
        }, getAll: function (k) {
            if (b.user.states == h.ing) {
                setTimeout(function () {
                    b.user.getAll(k)
                }, 100)
            } else {
                var j = i.get("userData").data || {};
                k.callback(j, k)
            }
        }
    };
    b.dataItem = {
        states: h.no, init: function () {
            if (b.dataItem.states == h.no) {
                b.dataItem.states = h.ing;
            }
        }, get: function (k) {
            b.dataItem.init();
            if (b.dataItem.states == h.ing) {
                setTimeout(function () {
                    b.dataItem.get(k)
                }, 100)
            } else {
                var j = i.get("dataItemData").data || {};
                k.callback(b.dataItem.find(k.key, j[k.code] || {}) || {}, k)
            }
        }, getAll: function (k) {
            if (b.dataItem.states == h.ing) {
                setTimeout(function () {
                    b.dataItem.getAll(k)
                }, 100)
            } else {
                var j = i.get("dataItemData").data || {};
                k.callback(j[k.code] || {}, k)
            }
        }, find: function (l, j) {
            var m = {};
            for (var k in j) {
                if (j[k].value == l) {
                    m = j[k];
                    break
                }
            }
            return m
        }
    };
    b.db = {
        states: h.no, init: function () {
            if (b.db.states == h.no) {
                b.db.states = h.ing;
            }
        }, get: function (k) {
            b.db.init();
            if (b.db.states == h.ing) {
                setTimeout(function () {
                    b.db.get(k)
                }, 100)
            } else {
                var j = i.get("dbData").data || {};
                k.callback(j[k.key] || {}, k)
            }
        }, getAll: function (k) {
            if (b.db.states == h.ing) {
                setTimeout(function () {
                    b.db.getAll(k)
                }, 100)
            } else {
                var j = i.get("dbData").data || {};
                k.callback(j, k)
            }
        }
    };
    b.sourceData = {
        states: {}, get: function (k) {
            if (b.sourceData.states[k.code] == undefined || b.sourceData.states[k.code] == h.no) {
                b.sourceData.states[k.code] = h.ing;
                b.sourceData.load(k.code)
            }
            if (b.sourceData.states[k.code] == h.ing) {
                setTimeout(function () {
                    b.sourceData.get(k)
                }, 100)
            } else {
                var j = i.get("sourceData_" + k.code).data || [];
                if (!!j) {
                    k.callback(b.sourceData.find(k.key, k.keyId, j) || {}, k)
                } else {
                    k.callback({}, k)
                }
            }
        }, getAll: function (k) {
            if (b.sourceData.states[k.code] == undefined || b.sourceData.states[k.code] == h.no) {
                b.sourceData.states[k.code] = h.ing;
                b.sourceData.load(k.code)
            }
            if (b.sourceData.states[k.code] == h.ing) {
                setTimeout(function () {
                    b.sourceData.getAll(k)
                }, 100)
            } else {
                if (b.sourceData.states[k.code] == h.yes) {
                    var j = i.get("sourceData_" + k.code).data || [];
                    if (!!j) {
                        k.callback(j, k)
                    } else {
                        k.callback({}, k)
                    }
                }
            }
        }, load: function (j) {
        }, find: function (m, n, j) {
            var p = {};
            for (var k = 0, o = j.length; k < o; k++) {
                if (j[k][n] == m) {
                    p = j[k];
                    break
                }
            }
            return p
        }
    };
    b.custmerData = {
        states: {}, get: function (k) {
            if (b.custmerData.states[k.url] == undefined || b.custmerData.states[k.url] == h.no) {
                b.custmerData.states[k.url] = h.ing;
                b.custmerData.load(k.url)
            }
            if (b.custmerData.states[k.url] == h.ing) {
                setTimeout(function () {
                    b.custmerData.get(k)
                }, 100)
            } else {
                var j = c[k.url] || [];
                if (!!j) {
                    k.callback(b.custmerData.find(k.key, k.keyId, j) || {}, k)
                } else {
                    k.callback({}, k)
                }
            }
        }, getAll: function (k) {
            if (b.custmerData.states[k.url] == undefined || b.custmerData.states[k.url] == h.no) {
                b.custmerData.states[k.url] = h.ing;
                b.custmerData.load(k.url)
            }
            if (b.custmerData.states[k.url] == h.ing) {
                setTimeout(function () {
                    b.custmerData.get(k)
                }, 100)
            } else {
                var j = c[k.url] || [];
                if (!!j) {
                    k.callback(j, k)
                } else {
                    k.callback([], k)
                }
            }
        }, find: function (m, n, j) {
            var p = {};
            for (var k = 0, o = j.length; k < o; k++) {
                if (j[k][n] == m) {
                    p = j[k];
                    break
                }
            }
            return p
        }
    }
})(window.jQuery, top.functiontpl);

var ina, inb;
var settimeloadout;
function settimeload(){
    var u = inb.clientdata.get(["modulesTree"]);
    if(u != undefined && u != '') {
        bootstrap(ina, inb);
        clearTimeout(settimeloadout);
    }
}

(function (a, b) {
    var c = {
        init: function () {
            if (a("body").hasClass("IE") || a("body").hasClass("InternetExplorer")) {
                a("#loadbg").append('<img src="' + top.$.rootUrl + '/Content/images/ie-loader.gif" style="position: absolute;top: 0;left: 0;right: 0;bottom: 0;margin: auto;vertical-align: middle;">');
                Pace.stop();
            } else {
                Pace.on("done", function () {
                    a("#loadbg").fadeOut();
                    Pace.options.target = "#functiontplpacenone";
                })
            }
            toastr.options = {
                closeButton: true,
                debug: false,
                newestOnTop: true,
                progressBar: false,
                positionClass: "toast-top-center",
                preventDuplicates: false,
                onclick: null,
                showDuration: "300",
                hideDuration: "1000",
                timeOut: "3000",
                extendedTimeOut: "1000",
                showEasing: "swing",
                hideEasing: "linear",
                showMethod: "fadeIn",
                hideMethod: "fadeOut"
            };
            b.frameTab.open({
                id: "0",
                icon: "fa fa-desktop",
                name: "首页模板",
                url: "/sys/index/default"
            }, true);
            b.clientdata.init(function () {
                a("#loginout_btn").on("click", function () {
                    window.location.href = "/sys/user/login/out";
                });
                a("#set_password").on("click", function () {
                    b.frameTab.open({
                        id: "10001",
                        icon: "fa fa-edit",
                        name: "修改密码",
                        url: "/sys/user/reset/password.handle"
                    })
                });
                a("#set_userinfo").on("click", function () {
                    b.frameTab.open({
                        id: "10002",
                        icon: "fa fa-user-o",
                        name: "个人中心",
                        url: "/sys/user/userinfo.handle"
                    })
                });
                ina = a;
                inb = b;
                settimeloadout = setInterval(settimeload, 300);
                if (a("body").hasClass("IE") || a("body").hasClass("InternetExplorer")) {
                    a("#loadbg").fadeOut()
                }
            });
            c.fullScreenInit();
        }, fullScreenInit: function () {
            a("#fullscreen_btn").on("click", function () {
                if (!a(this).attr("fullscreen")) {
                    a(this).attr("fullscreen", "true");
                    c.requestFullScreen()
                } else {
                    a(this).removeAttr("fullscreen");
                    c.exitFullscreen()
                }
            });

            layui.use(['layer'], function() {
                //清除缓存
                a("#refresh_btn").on("click", function () {
                    $.ajax({
                        url: $(this).attr("data-uri"),
                        success: function (result) {
                            if(result.code == 1){
                                layer.msg(result.msg, {icon: 1});
                            }else{
                                layer.msg(result.msg, {icon: 2});
                            }
                        }
                    });
                });
            });

            //刷新当前页面
            a("#flush_btn").on("click", function () {
                $("#frame_main>iframe.active")[0].contentWindow.location.reload();
            });
        }, requestFullScreen: function () {
            var d = document.documentElement;
            if (d.requestFullscreen) {
                d.requestFullscreen()
            } else {
                if (d.mozRequestFullScreen) {
                    d.mozRequestFullScreen()
                } else {
                    if (d.webkitRequestFullScreen) {
                        d.webkitRequestFullScreen()
                    }
                }
            }
        }, exitFullscreen: function () {
            var d = document;
            if (d.exitFullscreen) {
                d.exitFullscreen()
            } else {
                if (d.mozCancelFullScreen) {
                    d.mozCancelFullScreen()
                } else {
                    if (d.webkitCancelFullScreen) {
                        d.webkitCancelFullScreen()
                    }
                }
            }
        }
    };
    a(function () {
        c.init()
    })
})(window.jQuery, top.functiontpl);