$(function() {
    var a = getCookie("key");
    var e = '<div class="nctouch-footer-wrap posr">' + '<div class="nav-text">';
    if (a) {
        e += '<a href="' + WapSiteUrl + '/tmpl/member/member.html">我的商城</a>' + '<a id="logoutbtn" href="javascript:void(0);">注销</a>' + '<a href="' + WapSiteUrl + '/tmpl/member/member_feedback.html">反馈</a>'
    } else {
        e += '<a href="' + WapSiteUrl + '/tmpl/member/login.html">登录</a>' + '<a href="' + WapSiteUrl + '/tmpl/member/register.html">注册</a>' + '<a href="' + WapSiteUrl + '/tmpl/member/login.html">反馈</a>'
    }
    e += '<a href="javascript:void(0);" class="gotop">返回顶部</a>' + "</div>" + '<div class="nav-pic">' + '<a href="' + SiteUrl + '/index.php?act=mb_app" class="app"><span><i></i></span><p>客户端</p></a>' + '<a href="javascript:void(0);" class="touch"><span><i></i></span><p>触屏版</p></a>' + '<a href="' + SiteUrl + '" class="pc"><span><i></i></span><p>电脑版</p></a>' + "</div>" + '<div class="copyright">' + 'Copyright&nbsp;&copy;&nbsp;2005-2016 <a href="javascript:void(0);">好商城V4 www.33hao.com</a>版权所有' + "</div>";
    $("#footer").html(e);
    var a = getCookie("key");
    $("#logoutbtn").click(function() {
        var a = getCookie("username");
        var e = getCookie("key");
        var i = "wap";
        $.ajax({
            type: "get",
            url: ApiUrl + "/index.php?act=logout",
            data: {
                username: a,
                key: e,
                client: i
            },
            success: function(a) {
                if (a) {
                    delCookie("username");
                    delCookie("key");
                    location.href = WapSiteUrl
                }
            }
        })
    })
});