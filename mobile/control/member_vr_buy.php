<?php
/**
 * 购买
 *
 * @好商城V4 (c) 2005-2016 33hao Inc.
 * @license    http://www.33hao.com
 * @link       交流群号：216611541
 * @since      好商城提供技术支持 授权请购买shopnc授权

 */

defined('InShopNC') or exit('Access Invalid!');

class member_vr_buyControl extends mobileMemberControl {

	public function __construct() {
		parent::__construct();
	}

	/**
	 * 虚拟商品购买第一步，设置购买数量
	 * POST
	 * 传入：cart_id:商品ID，quantity:购买数量
	 */
	public function buy_step1Op() {
	    $_POST['goods_id'] = $_POST['cart_id'];

	    $logic_buy_virtual = Logic('buy_virtual');
	    $result = $logic_buy_virtual->getBuyStep2Data($_POST['goods_id'], $_POST['quantity'], $this->member_info['member_id']);
	    if(!$result['state']) {
	        output_error($result['msg']);
	    } else {
	        $result = $result['data'];
	    }
	    unset($result['member_info']);
	    output_data($result);
	}

    /**
     * 虚拟商品购买第二步，设置接收手机号
	 * POST
	 * 传入：goods_id:商品ID，quantity:购买数量
	 */
    public function buy_step2Op() {

        $logic_buy_virtual = Logic('buy_virtual');
        $result = $logic_buy_virtual->getBuyStep2Data($_POST['goods_id'], $_POST['quantity'], $this->member_info['member_id']);
        if(!$result['state']) {
            output_error($result['msg']);
        } else {
	        $result = $result['data'];
            $member_info = array();
            $member_info['member_mobile'] = $result['member_info']['member_mobile'];
            $member_info['available_predeposit'] = $result['member_info']['available_predeposit'];
            $member_info['available_rc_balance'] = $result['member_info']['available_rc_balance'];
            unset($result['member_info']);
            $result['member_info'] = $member_info;
            output_data($result);
        }
    }

    /**
     * 虚拟订单第三步，产生订单
	 * POST
	 * 传入：goods_id:商品ID，quantity:购买数量，buyer_phone：接收手机，buyer_msg:下单留言,pd_pay:是否使用预存款支付0否1是，password：支付密码
	 */
    public function buy_step3Op() {
        $logic_buy_virtual = Logic('buy_virtual');
        $input = array();
        $input['goods_id'] = $_POST['goods_id'];
        $input['quantity'] = $_POST['quantity'];
        $input['buyer_phone'] = $_POST['buyer_phone'];
        $input['buyer_msg'] = $_POST['buyer_msg'];
        //支付密码
        $input['password'] = $_POST['password'];

        //是否使用充值卡支付0是/1否
        $input['rcb_pay'] = intval($_POST['rcb_pay']);

        //是否使用预存款支付0是/1否
        $input['pd_pay'] = intval($_POST['pd_pay']);

        $input['order_from'] = 2;
        $result = $logic_buy_virtual->buyStep3($input,$this->member_info['member_id']);
        if (!$result['state']) {
            output_error($result['msg']);
        } else {
            output_data($result['data']);
        }
    }
	
	
    /**
     * 验证密码
     */
    public function check_passwordOp() {
        if(empty($_POST['password'])) {
            output_error('参数错误');
        }

        $model_member = Model('member');

        $member_info = $model_member->getMemberInfoByID($this->member_info['member_id']);
        if($member_info['member_paypwd'] == md5($_POST['password'])) {
            output_data('1');
        } else {
            output_error('密码错误');
        }
    }

    /**
     * 更换收货地址
     */
    public function change_addressOp() {
        $logic_buy = Logic('buy');
        if (empty($_POST['city_id'])) {
            $_POST['city_id'] = $_POST['area_id'];
        }
        
        $data = $logic_buy->changeAddr($_POST['freight_hash'], $_POST['city_id'], $_POST['area_id'], $this->member_info['member_id']);
        if(!empty($data) && $data['state'] == 'success' ) {
            output_data($data);
        } else {
            output_error('地址修改失败');
        }
    }
	

	/**
     * 支付方式
     */
    public function payOp() {
		$pay_sn = $_POST['pay_sn'];  
		$condition = array();
		$condition['order_sn'] = $pay_sn;
		$order_info = Model('vr_order')->getOrderInfo($condition);
		$payment_list = Model('mb_payment')->getMbPaymentList();
		
		$pay_info['pay_amount'] = $order_info['order_amount'];
		$pay_info['member_available_pd'] = $this->member_info['available_predeposit'];
		$pay_info['member_available_rcb'] = $this->member_info['available_rc_balance'];

		$pay_info['member_paypwd'] = true;
		if(empty($this->member_info['member_paypwd'])){
			$pay_info['member_paypwd'] = false;		
		}
		
		$pay_info['pay_sn'] = $order_info['pay_sn'];
		$pay_info['payed_amount'] = $order_info['pd_amount'];
		if($pay_info['payed_amount']>'0.00'){
			$pay_info['pay_amount'] = $pay_info['pay_amount']-$pay_info['payed_amount'];
		}

		$pay_in["pay_info"]=$pay_info;
		$pay_in["pay_info"]["payment_list"]=$payment_list;
		output_data($pay_in);
	}

	/**
     * 支付密码确认
     */
    public function check_pd_pwdOp() {
		if($this->member_info['member_paypwd'] != md5($_POST['password'])){
			output_error('支付密码错误');
		}else{
			output_data('OK');
		}
	}
	
	
}
