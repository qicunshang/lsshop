$(function() {
    var e = getCookie("key");
    if (!e) {
        window.location.href = WapSiteUrl + "/tmpl/member/login.html";
        return
    }
    loadSeccode();
    $("#refreshcode").bind("click",
    function() {
        loadSeccode()
    });
    $("#mobile").on("blur",
    function() {
        if ($(this).val() != "" && !/^-?(?:\d+|\d{1,3}(?:,\d{3})+)?(?:\.\d+)?$/.test($(this).val())) {
            $(this).val(/\d+/.exec($(this).val()))
        }
    });
    $.ajax({
        type: "get",
        url: ApiUrl + "/index.php?act=member_account&op=get_mobile_info",
        data: {
            key: e
        },
        dataType: "json",
        success: function(e) {
            if (e.datas.state) {
                $("#mobile").val(e.datas.mobile)
            }
        }
    });
    $.sValid.init({
        rules: {
            captcha: {
                required: true,
                minlength: 4
            },
            mobile: {
                required: true,
                mobile: true
            }
        },
        messages: {
            captcha: {
                required: "请填写图形验证码",
                minlength: "图形验证码不正确"
            },
            mobile: {
                required: "请填写手机号",
                mobile: "手机号码不正确"
            }
        },
        callback: function(e, a, t) {
            if (e.length > 0) {
                var o = "";
                $.map(a,
                function(e, a) {
                    o += "<p>" + e + "</p>"
                });
                errorTipsShow(o)
            } else {
                errorTipsHide()
            }
        }
    });
    $("#send").click(function() {
        if ($.sValid()) {
            var a = $.trim($("#mobile").val());
            var t = $.trim($("#captcha").val());
            var o = $.trim($("#codekey").val());
            $.ajax({
                type: "post",
                url: ApiUrl + "/index.php?act=member_account&op=bind_mobile_step1",
                data: {
                    key: e,
                    mobile: a,
                    captcha: t,
                    codekey: o
                },
                dataType: "json",
                success: function(e) {
                    if (e.code == 200) {
                        $("#send").hide();
                        $("#auth_code").removeAttr("readonly");
                        $(".code-countdown").show().find("em").html(e.datas.sms_time);
                        $.sDialog({
                            skin: "block",
                            content: "短信验证码已发出",
                            okBtn: false,
                            cancelBtn: false
                        });
                        var a = setInterval(function() {
                            var e = $(".code-countdown").find("em");
                            var t = parseInt(e.html() - 1);
                            if (t == 0) {
                                $("#send").show();
                                $(".code-countdown").hide();
                                clearInterval(a);
                                $("#codeimage").attr("src", ApiUrl + "/index.php?act=seccode&op=makecode&k=" + $("#codekey").val() + "&t=" + Math.random());
                                $("#captcha").val("")
                            } else {
                                e.html(t)
                            }
                        },
                        1e3)
                    } else {
                        errorTipsShow("<p>" + e.datas.error + "</p>");
                        $("#codeimage").attr("src", ApiUrl + "/index.php?act=seccode&op=makecode&k=" + $("#codekey").val() + "&t=" + Math.random());
                        $("#captcha").val("")
                    }
                }
            })
        }
    });
    $("#nextform").click(function() {
        if (!$(this).parent().hasClass("ok")) {
            return false
        }
        var a = $.trim($("#auth_code").val());
        if (a) {
            $.ajax({
                type: "post",
                url: ApiUrl + "/index.php?act=member_account&op=bind_mobile_step2",
                data: {
                    key: e,
                    auth_code: a
                },
                dataType: "json",
                success: function(e) {
                    if (e.code == 200) {
                        $.sDialog({
                            skin: "block",
                            content: "绑定成功",
                            okBtn: false,
                            cancelBtn: false
                        });
                        setTimeout("location.href = WapSiteUrl+'/tmpl/member/member_account.html'", 2e3)
                    } else {
                        errorTipsShow("<p>" + e.datas.error + "</p>")
                    }
                }
            })
        }
    })
});