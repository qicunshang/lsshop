$(function() {
	var e = getQueryString("mobile");
	var c = getQueryString("captcha");
	var a = getQueryString("codekey");
	$("#usermobile").html(e);
	send_sms(e, c, a);
	$("#again").click(function() {
		c = $("#captcha").val();
		a = $("#codekey").val();
		send_sms(e, c, a)
	});
	$("#find_password_code").click(function() {
		if (!$(this).parent().hasClass("ok")) {
			return false
		}
		var c = $("#mobilecode").val();
		if (c.length == 0) {
			errorTipsShow("<p>请填写验证码<p>")
		}
		check_sms_captcha(e, c);
		return false
	});
	loadSeccode();
	$("#refreshcode").bind("click", function() {
		loadSeccode()
	})
});

function send_sms(e, c, a) {
	$.getJSON(ApiUrl + "/index.php?act=connect&op=get_sms_captcha", {
		type: 3,
		phone: e,
		sec_val: c,
		sec_key: a
	}, function(e) {
		if (!e.datas.error) {
			$.sDialog({
				skin: "green",
				content: "发送成功",
				okBtn: false,
				cancelBtn: false
			});
			$(".code-again").hide();
			$(".code-countdown").show().find("em").html(e.datas.sms_time);
			var c = setInterval(function() {
				var e = $(".code-countdown").find("em");
				var a = parseInt(e.html() - 1);
				if (a == 0) {
					$(".code-again").show();
					$(".code-countdown").hide();
					clearInterval(c)
				} else {
					e.html(a)
				}
			}, 1e3)
		} else {
			loadSeccode();
			errorTipsShow("<p>" + e.datas.error + "<p>")
		}
	})
}

function check_sms_captcha(e, c) {
	$.getJSON(ApiUrl + "/index.php?act=connect&op=check_sms_captcha", {
		type: 3,
		phone: e,
		captcha: c
	}, function(a) {
		if (!a.datas.error) {
			window.location.href = "find_password_password.html?mobile=" + e + "&captcha=" + c
		} else {
			loadSeccode();
			errorTipsShow("<p>" + a.datas.error + "<p>")
		}
	})
}