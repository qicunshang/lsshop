<?php
/**
 * 第三方账号登录行为
 *
 * @好商城V4 (c) 2005-2016 33hao Inc.
 * @license    http://www.33hao.com
 * @link       交流群号：216611541
 * @since      好商城提供技术支持 授权请购买shopnc授权
 */
defined('InShopNC') or exit('Access Invalid!');
class connect_apiLogic {

    /**
     * 手机注册
     * @param array $order_info
     * @param string $phone 手机号码
     * @param string $password 密码
     * @return array
     */
    public function smsRegister($phone, $captcha, $password, $client) {
		if ($this->check_captcha($phone,$captcha)){
			if(C('sms_register') != 1) {
                //output_error('系统没有开启手机注册功能');
				return array('state'=>0,'msg'=>'系统没有开启手机注册功能');
            }
			$model_member = Model('member');
            $member_name = 'phone_'.$phone;
            $member = $model_member->getMemberInfo(array('member_name'=> $member_name));//检查重名
            if(!empty($member)) {
                //output_error('用户名已被注册');
				return array('state'=>0,'msg'=>'用户名已被注册');
            }
            $member = $model_member->getMemberInfo(array('member_mobile'=> $phone));//检查手机号是否已被注册
            if(!empty($member)) {
                //output_error('手机号已被注册');
				return array('state'=>0,'msg'=>'手机号已被注册');
            }	
			$member = array();
            $member['member_name'] = $member_name;
            $member['member_passwd'] = $password;
            $member['member_mobile'] = $phone;
			$member['member_email']     = '';
            $member['member_mobile_bind'] = 1;
            $result = $model_member->addMember($member);
			//output_error($member);
            if($result) {
                $member = $model_member->getMemberInfo(array('member_mobile'=> $phone));               
				$key ='';// $model_member->_get_token($member['member_id'],$member['member_name'],$client);
				return array('state'=>1,'username'=>$member_name,'key'=>$key);
            } else {
                //output_error('注册失败');
				return array('state'=>0,'msg'=>'注册失败',$member);
            }
		}
       
    }

	/**
     * 手机找回密码
     * @param array $order_info
     * @param string $phone 手机号码
     * @param string $password 密码
     * @return array
     */
    public function smsPassword($phone, $captcha, $password, $client) {
		if ($this->check_captcha($phone,$captcha)){
			if(C('sms_password') != 1) {
                //output_error('系统没有开启手机找回密码功能');
				return array('state'=>0,'msg'=>'系统没有开启手机找回密码功能');
            }
            $condition = array();
            $condition['log_phone'] = $phone;
            $condition['log_captcha'] = $captcha;
            $condition['log_type'] = 3;
            $model_sms_log = Model('sms_log');
            $sms_log = $model_sms_log->getSmsInfo($condition);
            if(empty($sms_log) || ($sms_log['add_time'] < TIMESTAMP-1800)) {//半小时内进行验证为有效
                //output_error('动态码错误或已过期，重新输入');
				return array('state'=>0,'msg'=>'动态码错误或已过期，重新输入');
            }
            $model_member = Model('member');
            $member = $model_member->getMemberInfo(array('member_mobile'=> $phone));//检查手机号是否已被注册
            if(!empty($member)) {
                $new_password = md5($password);
                $model_member->editMember(array('member_id'=> $member['member_id']),array('member_passwd'=> $new_password));
                $model_member->createSession($member);//自动登录
                //output_data('密码修改成功');
				return array('state'=>1,'msg'=>'密码修改成功');
            }
		}
		else{
			output_error('验证错误!');
		}
	}

	public function getStateInfo(){
		if(C('sms_register') == 1) {
			return array('connect_sms_reg'=>1);
		}else{
			return array('connect_sms_reg'=>0);
		}
	}
	/**
     * 手机验证码验证
     */
    protected function check_captcha($phone,$captcha,$type='1'){
        if (strlen($phone) == 11 && strlen($captcha) == 6){
            $condition = array();
            $condition['log_phone'] = $phone;
            $condition['log_captcha'] = $captcha;
            $condition['log_type'] = $type;
            $model_sms_log = Model('sms_log');
            $sms_log = $model_sms_log->getSmsInfo($condition);
            if(empty($sms_log) || ($sms_log['add_time'] < TIMESTAMP-1800)) {//半小时内进行验证为有效
                $state = '动态码错误或已过期，重新输入1';
				output_error($sms_log);
            }
			return true;
        }
		output_error("1");
        return false;
    }
	/**
     * AJAX验证
     *
     */
	protected function check(){
        if (checkSeccode($_GET["sec_key"],$_GET["sec_val"])){
            return true;
        }else{
            return false;
        }
    }

}
