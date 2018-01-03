<?php
/**
 * 二维码
 *
 *
 *
 **by 好商城V3 www.33hao.com 运营版*/


defined('InShopNC') or exit('Access Invalid!');
class qrcodeControl extends BaseSellerControl {
    /**
	 *
	 * 广告展示
	 */
	public function showQRcodeOp(){
		//http://www.shop.com/wap/tmpl/member/register.html?inv_id=33
        $arr = [
            'url' => BASE_SITE_URL.'/wap/tmpl/member/register.html?inv_id='.$_SESSION['member_id'].'%26store_id='.$_SESSION['store_id'],
        ];
        Tpl::output('arr',$arr);
        Tpl::showpage('qrcode');die();
	}
}
