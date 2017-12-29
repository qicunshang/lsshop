var order_id;
$(function() {
	var e = getCookie("key");
	if (!e) {
		window.location.href = WapSiteUrl + "/tmpl/member/login.html"
	}
	$.getJSON(ApiUrl + "/index.php?act=member_refund&op=refund_all_form", {
		key: e,
		order_id: getQueryString("order_id")
	}, function(a) {
		a.datas.WapSiteUrl = WapSiteUrl;
		$("#order-info-container").html(template.render("order-info-tmpl", a.datas));
		order_id = a.datas.order.order_id;
		$("#allow_refund_amount").val("￥" + a.datas.order.allow_refund_amount);
		$('input[name="refund_pic"]').ajaxUploadImage({
			url: ApiUrl + "/index.php?act=member_refund&op=upload_pic",
			data: {
				key: e
			},
			start: function(e) {
				e.parent().after('<div class="upload-loading"><i></i></div>');
				e.parent().siblings(".pic-thumb").remove()
			},
			success: function(e, a) {
				checkLogin(a.login);
				if (a.datas.error) {
					e.parent().siblings(".upload-loading").remove();
					$.sDialog({
						skin: "red",
						content: "图片尺寸过大！",
						okBtn: false,
						cancelBtn: false
					});
					return false
				}
				e.parent().after('<div class="pic-thumb"><img src="' + a.datas.pic + '"/></div>');
				e.parent().siblings(".upload-loading").remove();
				e.parents("a").next().val(a.datas.file_name)
			}
		});
		$(".btn-l").click(function() {
			var a = $("form").serializeArray();
			var r = {};
			r.key = e;
			r.order_id = order_id;
			for (var n = 0; n < a.length; n++) {
				r[a[n].name] = a[n].value
			}
			if (r.buyer_message.length == 0) {
				$.sDialog({
					skin: "red",
					content: "请填写退款说明",
					okBtn: false,
					cancelBtn: false
				});
				return false
			}
			$.ajax({
				type: "post",
				url: ApiUrl + "/index.php?act=member_refund&op=refund_all_post",
				data: r,
				dataType: "json",
				async: false,
				success: function(e) {
					checkLogin(e.login);
					if (e.datas.error) {
						$.sDialog({
							skin: "red",
							content: e.datas.error,
							okBtn: false,
							cancelBtn: false
						});
						return false
					}
					window.location.href = WapSiteUrl + "/tmpl/member/member_refund.html"
				}
			})
		})
	})
});