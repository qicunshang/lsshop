<?php
/**
 * 我的代金券
 *
 * @好商城V4 (c) 2015-2016 33hao Inc.
 * @license    http://www.33hao.com
 * @link       交流群号：216611541
 * @since      好商城提供技术支持 授权请购买shopnc授权

 */

defined('InShopNC') or exit('Access Invalid!');

class member_voucherControl extends mobileMemberControl {

	public function __construct() {
		parent::__construct();
	}

    /**
     * 地址列表
     */
    public function voucher_listOp() {
		$model_voucher = Model('voucher');
        $voucher_list = $model_voucher->getMemberVoucherList($this->member_info['member_id'], $_POST['voucher_state'], $this->page);
        $page_count = $model_voucher->gettotalpage();

        output_data(array('voucher_list' => $voucher_list), mobile_page($page_count));
    }
}
