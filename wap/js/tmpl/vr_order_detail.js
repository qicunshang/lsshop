var order_id = getQueryString("order_id");
var store_id = "";
var map_index_id = "";
var map_list = [];
$(function() {
	var e = getCookie("key");
	if (!e) {
		window.location.href = WapSiteUrl + "/tmpl/member/login.html"
	}
	$.getJSON(ApiUrl + "/index.php?act=member_vr_order&op=order_info", {
		key: e,
		order_id: order_id
	}, function(e) {
		if (e.datas.error) {
			return
		}
		e.datas.order_info.WapSiteUrl = WapSiteUrl;
		$("#order-info-container").html(template.render("order-info-tmpl", e.datas.order_info));
		$("#buyer_phone").val(e.datas.order_info.buyer_phone);
		$(".cancel-order").click(r);
		$(".evaluation-order").click(i);
		$(".all_refund_order").click(o);
		$("#resend").click(t);
		$("#tosend").click(d);
		$.getJSON(ApiUrl + "/index.php?act=goods&op=store_o2o_addr", {
			store_id: e.datas.order_info.store_id
		}, function(e) {
			if (e.datas.error) {
				return
			}
			$("#list-address-ul").html(template.render("list-address-script", e.datas));
			if (e.datas.addr_list.length > 0) {
				map_list = e.datas.addr_list;
				var r = "";
				r += '<dl index_id="0">';
				r += "<dt>" + map_list[0].name_info + "</dt>";
				r += "<dd>" + map_list[0].address_info + "</dd>";
				r += "</dl>";
				r += '<p><a href="tel:' + map_list[0].phone_info + '"></a></p>';
				$("#goods-detail-o2o").html(r);
				$("#goods-detail-o2o").on("click", "dl", l);
				if (map_list.length > 1) {
					$("#store_addr_list").html("查看全部" + map_list.length + "家分店地址")
				} else {
					$("#store_addr_list").html("查看商家地址")
				}
				$("#map_all > em").html(map_list.length)
			} else {
				$(".nctouch-vr-order-location").hide()
			}
		});
		$.animationLeft({
			valve: "#store_addr_list",
			wrapper: "#list-address-wrapper",
			scroll: "#list-address-scroll"
		})
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
			url: ApiUrl + "/index.php?act=member_vr_order&op=order_cancel",
			data: {
				order_id: r,
				key: e
			},
			dataType: "json",
			success: function(e) {
				if (e.datas && e.datas == 1) {
					window.location.reload()
				}
			}
		})
	}

	function t() {
		$.animationUp({
			valve: "",
			scroll: ""
		});
		$("#buyer_phone").on("blur", function() {
			if ($(this).val() != "" && !/^-?(?:\d+|\d{1,3}(?:,\d{3})+)?(?:\.\d+)?$/.test($(this).val())) {
				$(this).val(/\d+/.exec($(this).val()))
			}
		})
	}

	function d() {
		var r = $("#buyer_phone").val();
		$.ajax({
			type: "post",
			url: ApiUrl + "/index.php?act=member_vr_order&op=resend",
			data: {
				order_id: order_id,
				buyer_phone: r,
				key: e
			},
			dataType: "json",
			success: function(e) {
				if (e.datas && e.datas == 1) {
					$(".nctouch-bottom-mask").addClass("down").removeClass("up")
				} else {
					$(".rpt_error_tip").html(e.datas.error).show()
				}
			}
		})
	}

	function i() {
		var e = $(this).attr("order_id");
		location.href = WapSiteUrl + "/tmpl/member/member_vr_evaluation.html?order_id=" + e
	}

	function o() {
		var e = $(this).attr("order_id");
		location.href = WapSiteUrl + "/tmpl/member/refund_all.html?order_id=" + e
	}
	$("#list-address-scroll").on("click", "dl > a,#map_all", l);
	$("#map_all").on("click", l);

	function l() {
		$("#map-wrappers").removeClass("hide").removeClass("right").addClass("left");
		$("#map-wrappers").on("click", ".header-l > a", function() {
			$("#map-wrappers").addClass("right").removeClass("left")
		});
		$("#baidu_map").css("width", document.body.clientWidth);
		$("#baidu_map").css("height", document.body.clientHeight);
		map_index_id = $(this).attr("index_id");
		if (typeof map_index_id != "string") {
			map_index_id = ""
		}
		if (typeof map_js_flag == "undefined") {
			$.ajax({
				url: WapSiteUrl + "/js/map.js",
				dataType: "script",
				async: false
			})
		}
		if (typeof BMap == "object") {
			baidu_init()
		} else {
			load_script()
		}
	}
});