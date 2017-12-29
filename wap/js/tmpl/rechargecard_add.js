$(function() {
	var e = getCookie("key");
	if (!e) {
		window.location.href = WapSiteUrl + "/tmpl/member/login.html";
		return
	}
	loadSeccode();
	$("#refreshcode").bind("click", function() {
		loadSeccode()
	});
	$.sValid.init({
		rules: {
			rc_sn: "required",
			captcha: "required"
		},
		messages: {
			rc_sn: "请输入平台充值卡号",
			captcha: "请填写验证码"
		},
		callback: function(e, r, a) {
			if (e.length > 0) {
				var c = "";
				$.map(r, function(e, r) {
					c += "<p>" + e + "</p>"
				});
				errorTipsShow(c)
			} else {
				errorTipsHide()
			}
		}
	});
	$("#saveform").click(function() {
		if (!$(this).parent().hasClass("ok")) {
			return false
		}
		if ($.sValid()) {
			var r = $.trim($("#rc_sn").val());
			var a = $.trim($("#captcha").val());
			var c = $.trim($("#codekey").val());
			$.ajax({
				type: "post",
				url: ApiUrl + "/index.php?act=member_fund&op=rechargecard_add",
				data: {
					key: e,
					rc_sn: r,
					captcha: a,
					codekey: c
				},
				dataType: "json",
				success: function(e) {
					if (e.code == 200) {
						location.href = WapSiteUrl + "/tmpl/member/rechargecardlog_list.html"
					} else {
						loadSeccode();
						errorTipsShow("<p>" + e.datas.error + "</p>")
					}
				}
			})
		}
	})
});