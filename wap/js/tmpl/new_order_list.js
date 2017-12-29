var page = pagesize;
var curpage = 1;
var hasMore = true;
var footer = false;
var reset = true;
var orderKey = "";
$(function() {
	var e = getCookie("key");
	if (!e) {
		window.location.href = WapSiteUrl + "/tmpl/member/login.html"
	}
	if (getQueryString("data-state") != "") {
		$("#filtrate_ul").find("li").has('a[data-state="' + getQueryString("data-state") + '"]').addClass("selected").siblings().removeClass("selected")
	}
	$("#search_btn").click(function() {
		reset = true;
		t()
	});
	$("#fixed_nav").waypoint(function() {
		$("#fixed_nav").toggleClass("fixed")
	}, {
		offset: "50"
	});

	function t() {
		if (reset) {
			curpage = 1;
			hasMore = true
		}
		$(".loading").remove();
		if (!hasMore) {
			return false
		}
		hasMore = false;
		var t = $("#filtrate_ul").find(".selected").find("a").attr("data-state");
		var r = $("#order_key").val();
		$.ajax({
			type: "post",
			url: ApiUrl + "/index.php?act=member_order&op=order_list&page=" + page + "&curpage=" + curpage,
			data: {
				key: e,
				state_type: t,
				order_key: r
			},
			dataType: "json",
			success: function(e) {
				checkLogin(e.login);
				curpage++;
				hasMore = e.hasmore;
				if (e.datas.order_group_list.length <= 0) {
					$("#footer").addClass("posa")
				} else {
					$("#footer").removeClass("posa")
				}
				var t = e;
				t.WapSiteUrl = WapSiteUrl;
				t.ApiUrl = ApiUrl;
				t.key = getCookie("key");
				template.helper("$getLocalTime", function(e) {
					var t = new Date(parseInt(e) * 1e3);
					var r = "";
					r += t.getFullYear() + "年";
					r += t.getMonth() + 1 + "月";
					r += t.getDate() + "日 ";
					r += t.getHours() + ":";
					r += t.getMinutes();
					return r
				});
				template.helper("p2f", function(e) {
					return (parseFloat(e) || 0).toFixed(2)
				});
				template.helper("parseInt", function(e) {
					return parseInt(e)
				});
				var r = template.render("order-list-tmpl", t);
				if (reset) {
					reset = false;
					$("#order-list").html(r)
				} else {
					$("#order-list").append(r)
				}
			}
		})
	}
	$("#order-list").on("click", ".cancel-order", r);
	$("#order-list").on("click", ".delete-order", o);
	$("#order-list").on("click", ".sure-order", n);
	$("#order-list").on("click", ".evaluation-order", l);
	$("#order-list").on("click", ".evaluation-again-order", d);
	$("#order-list").on("click", ".viewdelivery-order", c);
	$("#order-list").on("click", ".check-payment", function() {
		var e = $(this).attr("data-paySn");
		toPay(e, "member_payment", "pay");
		return false
	});

	function r() {
		var e = $(this).attr("order_id");
		$.sDialog({
			content: "确定取消订单？",
			okFn: function() {
				a(e)
			}
		})
	}

	function a(r) {
		$.ajax({
			type: "post",
			url: ApiUrl + "/index.php?act=member_order&op=order_cancel",
			data: {
				order_id: r,
				key: e
			},
			dataType: "json",
			success: function(e) {
				if (e.datas && e.datas == 1) {
					reset = true;
					t()
				} else {
					$.sDialog({
						skin: "red",
						content: e.datas.error,
						okBtn: false,
						cancelBtn: false
					})
				}
			}
		})
	}

	function o() {
		var e = $(this).attr("order_id");
		$.sDialog({
			content: "是否移除订单？<h6>电脑端订单回收站可找回订单！</h6>",
			okFn: function() {
				i(e)
			}
		})
	}

	function i(r) {
		$.ajax({
			type: "post",
			url: ApiUrl + "/index.php?act=member_order&op=order_delete",
			data: {
				order_id: r,
				key: e
			},
			dataType: "json",
			success: function(e) {
				if (e.datas && e.datas == 1) {
					reset = true;
					t()
				} else {
					$.sDialog({
						skin: "red",
						content: e.datas.error,
						okBtn: false,
						cancelBtn: false
					})
				}
			}
		})
	}

	function n() {
		var e = $(this).attr("order_id");
		$.sDialog({
			content: "确定收到了货物吗？",
			okFn: function() {
				s(e)
			}
		})
	}

	function s(r) {
		$.ajax({
			type: "post",
			url: ApiUrl + "/index.php?act=member_order&op=order_receive",
			data: {
				order_id: r,
				key: e
			},
			dataType: "json",
			success: function(e) {
				if (e.datas && e.datas == 1) {
					reset = true;
					t()
				} else {
					$.sDialog({
						skin: "red",
						content: e.datas.error,
						okBtn: false,
						cancelBtn: false
					})
				}
			}
		})
	}

	function l() {
		var e = $(this).attr("order_id");
		location.href = WapSiteUrl + "/tmpl/member/member_evaluation.html?order_id=" + e
	}

	function d() {
		var e = $(this).attr("order_id");
		location.href = WapSiteUrl + "/tmpl/member/member_evaluation_again.html?order_id=" + e
	}

	function c() {
		var e = $(this).attr("order_id");
		location.href = WapSiteUrl + "/tmpl/member/order_delivery.html?order_id=" + e
	}
	$("#filtrate_ul").find("a").click(function() {
		$("#filtrate_ul").find("li").removeClass("selected");
		$(this).parent().addClass("selected").siblings().removeClass("selected");
		reset = true;
		window.scrollTo(0, 0);
		t()
	});
	t();
	$(window).scroll(function() {
		if ($(window).scrollTop() + $(window).height() > $(document).height() - 1) {
			t()
		}
	})
});
