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

        $arr = [
            'url' => urlencode('http://59.110.60.173/shop/index.php?act=seller_login%26op=show_login'),
        ];
        Tpl::output('arr',$arr);
        Tpl::showpage('qrcode');die();
	}
}
