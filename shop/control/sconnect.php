<?php
/**
 * 新浪微博登录 v3-b12
 **by 好商城V3 www.33hao.com 运营版*/


defined('InShopNC') or exit('Access Invalid!');

class sconnectControl extends BaseHomeControl{
	public function __construct(){
		parent::__construct();
		Language::read("home_login_register,home_login_index,home_sconnect");
		/**
		 * 判断新浪微博登录功能是否开启
		 */
		if (C('sina_isuse') != 1){
			showMessage(Language::get('home_sconnect_unavailable'),'index.php','html','error');
		}
		if (!$_SESSION['slast_key']){
			showMessage(Language::get('home_sconnect_error'),'index.php','html','error');
		}
		Tpl::output('hidden_nctoolbar', 1);
		Tpl::setLayout('login_layout');
	}
	/**
	 * 首页
	 */
	public function indexOp(){
		/**
		 * 检查登录状态
		 */
		if($_SESSION['is_login'] == '1') {
			$this->bindsinaOp();
		}else {
			$this->autologin();
			$this->registerOp();
		}
	}
    /**
     * 新浪微博账号绑定新用户
     */
    public function registerOp(){
        //实例化模型
        $model_member   = Model('member');
        if (chksubmit()){
            $update_info    = array();
            $update_info['member_passwd']= md5(trim($_POST["password"]));
            if(!empty($_POST["email"])) {
                $update_info['member_email']= $_POST["email"];
                $_SESSION['member_email']= $_POST["email"];
            }
            $model_member->editMember(array('member_id'=>$_SESSION['member_id']),$update_info);
            showMessage(Language::get('nc_common_save_succ'),SHOP_SITE_URL);
        }else{
            //检查登录状态
            $model_member->checkloginMember();
            //获取新浪微博账号信息
            require_once (BASE_PATH.DS.'api'.DS.'sina'.DS.'saetv2.ex.class.php');
            $c = new SaeTClientV2( C('sina_wb_akey'), C('sina_wb_skey') , $_SESSION['slast_key']['access_token']);
            $sinauser_info = $c->show_user_by_id($_SESSION['slast_key']['uid']);//根据ID获取用户等基本信息
            Tpl::output('sinauser_info',$sinauser_info);
            $sina_str = serialize($sinauser_info);
            $user_array = array();
            $user_passwd = rand(100000, 999999);
            /**
             * 会员添加
             */
            $user_array['member_name']      = $sinauser_info['screen_name'];
            $user_array['member_passwd']    = $user_passwd;
            $user_array['member_email']     = '';
            $user_array['member_sinaopenid']    = $_SESSION['slast_key']['uid'];//sina openid
            $user_array['member_sinainfo']  = $sina_str;//sina 信息
            $rand = rand(100, 899);
            if(strlen($user_array['member_name']) < 3) $user_array['member_name']       = $sinauser_info['screen_name'].$rand;
            $check_member_name  = $model_member->getMemberInfo(array('member_name'=>trim($user_array['member_name'])));
            $result = 0;
            if(empty($check_member_name)) {
                $result = $model_member->addMember($user_array);
            }else {
                for ($i = 1;$i < 999;$i++) {
                    $rand += $i;
                    $user_array['member_name'] = trim($sinauser_info['screen_name']).$rand;
                    $check_member_name  = $model_member->getMemberInfo(array('member_name'=>trim($user_array['member_name'])));
                    if(empty($check_member_name)) {
                        $result = $model_member->addMember($user_array);
                        break;
                    }
                }
            }
            if($result) {
                Tpl::output('user_passwd',$user_passwd);
                $avatar = @copy($sinauser_info['avatar_large '],BASE_UPLOAD_PATH.'/'.ATTACH_AVATAR."/avatar_$result.jpg");
                $update_info    = array();
                if($avatar) {
                    $update_info['member_avatar']   = "avatar_$result.jpg";
                    $model_member->editMember(array('member_id'=>$result),$update_info);
                }
                $member_info = $model_member->getMemberInfo(array('member_name'=>$user_array['member_name']));
                $model_member->createSession($member_info,true);
                Tpl::showpage('sconnect_register');
            } else {
                showMessage(Language::get('login_usersave_regist_fail'),urlLogin('login', 'register') ,'html','error');
            }
        }
    }
	/**
	 * 绑定新浪微博账号后自动登录
	 */
	public function autologin(){
		//查询是否已经绑定该新浪微博账号,已经绑定则直接跳转
		$model_member	= Model('member');
		$array	= array();
		$array['member_sinaopenid']	= $_SESSION['slast_key']['uid'];
		$member_info = $model_member->getMemberInfo($array);
		if (is_array($member_info) && count($member_info)>0){
			if(!$member_info['member_state']){//1为启用 0 为禁用
				showMessage(Language::get('nc_notallowed_login'),'','html','error');
			}
			$model_member->createSession($member_info);
			$success_message = Language::get('login_index_login_success');
			showMessage($success_message,SHOP_SITE_URL);
		}
	}
	/**
	 * 已有用户绑定新浪微博账号
	 */
	public function bindsinaOp(){
		$model_member	= Model('member');
		//验证新浪账号用户是否已经存在
		$array	= array();
		$array['member_sinaopenid']	= $_SESSION['slast_key']['uid'];
		$member_info = $model_member->getMemberInfo($array);
		if (is_array($member_info) && count($member_info)>0){
			unset($_SESSION['slast_key']['uid']);
			showMessage(Language::get('home_sconnect_binding_exist'),'index.php?act=member_connect&op=sinabind','html','error');
		}
		//处理sina账号信息
		require_once (BASE_PATH.DS.'api'.DS.'sina'.DS.'saetv2.ex.class.php');
		$c = new SaeTClientV2( C('sina_wb_akey'), C('sina_wb_skey') , $_SESSION['slast_key']['access_token']);
		$sinauser_info = $c->show_user_by_id($_SESSION['slast_key']['uid']);//根据ID获取用户等基本信息
		$sina_arr = array();
		$sina_arr['name'] = $sinauser_info['name'];
		$sina_str = '';
	    $sina_str = serialize($sina_arr);
		$edit_state		= $model_member->editMember(array('member_id'=>$_SESSION['member_id']),array('member_sinaopenid'=>$_SESSION['slast_key']['uid'], 'member_sinainfo'=>$sina_str));
		if ($edit_state){
			showMessage(Language::get('home_sconnect_binding_success'),'index.php?act=member_connect&op=sinabind');
		}else {
			showMessage(Language::get('home_sconnect_binding_fail'),'index.php?act=member_connect&op=sinabind','html','error');
		}
	}
	/**
	 * 更换绑定新浪微博账号
	 */
	public function changesinaOp(){
		//如果用户已经登录，进入此链接则显示错误
		if($_SESSION['is_login'] == '1') {
			showMessage(Language::get('home_sconnect_error'),'index.php','html','error');
		}
		unset($_SESSION['slast_key']);
		header('Location:'.SHOP_SITE_URL.'/api.php?act=tosina');
		exit;
	}
}
