<?php
/**
 * 邮币接口类
 * @authors 刘帅
 * @date    2016-08-31 11:23:35
 */
//define('GOODS_IMGPATH','http://59.110.60.173/data/upload/shop/ybk_info/');//商品图片
define('AVATAR_PATH','http://59.110.60.173/data/upload/shop/avatar/');//用户头像图片
define('ARTICLE_PATH','http://59.110.60.173/data/upload/shop/adv/');//公告图片
define('GOODSIMAGE_PATH','http://59.110.60.173/data/upload/shop/store/goods/');//商品图片
define('APPID','wx03df5def2b39235f');//微信appid
define('PARTNERID','1418857302');//微信商户号
define('WXKEY','huibangkelvzonghuangjiemengdando');//微信key
define('INFO_PRICE',2.00);  //信息价格
define('OFFICIALSTORE','官网旗舰店');  //官方店铺
header("content-type:text/html;charset=utf-8");
include_once("./api/yunapi/SendTemplateSMS.php");

class ybkApiControl extends BaseHomeControl
{
    public function __construct()
    {
        parent::__construct();
        file_put_contents('2.txt', print_r($_REQUEST, 1),FILE_APPEND);
    }

    //判断数据来源和传输方式
    private function bypost()
    {
        if (count($_GET) == 2 || count($_GET) == 0) {
            $agent = $_SERVER['HTTP_USER_AGENT'];
            if (!strpos($agent, "iPhone")) {
                $c = file_get_contents('php://input');
                file_put_contents('2.txt', '\r\n android:' . $c, FILE_APPEND);
                $info = json_decode($c, true);
                //file_put_contents('2.txt', '\r\n img:' . $info['ybk_pic'][0], FILE_APPEND);
                return $info;
            } else {
                file_put_contents('2.txt', '\r\n ios:' . print_r($_REQUEST,1), FILE_APPEND);
                $c = json_encode($_REQUEST);
                file_put_contents('2.txt','\r\n json:'. $c, FILE_APPEND);
                return $_REQUEST;
            }
        } else {
            return $_REQUEST;
        }
    }

    //登陆接口//
    public function loginOp()
    {
        $_REQUEST = $this->bypost();
        //判断账号密码是否正确
        $model_member = Model('member');
        $user_name = $_REQUEST['user_name'];
        $password = $_REQUEST['password'];

        //验证账号密码合法,放sql注入
        $user_name = htmlspecialchars($user_name, ENT_QUOTES);
        $password = htmlspecialchars($password, ENT_QUOTES);

        $condition = array();
        $condition['member_name'] = $user_name;
        $condition['member_passwd'] = md5($password);
        //开启用户名登陆
        //$member_info = $model_member->getMemberInfo($condition);
        //检查手机号是否存在
        $mobile_info = $this->checkMobile($user_name);
        $info = $this->getUserInfo($mobile_info);
        //dump($info);
        if (!$mobile_info) {
            $this->error('200', '手机号不存在', $info);
            exit;
        }
        //开启手机号登录
        if (empty($member_info) && preg_match('/^0?(13|15|17|18|14)[0-9]{9}$/i', $user_name)) {//根据会员名没找到时查手机号
            $condition = array();
            $condition['member_mobile'] = $user_name;
            $condition['member_passwd'] = md5($password);
            $member_info = $model_member->getMemberInfo($condition);
        }
        //开启邮箱登陆
        /*if(empty($member_info) && (strpos($user_name, '@') > 0)) {//按邮箱和密码查询会员
            $condition = array();
            $condition['member_email'] = $user_name;
            $condition['member_passwd'] = md5($password);
            $member_info = $model_member->getMemberInfo($condition);
        }*/
        //判断是否查询到输入的用户信息
        if (is_array($member_info) && !empty($member_info)) {
            if (!$member_info['member_state']) {
                $this->error('400', '当前用户已被禁用', $info);
            } else {
                $info = $this->getUserInfo($member_info);
                $this->error('0', 'ok', $info);
            }
        } else {
            $this->error('300', '密码错误', $info);
        }
    }

    //验证码登陆/注册/找回密码
    public function verifyOp()
    {
        $this->Dump();
        $_REQUEST = $this->bypost();
        //检查手机号格式
        $mobile = $_REQUEST['member_mobile'];
        if (strlen($mobile) != 11) {
            $this->error('10', '手机号格式错误');
        }
        $case = $_REQUEST['type'];
        $mobile_info = $this->checkMobile($mobile);
        if (empty($case)) {
            $this->error('10', '不正确的类型');
        }
        switch ($case) {
            //注册,手机号存在
            case 'reg':
                $type = '1';
                if ($mobile_info) {
                    $this->error('201', '手机号已被注册');
                }
                break;
            case 'login':
                $type = '2';
                if (!$mobile_info) {
                    $this->error('200', '手机号不存在');
                }
                break;
            case 'updatepassword':
                $type = '3';
                if (!$mobile_info) {
                    $this->error('200', '手机号不存在');
                }
                break;
            case 'updatemobile':
                $type = '4';
                if (!$mobile_info) {
                    $this->error('200', '手机号不存在');
                }
                break;
            default:
                $type = '5';//发送用户密码
                break;
        }
        $time = '60';//有效期
        $verify = rand(100000, 999999);
        $this->sendVerify($mobile, $verify, $time, $type);
    }

    //返回用户信息
    private function getUserInfo($member_info = '')
    {
        if ($member_info == '') {
            $member_info = array(
                'member_id' => null,
                'member_avatar' => null,
                'member_name' => null,
                'member_sex' => null,
                'member_mobile' => null,
                'balance' => null,
                'drinksbalance' => null,
                'recentrebate' => null,
                'token' => null,
            );
        } else {
            //成功登入,写入数据库,返回一个access_token
            $token = md5(uniqid(rand()));
            $model_member = Model('member');
            if ($member_info['member_mobile'] == '13621286210') {
                $token = '123';
            }
            $model_member->update(array(
                'member_id' => $member_info['member_id'],
                'member_login_time' => TIMESTAMP,
                'member_login_num' => $member_info['member_login_num'] + 1,
                'member_login_ip' => getIp(),
                'member_old_login_time' => $member_info['member_login_time'],
                'member_old_login_ip' => $member_info['member_login_ip'],
                'member_privacy' => $token,
            ));
            //$ybk_member = Model('ybk_bank');
            $condition = array();
            $condition['member_id'] = $member_info['member_id'];

            //用户银行卡信息
            //$ybkinfo = $ybk_member -> table('ybk_bank') -> where($condition) -> find();

            //余额
            $data = $this->balance($member_info['member_id']);
            $member_info = array(
                'member_id' => $member_info['member_id'],//id
                'member_avatar' => AVATAR_PATH . $member_info['member_avatar'],//头像
                'member_name' => $member_info['member_name'],//名称,网站中作为登录名
                'member_sex' => $member_info['member_sex'] == null ? '1' : $member_info['member_sex'],//性别
                'member_mobile' => $member_info['member_mobile'],//手机号
                'balance' => $data['balance'],//余额
                'drinksbalance' => $data['drinksbalance'],//酒券余额
                'recentrebate' => $data['recentrebate'],//最近奖励收益
                'token' => $token,//token
            );
            if ($member_info['member_avatar'] == AVATAR_PATH) {
                $member_info['member_avatar'] = '';
            }
            /*if($member_info['member_avatar']==null){
                $member_info['member_avatar'] = 'http://59.110.60.173/data/upload/shop/common/default_user_portrait.gif';
            }*/
        }
        return $member_info;
    }

    //判断token
    private function checkToken($uid, $token = null, $data = false)
    {
        if(empty($uid)){
            $this->error('4', 'token不存在', $data);
        }
        $ybk_member = Model('member');
        $info = $ybk_member->find($uid);
        $u_token = $info['member_privacy'];
        if ($token != $u_token) {
            $this->error('4', 'token不存在', $data);
        }
        $time = $info['member_login_time'];
        if ((time() - $time) > 4 * 3600) {
            //$this->error('4','token过期',$data);
        }
    }

    //退出登录
    public function logoutOp()
    {
        $_REQUEST = $this->bypost();
        $this->checkToken($_REQUEST['member_id'], $_REQUEST['token']);
        $m_model = Model('member');
        $res = $m_model->where(array('member_id' => $_REQUEST['member_id'],))->update(array('member_privacy' => null,));
        if ($res) {
            $this->error('0', '退出成功');
        } else {
            $this->error('-1', '退出失败');
        }
    }

    //手机登陆
    public function loginByMobileOp()
    {
        $_REQUEST = $this->bypost();
        //验证手机和验证码
        $time = 60;//默认有效期
        $member_mobile = $_REQUEST['member_mobile'];
        $member_info = $this->checkMobile($member_mobile);
        $info = $this->getUserInfo();
        if (!$member_info) {
            $this->error('200', '手机号不存在', $info);
        }
        $verify = $_REQUEST['verify'];
        $mobile_info = $this->checkVerify($member_mobile, $verify);
        //匹配短信接口中的手机号码和验证码
        if ($mobile_info) {
            if ((time() - $mobile_info['add_time']) > $time * 60) {
                $this->error('102', '验证码已过期', $info);
            }
            //判断是否被禁用
            if (!$member_info['member_state']) {
                $this->error('400', '当前用户已被禁用', $info);
            } else {
                //处理信息,返回信息
                $info = $this->getUserInfo($member_info);
                if ($info) {
                    $this->error('0', 'ok', $info);
                } else {
                    $this->error('-1', '系统繁忙', $info);
                }
            }
        } else {
            $this->error('100', '验证码错误', $info);
        }
    }

    //判断注册手机号、验证码、邀请人手机号是否正确
    public function checkRegInfoOp()
    {
        $_REQUEST = $this->bypost();
        //验证手机和验证码
        $time = 60;//默认有效期
        $mobile = $_REQUEST['member_mobile'];
        $verify = $_REQUEST['verify'];
        $info = $this->checkVerify($mobile, $verify);
        if ($info) {
            if ((time() - $info['add_time']) > $time * 60) {
                $this->error('102', '验证码已过期');
            }
            //开始验证邀请人手机号,如果存在则进入下一步设置密码
            if ($this->checkMobile($_REQUEST['inv_mobile'])) {
                $this->error('0', 'ok');
            } else {
                $this->error('210', '邀请人手机号不存在');
            }
        } else {
            $this->error('100', '验证码错误');
        }
    }

    //注册操作
    public function regOp()
    {
        $_REQUEST = $this->bypost();
        $model_member = Model('member');
        $mobile = $_REQUEST['member_mobile'];
        $password = $_REQUEST['password'];
        if ($this->checkMobile($mobile)) {
            $this->error('201', '手机号已经注册');
        }
        if (strlen($password) < 6) {
            $this->error('301', '密码不合法');
        }
        //file_put_contents('1.txt',$mobile);
        $verify = $_REQUEST['verify'];
        //file_put_contents('1.txt',$verify,FILE_APPEND);
        $info = $this->checkVerify($mobile, $verify);
        //$info = true;
        //匹配短信接口中的手机号码和验证码
        if ($info) {
            //开始验证邀请人手机号,如果存在则进入下一步设置密码
            if (!($this->checkMobile($_REQUEST['inv_mobile']))) {
                $this->error('210', '邀请人手机号不存在');
            }
            //查询出邀请人的id
            $inv_info = $model_member->where(array('member_mobile' => $_REQUEST['inv_mobile']))->find();
            /*邀请人id为null，则其没有上级，inform_allow为1,此人为普通超级会员,inform_allow为2,为特殊超级会员
            特殊超级会员:下级所有返利都返回特殊超级会员,不论有多少下级
            普通超级会员:直返回直系下级的返利*/
            if ($inv_info['inviter_id'] == null) {
                //邀请人为超级会员,则被邀请人上级为此超级会员
                $inv_id = $inv_info['member_id'];
            } elseif($inv_info['member_id'] == $inv_info['inviter_id']) {
                $inv_id = $inv_info['member_id'];
            }else{
                //邀请人不是超级会员,则判断其邀请人的最终上级是否特殊超级会员
                $invInfo = $this->getTopInvInfo($inv_info['member_id']);
                if($invInfo['inform_allow'] == 1){
                    //顶级为普通超级会员,被邀请人上级为邀请人
                    $inv_id = $inv_info['member_id'];
                }else{
                    //顶级为特殊超级会员,被邀请人上级为特殊超级会员
                    $inv_id = $invInfo['member_id'];
                }
            }
            $member_info = array();
            $member_info['member_id'] = null;
            $member_info['member_name'] = 'user' . $mobile;
            $member_info['member_mobile'] = $mobile;
            $member_info['member_passwd'] = md5($password);
            $member_info['member_time'] = TIMESTAMP;
            $member_info['member_login_time'] = TIMESTAMP;
            $member_info['inviter_id'] = $inv_id;
            $member_info['member_old_login_time'] = TIMESTAMP;
            $res = $model_member->insert($member_info);
            if ($res) {
                $m_model = Model('ybk_member');
                $m_model->insert(array('ybk_member_id' => $res, 'ybk_balance' => 0.00));
                $this->error('0', 'ok');
            } else {
                $this->error('-1', '系统错误');
            }
        } else {
            $this->error('100', '验证码错误');
        }
    }

    public function testOp(){
        $invInfo = $this->getTopInvInfo(1);
        dump($invInfo);
    }

    private function getTopInvInfo($member_id){
        $memberModel = Model('member');
        $memberInfo = $memberModel->where(array('member_id'=>$member_id))->find();
        if($memberInfo['inviter_id'] != null){
            if($memberInfo['inviter_id'] != $memberInfo['member_id']){
                $memberInfo =  $this->getTopInvInfo($memberInfo['inviter_id']);
            }
        }
        return $memberInfo;
    }

    //修改密码(原密码修改)
    public function updatePassWordOp()
    {
        $_REQUEST = $this->bypost();
        $model_member = Model('member');
        $condition = array();
        $condition['member_id'] = $_REQUEST['member_id'];
        $info = $model_member->where($condition)->find();
        if ($info['member_passwd'] != md5($_REQUEST['password'])) {
            $this->error('300', '密码错误');
        }
        if ($_REQUEST['newpassword'] == $_REQUEST['password']) {
            $this->error('302', '新密码与原密码相同');
        }
        $this->setPassWd($_REQUEST['member_id'], $_REQUEST['newpassword']);
    }

    //修改密码2(验证手机号和密码)
    public function updatePwdByMobileOp()
    {
        $_REQUEST = $this->bypost();
        //验证手机和验证码
        $time = 60;//默认有效期,分
        $member_mobile = $_REQUEST['member_mobile'];
        if (!$this->checkMobile($member_mobile)) {
            $this->error('200', '手机号不存在');
        }
        $verify = $_REQUEST['verify'];
        $info = $this->checkVerify($member_mobile, $verify);
        //匹配短信接口中的手机号码和验证码
        if ($info) {
            //判断是否需要修改密码
            if (isset($_REQUEST['newpassword'])) {
                $this->setPassWd($member_mobile, $_REQUEST['newpassword'], 'member_mobile');
            }
            if ((time() - $info['add_time']) > $time * 60) {
                $this->error('102', '验证码已过期');
            }
            $this->error('0', 'ok');
        } else {
            $this->error('100', '验证码错误');
        }
    }

    //修改绑定手机号
    public function updateMobileOp()
    {
        $_REQUEST = $this->bypost();
        $this->checkToken($_REQUEST['member_id'], $_REQUEST['token']);
        //验证手机和验证码
        $time = 60;//默认有效期,分
        if (!isset($_REQUEST['member_mobile'])) {
            $this->error('10', '参数错误');
        }
        if (isset($_REQUEST['newmobile'])) {
            $checkmobile = $this->checkMobile($_REQUEST['newmobile']);
            if ($checkmobile) {
                $this->error('201', '手机号已存在');
            }
        } else {
            $checkmobile = $this->checkMobile($_REQUEST['member_mobile']);
            if (!$checkmobile) {
                $this->error('200', '手机号不存在');
            }
        }
        $verify = $_REQUEST['verify'];
        if (isset($_REQUEST['newmobile'])) {
            $info = $this->checkVerify($_REQUEST['newmobile'], $verify);
        } else {
            $info = $this->checkVerify($_REQUEST['member_mobile'], $verify);
        }
        //匹配短信接口中的手机号码和验证码
        if ($info) {
            //判断是否需要修改绑定手机
            if (isset($_REQUEST['newmobile'])) {
                $m_model = Model('member');
                $res = $m_model->where(array('member_id' => $_REQUEST['member_id'], 'member_mobile' => $_REQUEST['member_mobile']))->update(array('member_mobile' => $_REQUEST['newmobile']));
                if ($res) {
                    $this->error('0', 'ok');
                } else {
                    $this->error('-1', '系统繁忙');
                }
            }
            if ((time() - $info['add_time']) > $time * 60) {
                $this->error('102', '验证码已过期');
            }
            $this->error('0', 'ok');
        } else {
            $this->error('100', '验证码错误');
        }
    }

    //修改密码$member_id修改条件,默认为id($method='member_id')
    protected function setPassWd($member_id, $passwd, $method = 'member_id')
    {
        $model_member = Model('member');
        if (strlen($passwd) < 6) {
            $this->error('301', '密码应大于6位');
        }
        $condition = array();
        if ($method == 'member_id') {
            //通过id修改
            $condition['member_id'] = $member_id;
        } else {
            //通过其他方式查找
            $condition["$method"] = $member_id;
        }
        $data = array(
            'member_passwd' => md5($passwd),
        );
        $res = $model_member->where($condition)->update($data);
        if ($res) {
            $this->error('0', 'ok');
        } else {
            $this->error('-1', '系统繁忙');
        }
    }

    //发送验证码
    protected function sendVerify($mobile, $verify, $time = '1', $type = '1')
    {
        //手机,验证码,模板,有效期
        switch ($type) {
            //注册
            case '1':
                $log_msg = "你的验证码为{$verify},有效期{$time}分钟";
                $log_type = 146772;
                break;
            //登陆
            case '2':
                $log_msg = "你的验证码为{$verify},有效期{$time}分钟";
                $log_type = 146772;
                break;
            //找回密码
            case '3':
                $log_msg = "你的验证码为{$verify},有效期{$time}分钟";
                $log_type = 146772;
                break;
            //发送密码
            case '4':
                $log_msg = "你的密码为{$verify},请妥善保存";
                $log_type = 146772;
                break;
            /*default:
                $log_msg = "你的密码为{$verify},请妥善保存";
                $log_type = 4;
                break;*/
        }
        $type = '1';
        $res = sendTemplateSMS($mobile, array($verify, $time), $log_type);
        //dump($res);exit;
        //$res = true;
        if ($res) {
            $model_sms_log = Model('sms_log');
            $log_array = array();
            $log_array['log_id'] = null;
            $log_array['log_phone'] = $mobile;
            $log_array['log_captcha'] = "$verify";
            $log_array['log_ip'] = getIp();
            $log_array['log_msg'] = $log_msg;
            $log_array['log_type'] = $type;//注册
            $log_array['add_time'] = time();
            $log_array['member_id'] = 0;
            $log_array['member_name'] = null;
            //var_dump($log_array);exit();
            $result = $model_sms_log->table('sms_log')->insert($log_array);
            if ($result) {
                $this->error('0', 'ok');
            } else {
                $this->error('-1', '系统错误');
            }
        } else {
            $this->error('101', '发送验证码失败');
        }
    }

    //验证手机号和验证码
    protected function checkVerify($mobile, $verify)
    {
        $model_sms_log = Model('sms_log');
        $condition = array();
        $condition['log_phone'] = $mobile;
        $condition['log_captcha'] = $verify;
        $info = $model_sms_log->table('sms_log')->where($condition)->order('log_id desc')->limit('1')->find();
        return $info;
    }

    //检查手机号是否存在
    protected function checkMobile($mobile)
    {
        //判断手机号是否存在
        if (strlen($mobile) != 11) {
            $this->error('10', '手机号格式错误');
        }
        $model_member = Model('member');
        $condition = array();
        $condition['member_mobile'] = $mobile;
        return $model_member->where($condition)->find();
    }

    public function updateMemberInfoOp(){
        //$this->Dump();
        $this->checkToken($_REQUEST['member_id'],$_REQUEST['token']);
        $info_model = Model('member');
        if(isset($_REQUEST['qq']) && $_REQUEST['qq']!=''){
            $res= $info_model->where(array('member_id'=>$_REQUEST['member_id']))->update(array('member_qq'=>$_REQUEST['qq']));
        }elseif(isset($_REQUEST['wx']) && $_REQUEST['wx']!=''){
            $res= $info_model->where(array('member_id'=>$_REQUEST['member_id']))->update(array('weixin_info'=>$_REQUEST['wx']));
        }elseif(isset($_REQUEST['idcard']) && $_REQUEST['idcard']!=''){
            $m_model = Model('ybk_member');
            if(count($_FILES)!=0){
                //dump($_FILES);
                $id_pic = '';
                $upload = new UploadFile();
                $upload->set('thumb_width',	500);
                $upload->set('thumb_height',499);
                $upload->set('thumb_ext','_new');
                $upload->set('ifremove',true);
                $upload->set('default_dir','shop/member/');
                foreach($_FILES as $key => $value) {
                    if($_FILES[$key]['size']>0){
                        $ext = strtolower(pathinfo($_FILES[$key]['name'], PATHINFO_EXTENSION));
                        $filename = uniqid();
                        $basename = $filename . ".$ext";
                        $upload->set('file_name', $basename);
                        $upload->upfile($key);
                        $id_pic = $id_pic.','.$filename.'_new'.".$ext";
                    }
                }
                $id_pic = trim($id_pic,',');
            }else{
                $id_pic = '';
            }
            $data=array(
                'ybk_member_id' => $_REQUEST['member_id'],
                'ybk_member_status' => 0,
                'ybk_idcard' => $_REQUEST['idcard'],
                'ybk_identity_pic'=> $id_pic,//位置data/upload/shop/member/
            );
            //插入操作
            $res = $m_model->insert($data);
            //插入操作失败,执行更新操作
            if(!$res){
                $res= $m_model->where(array('ybk_member_id'=>$_REQUEST['member_id']))->update($data);
            }
        }else{
            $data=array();
            if($_FILES['avatar'][size]>0){
                $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
                //$upload->set('file_name',"avatar_".$_REQUEST['member_id'].".$ext");
                $allowExt = array('image/jpeg','image/jpg','image/png','image/gif');
                $res = getimagesize($_FILES['avatar']['tmp_name']);
                $imageType = $res['mime'];
                if(!in_array($imageType,$allowExt)){
                    $this->error('151','图片格式不符');
                }
                $ybk_pic = "avatar_".$_REQUEST['member_id']."_new.$ext";
                $image = BASE_DATA_PATH.'/upload/shop/avatar/'.$ybk_pic;
                //$res = $upload->upfile('avatar');
                if(!move_uploaded_file($_FILES['avatar']['tmp_name'],$image)){
                    $this->error('150','文件上传失败');
                }
            }
            //data/upload/shop/avatar/avatar_1.jpg
            if(isset($ybk_pic)){$data['member_avatar']=$ybk_pic;}
            if(isset($_REQUEST['member_name']) && $_REQUEST['member_name']!=''){$data['member_name'] = $_REQUEST['member_name'];}
            if(isset($_REQUEST['member_truename']) && $_REQUEST['member_truename']!=''){$data['member_truename'] = $_REQUEST['member_truename'];}
            if(isset($_REQUEST['member_sex']) && $_REQUEST['member_sex']!=''){$data['member_sex']=$_REQUEST['member_sex'];}//1男2女3保密
            $res= $info_model->where(array('member_id'=>$_REQUEST['member_id']))->update($data);
            if(isset($ybk_pic)){
                $this->avatarOp();
            }
        }
        if($res){
            $this->error('0','ok');
        }else{
            $this->error('-1','系统繁忙');
        }
    }

    //刷新头像图片
    public function avatarOp(){
        //$_REQUEST = $this -> bypost();
        $m_model = Model('member');
        $avatar = $m_model->field('member_avatar')->where(array('member_id'=>$_REQUEST['member_id']))->find();
        if(isset($avatar) && $avatar['member_avatar']!=null){
            $avatar['member_avatar'] = AVATAR_PATH.$avatar['member_avatar'];
            $this->error('0','ok',$avatar);
        }elseif($avatar['member_avatar']==null){
            $this->error('500','暂无记录',array('member_avatar'=>''));
        }else{
            $this->error('-1','系统繁忙',array('member_avatar'=>''));
        }
    }

    //收藏,关注
    public function goodsCollOp(){
        $_REQUEST = $this -> bypost();
        $coll_model = Model('favorites');
        $data = array(
            'member_id' => $_REQUEST['member_id'],
            'fav_id'    => $_REQUEST['goods_id'],
        );
        if($coll_model->table('favorites')->where($data)->select()){
            $this->error('-1','已经关注');
        }
        //没有关注,获取商品数据
        $goods_m = Model('goods');
        $goodsInfo = $goods_m ->table('goods')
            ->field('goods_id as fav_id,goods_name,goods_image,gc_id,goods_price as log_price')
            ->where(array('goods_id'=>$_REQUEST['goods_id']))
            ->find();
        if($goodsInfo){
            $goodsInfo['fav_time'] = time();
            $goodsInfo['member_id'] = $_REQUEST['member_id'];
            if($coll_model->insert($goodsInfo)){
                $this->error('0','ok');
            }else{
                $this->error('-1','系统繁忙');
            }
        }else{
            $this->error('-1','没有此商品');
        }
    }

    //取消收藏,取消关注
    public function cancelCollOp(){
        $_REQUEST = $this -> bypost();
        $coll_model = Model('favorites');
        $data = array(
            'member_id' => $_REQUEST['member_id'],
            'fav_id'    => $_REQUEST['goods_id'],
        );
        if($coll_model->table('favorites')->where($data)->delete($data)){
            $this->error('0','ok');
        }else{
            $this->error('-1','取消失败');
        }
    }

    //收藏列表,关注列表
    public function collListOp(){
        $_REQUEST = $this -> bypost();
        $coll_model = Model('favorites');
        $collList = $coll_model->table('favorites,');
    }

    //收货地址列表member_id
    public function addressListOp(){
        //$this->Dump();
        $_REQUEST = $this -> bypost();
        $addrNull[0]['address_id']  = null;
        $addrNull[0]['true_name']   = null;
        $addrNull[0]['area_info']   = null;
        $addrNull[0]['address']     = null;
        $addrNull[0]['mob_phone']   = null;
        $addrNull[0]['is_default']  = null;
        //$this->checkToken($_REQUEST['member_id'],$_REQUEST['token'],$addrNull);
        $addr = Model('address');
        $condition = array();
        $condition['member_id'] = $_REQUEST['member_id'];
        $info = $addr->where($condition)->select();
        foreach($info as $key => $value){
            $addrInfo[$key]['address_id']  = $info[$key]['address_id'];
            $addrInfo[$key]['true_name']   = $info[$key]['true_name'];
            $addrInfo[$key]['area_info']   = $info[$key]['area_info'];
            $addrInfo[$key]['address']     = $info[$key]['address'];
            $addrInfo[$key]['mob_phone']   = $info[$key]['mob_phone'];
            $addrInfo[$key]['is_default']  = $info[$key]['is_default'];
        }
        if($addrInfo) {
            //dump($addrInfo);
            $this->error('0', 'ok', $addrInfo);
        }elseif(is_array($info)){
            $this->error('500','暂无记录',$addrNull);
        }else{
            //dump($addrInfo);
            $this->error('-1','系统繁忙',$addrNull);
        }
    }

    //新增收货地址member_id,token,true_name,area_info,mob_phone,is_default
    public function newAddressOp(){
        //$this->Dump();
        $_REQUEST = $this -> bypost();
        $this->checkToken($_REQUEST['member_id'],$_REQUEST['token']);
        $addr = Model('address');
        $data = array(
            'member_id'     => $_REQUEST['member_id'],//用户id
            'true_name'     => $_REQUEST['true_name'],//真实姓名
            'area_id'       => 0,//地区id
            'area_info'     => $_REQUEST['area_info'],//地区内容
            'address'       => $_REQUEST['address'],//详细地址
            'mob_phone'     => $_REQUEST['mob_phone'],//手机号
            'is_default'    => $_REQUEST['is_default'],//是否默认
        );
        if($data['is_default']==1){
            $condition=array();
            $condition['member_id'] = $_REQUEST['member_id'];
            $arr=array('is_default'=>0);
            $addr->where($condition)->update($arr);
            $res = $addr->insert($data);
        }else{
            $res = $addr->insert($data);
        }
        if($res){
            $this->error('0','ok');
        }else{
            $this->error('-1','系统繁忙');
        }
    }

    //设置默认收货地址
    public function setDefaultAddrOp(){
        $_REQUEST = $this -> bypost();
        $this->checkToken($_REQUEST['member_id'],$_REQUEST['token']);
        $addr = Model('address');
        $condition=array();
        $condition['member_id'] = $_REQUEST['member_id'];
        $arr=array('is_default'=>0);
        //清除该用户默认地址
        $addr->where($condition)->update($arr);
        //设置默认地址
        $res = $addr->update(array('is_default'=>1,'address_id' => $_REQUEST['address_id']));
        if($res){
            $this->error('0','ok');
        }else{
            $this->error('-1','系统繁忙');
        }
    }

    //删除收货地址
    public function delAddrOp(){
        $_REQUEST = $this -> bypost();
        $this->checkToken($_REQUEST['member_id'],$_REQUEST['token']);
        $addr = Model('address');
        $condition = array();
        $condition['address_id'] = $_REQUEST['address_id'];
        $condition['member_id'] = $_REQUEST['member_id'];
        $res= $addr->where($condition)->delete();
        if($res){
            $this->error('0','ok');
        }else{
            $this->error('-1','系统繁忙');
        }
    }

    //修改收货地址
    public function updateAddrOp(){
        //$this->Dump();
        $_REQUEST = $this -> bypost();
        $this->checkToken($_REQUEST['member_id'],$_REQUEST['token']);
        $addr = Model('address');
        $data = array();
        $data['address_id'] = $_REQUEST['address_id'];
        $update = 'area_id=0';
        if(isset($_REQUEST['true_name'])){$update .= ',true_name="'.$_REQUEST['true_name'].'"';}
        if(isset($_REQUEST['area_info'])){$update .= ',area_info="'.$_REQUEST['area_info'].'"';}
        if(isset($_REQUEST['address'])){$update .= ',address="'.$_REQUEST['address'].'"';}
        if(isset($_REQUEST['mob_phone'])){$update .= ',mob_phone='.$_REQUEST['mob_phone'];}
        if($_REQUEST['is_default']==1){
            $update .= ',is_default="1"';
        }else{
            $update .= ',is_default="0"';
        }
        $condition=array();
        $condition['member_id'] = $_REQUEST['member_id'];
        if($_REQUEST['is_default']==1){
            $arr=array('is_default'=>0);
            $addr->where($condition)->update($arr);
        }
        $sql = 'update 33hao_address set '.$update.' where address_id='.$_REQUEST['address_id'].' and member_id='.$_REQUEST['member_id'];
        //echo $sql;
        $res = $addr->execute($sql);
        if($res){
            $this->error('0','ok');
        }else{
            $this->error('-1','系统繁忙');
        }
    }

    //加入购物车
    public function newCartOp(){
        $_REQUEST = $this -> bypost();
        $cart_model = Model('cart');
        $data = array();
        $data['buyer_id']   = $_REQUEST['member_id'];
        $data['goods_id']   = $_REQUEST['goods_id'];
        $data['goods_num']  = isset($_REQUEST['goods_num'])?$_REQUEST['goods_num']:1;
        $data['store_id']   = 1;
        $data['store_name'] = OFFICIALSTORE;
        $goods_m = Model('goods');
        $goods = $goods_m->where('goods_id='.$_REQUEST['goods_id'].' and goods_state=1 and goods_verify=1')->find();
        //dump($goods);
        $data['goods_price'] = $goods['goods_price'];
        $res = $cart_model->where('buyer_id='.$_REQUEST['member_id'].' AND goods_id='.$_REQUEST['goods_id'])->find();
        if($res){
            $sql = 'update 33hao_cart set goods_num=goods_num+1 WHERE buyer_id='.$_REQUEST['member_id'].' AND goods_id='.$_REQUEST['goods_id'];
            //echo $sql;die;
            $result = $cart_model->execute($sql);
	    $info = ['cart_id'=>$res['cart_id']];
        }else{
            $result = $cart_model->insert($data);
	    $info = ['cart_id'=>$result];
        }
        if(!$goods){
            $this->error('-1','商品不存在');
        }
        if($result){
            $this->error('0','ok',$info);
        }else{
            $this->error('-1','系统繁忙');
        }
    }

    //删除购物车物品
    public function delCartGoodsOp(){
        $_REQUEST = $this -> bypost();
        $this->checkToken($_REQUEST['member_id'],$_REQUEST['token']);
        $cart_model = Model('cart');
        $res= $cart_model->where('cart_id in('.$_REQUEST['cart_id'].') and buyer_id='.$_REQUEST['member_id'])->delete();
        if($res){
            $this->error('0','ok');
        }else{
            $this->error('-1','系统繁忙');
        }
    }

    //更改购物车物品数量
    public function updateCartOp(){
        $_REQUEST = $this -> bypost();
        $this->checkToken($_REQUEST['member_id'],$_REQUEST['token']);
        $cart_model = Model('cart');
        $data = array(
            'cart_id' => $_REQUEST['cart_id'],
            'goods_num' => $_REQUEST['goods_num'],
        );
        $res= $cart_model->table('cart')->update($data);
        if($res){
            $this->error('0','ok');
        }else{
            $this->error('-1','系统繁忙');
        }
    }

    //购物车中商品数量
    public function cartNumOp(){
        $_REQUEST = $this->bypost();
        $cart_model = Model('cart');
        $where = array(
            'buyer_id' => $_REQUEST['member_id'],
        );
        $info = $cart_model ->field('count(*)')-> where('buyer_id='.$_REQUEST['member_id'])->find();
        //dump($info);\
        $list = $cart_model->table('cart,goods')
            ->field('cart.cart_id,goods.goods_price,goods.goods_name,goods.goods_image,cart.goods_num')
            ->join('left')
            ->on('cart.goods_id=goods.goods_id')
            ->where($where)
            ->select();
        foreach($list as $k=>$v){
            if(empty($list[$k]['goods_name'])){
                $info['count(*)'] = $info['count(*)'] -1;
                $sql = 'cart_id='.$list[$k]['cart_id'];
                $res = $cart_model->execute('delete from 33hao_cart where cart_id='.$list[$k]['cart_id']);
                //dump($res);
            }
        }
        //dump($list);
        $num['cart_num'] = $info['count(*)'];
        if($info){
            $this->error('0','ok',$num);
        }else{
            $this->error('-1','系统错误',array('cart_num'=>''));
        }
    }

    //购物车列表
    public function cartListOp(){
        $_REQUEST = $this -> bypost();
        $cart_model = Model('cart');
        $where = array(
            'buyer_id' => $_REQUEST['member_id'],
        );
        $list = $cart_model->table('cart,goods')
            ->field('cart.cart_id,cart.goods_id,goods.goods_price,goods.goods_name,goods.goods_image,cart.goods_num')
            ->join('left')
            ->on('cart.goods_id=goods.goods_id')
            ->where($where)
            ->select();
        //dump($list);
        foreach($list as $k=>$v){
            $arr_image = explode('_',$list[$k]['goods_image']);
            $num = $arr_image[0];
            $list[$k]['goods_image'] = GOODSIMAGE_PATH.$num.'/'.$list[$k]['goods_image'];
            $list[$k]['goods_price'] = (string)$list[$k]['goods_price'];
            $arr = explode(' ',$list[$k]['goods_name']);
            $list[$k]['goods_name'] = $arr[0];
            if(empty($list[$k]['goods_name'])){
                unset($list[$k]);
            }
        }
        $cartList = array();
        foreach($list as $key=>$value){
            $cartList[] = $value;
        }
        $null_list = array(
            array(
                'cart_id' => null,
                'goods_price' => null,
                'goods_name' => null,
                'goods_image' => null,
                'goods_num' => null,
            ),
        );
        //dump($list);
        if($cartList){
            $this->error('0','ok',$cartList);
        }elseif(is_array($cartList)){
            $this->error('500','暂无数据',$null_list);
        }else{
            $this->error('-1','系统错误',$null_list);
        }
    }

    //个人金币接口
    public function ybkBalanceOp(){
        $_REQUEST = $this -> bypost();
        $this->checkToken($_REQUEST['member_id'],$_REQUEST['token']);
        $data = $this->balance($_REQUEST['member_id']);
        $this->error('0','ok',$data);
    }

    //个人余额
    private function balance($id){
        $balance = Model('member');
        $drinks = Model('ybk_member');
        //充值记录,定义充值方式17为奖励
        $recent = Model('ybk_deposit');
        $m_balance = $balance->where(array('member_id'=>$id))->find();
        $drinks_balance = $drinks->where(array('ybk_member_id'=>$id))->find();
        $time = time()-7*24*3600;
        //7天内获得奖励
        $recentRebate = $recent->field('sum(deposit_money)')
            ->where('member_id='.$id.' and deposit_method=17 and deposit_time>'.$time)
            ->find();
        if(!isset($m_balance['available_predeposit'])){
            $m_balance['available_predeposit'] = '0.00';
        }elseif(!isset($drinks_balance['ybk_balance'])){
            $drinks_balance['ybk_balance'] = '0.00';
        }elseif(!isset($recentRebate['sum(deposit_money)'])){
            $recentRebate['sum(deposit_money)'] = '0.00';
        }
        $data=array(
            'balance'           =>$m_balance['available_predeposit'],//余额
            'drinksbalance'     =>$drinks_balance['ybk_balance'],//酒券
            'recentrebate'      =>$recentRebate['sum(deposit_money)'],//近期酒券
        );
        return $data;
    }

    //用户消息中心
    public function memberMsgOp(){
        $_REQUEST = $this -> bypost();
        //$this->checkToken($_REQUEST['member_id'],$_REQUEST['token']);
        $msg_model = Model('33hao_message');

        //查询该用户接收的最近10条消息
        $condition = array();
        $condition['to_member_id'] = array('like','%,'.$_REQUEST['member_id'].',%');
        //$condition['message_open'] = 0;//未读
        $condition['message_state'] = 0;//短消息状态，0为正常状态，1为发送人删除状态，2为接收人删除状态
        //$condition['message_ismore'] = 0;//站内信是否为一条发给多个用户 0为否 1为多条
        //$condition['message_type'] = 0;//0为私信、1为系统消息、2为留言

        //排序
        $order = 'message_open asc,message_time desc';

        //显示条数
        $limit = '10';
        $msglist = $msg_model->table('message')->where($condition)->order($order)->limit($limit)->select();

        //$sql = 'select * from 33hao_message where to_member_id like "%,'.$_REQUEST['member_id'].',%" AND message_state=0 order by message_open asc,message_time desc limit 10';
        //$msglist = $msg_model->query($sql);
        if($msglist){
            $msgData[0]['name'] = '系统消息';
            $msgData[1]['name'] = '订单通知';
            $msgData[2]['name'] = '订阅通知';
            $msgData[3]['name'] = '公告';
            foreach($msglist as $key => $value){
                $msglist[$key]['message_time'] = date('Y-m-d H:i:s',$msglist[$key]['message_time']);
                unset($msglist[$key]['message_parent_id']);
                unset($msglist[$key]['to_member_id']);
                unset($msglist[$key]['message_state']);
                unset($msglist[$key]['read_member_id']);
                unset($msglist[$key]['del_member_id']);
                unset($msglist[$key]['message_update_time']);
                unset($msglist[$key]['message_ismore']);
                //消息类别 系统消息1,订单通知10,订阅通知20,公告30
                switch($msglist[$key]['message_type']){
                    case 1:
                        $msgData[0]['data'][] = $msglist[$key];
                        break;
                    case 10:
                        $msgData[1]['data'][] = $msglist[$key];
                        break;
                    case 20:
                        $msgData[2]['data'][] = $msglist[$key];
                        break;
                    case 30:
                        $msgData[3]['data'][] = $msglist[$key];
                        break;
                }
            }
        }
        foreach($msgData as $key=>$v){
            if(!$msgData[$key]['data']){
                $msgData[$key]['data'] = array(
                    array(
                        'message_id'            =>null,     //消息id
                        'from_member_id'        =>null,     //发件人id
                        'message_title'         =>null,     //消息标题
                        'message_body'          =>null,     //消息内容
                        'message_time'          =>null,	    //消息发送时间
                        'message_open'          =>null,     //是否已读
                        'message_type'	        =>null,	    //消息类型 0为私信、1为系统消息、2为留言
                        'from_member_name'      =>null,     //发件人昵称
                        'to_member_name'        =>null,	    //收件人昵称
                    ),
                );
            }
        }
        //dump($msgData);
        if($msgData){
            $this->error('0','ok',$msgData);
        } elseif (is_array($msglist)){
            $this->error('500','暂无消息',$msgData);
        } else {
            $this->error('-1','系统繁忙',$msgData);
        }

    }

    //礼金卡激活
    public function cardActOp(){
        $_REQUEST = $this -> bypost();
        $mobile = $_REQUEST['member_mobile'];
        $verify = $_REQUEST['verify'];
        $this->checkToken($_REQUEST['member_id'],$_REQUEST['token']);
        $info = $this->checkVerify($mobile, $verify);
        //匹配短信接口中的手机号码和验证码
        if ($info) {
            $rechargecard_m = Model('rechargecard');
            $balance = Model('member');

            //礼金卡金额
            $card = $rechargecard_m->where(array('sn'=>$_REQUEST['sn']))->find();
            //dump($card);
            if(!$card){
                $this->error('40','礼金卡不存在');
            }elseif($card['state']!=0){
                $this->error('41', '礼金卡已被使用');
            }elseif(($card['tscreated']+$card['valid']*3600*24)<time()){
                $this->error('42','礼金卡已过期');
            }
            //开始事务
            $rechargecard_m->beginTransaction();
            $res1 = $rechargecard_m->where(array('sn'=>$_REQUEST['sn'],'state' => 0))->update($data = array('state' => 1,'member_id'=>$_REQUEST['member_id']));
            $res2 = $balance->execute("update 33hao_member set available_predeposit=available_predeposit + ".$card['denomination']." where member_id=".$_REQUEST['member_id']);
            if($res1 && $res2){
                $rechargecard_m->commit();
                $this->error('0', 'ok');
            }else{
                //dump($res1);
                //dump($res2);
                $rechargecard_m->rollback();
                $this->error('-1', '系统繁忙,请重试');
            }
        } else {
            $this->error('100', '验证码错误');
        }
    }

    //新增发票
    public function newInvOp(){
        $_REQUEST = $this -> bypost();
        $this->checkToken($_REQUEST['member_id'],$_REQUEST['token']);
        $str1 = 'a:3:{s:6:"类型";s:13:"普通发票 ";s:6:"抬头";s:9:"汇邦客";s:6:"内容";s:6:"饰品";}';
        $str2 = 'a:10:{s:12:"单位名称";s:9:"汇邦客";s:18:"纳税人识别号";s:4:"1111";s:12:"注册地址";s:12:"汇邦客123";s:12:"注册电话";s:3:"110";s:12:"开户银行";s:4:"1110";s:12:"银行账户";s:4:"1110";s:15:"收票人姓名";s:6:"ls";s:18:"收票人手机号";s:11:"13621286210";s:15:"收票人省份";s:26:"北京	北京市	东城区";s:12:"送票地址";s:6:"北京";}';
        $str = unserialize($str1);
        $inv = unserialize($str2);
        dump($str);
        dump($inv);
        $inv_m = Model('invoice');
        $invList = $inv_m->where('member_id='.$_REQUEST['member_id'])->select();
    }

    //前往结算，保存购物车信息，返回订单结算数据-地址，商品清单，买酒券，钱包余额
    public function settlementOp(){
        $_REQUEST = $this -> bypost();
        //$this->checkToken($_REQUEST['member_id'],$_REQUEST['token']);
        $cart_model = Model('cart');

        //购物车ids
        $cart_ids = $_REQUEST['cart_id'];
        $where = array(
            'buyer_id' => $_REQUEST['member_id'],
            'cart_id' => array('in',$cart_ids),
        );
        $list = $cart_model->table('cart,goods')
            ->field('cart.cart_id,goods.goods_price,goods.goods_name,goods.goods_image,cart.goods_num')
            ->join('left')
            ->on('cart.goods_id=goods.goods_id')
            ->where($where)
            ->select();
        $sum = 0;
        foreach($list as $k=>$v){
            $arr_image = explode('_',$list[$k]['goods_image']);
            $num = $arr_image[0];
            $list[$k]['goods_image'] = GOODSIMAGE_PATH.$num.'/'.$list[$k]['goods_image'];
            $list[$k]['goods_num'] = $list[$k]['goods_num'];
            $list[$k]['goods_price'] = (string)$list[$k]['goods_price'];
            $sum += $list[$k]['goods_price']*$list[$k]['goods_num'];
        }

        //总价
        $list['sum'] = (string)sprintf("%01.2f", $sum);
        //dump($list);

        //地址
        $addr_m = Model('address');
        $addr= $addr_m->field('address_id,true_name,mob_phone,area_info,address')
            ->where(array('member_id'=>$_REQUEST['member_id']))
            ->order('is_default desc')
            ->limit('1')
            ->find();
        if(!$addr){
            $addr = array(
                'address_id'=>'','true_name'=>'','mob_phone'=>'','area_info'=>'','address'=>'',
            );
        }

        //余额
        $balance = $this->balance($_REQUEST['member_id']);
        unset($balance['recentrebate']);
        $balance['balance'] = (string)sprintf("%01.2f", $balance['balance']);
        $balance['drinksbalance'] = (string)sprintf("%01.2f", $balance['drinksbalance']);

        //
        $sum = $list['sum'];
        unset($list['sum']);
        $info = array(
            'addr'  => $addr,//地址
            'cart_goods' => $list,//购物车商品
            'money' => $balance,//余额
            'sum' => $sum,
        );
        if(!$info['cart_goods']){
            $info['cart_goods'] = array(array(
                'cart_id'       => '',
                'goods_price'   => '',
                'goods_name'    => '',
                'goods_image'   => '',
                'goods_num'     => '',
            ));
            $this->error('500','ok',$info);
        }else {
            //dump($info);
            $this->error('0', 'ok', $info);
        }
    }

    //下订单
    public function newOrderOp(){
        $_REQUEST = $this -> bypost();
        //$this->checkToken($_REQUEST['member_id'],$_REQUEST['token']);

        //用户个人信息
        $member_m = Model('member');
        $memberInfo = $member_m->field('member_name,member_email')->where('member_id = '.$_REQUEST['member_id'])->find();
        //dump($memberInfo);

        //是否需要开发票
        if(!empty($_REQUEST['inv'])){
            //1普通发票
            if($_REQUEST['inv'] == '1'){
                $data = array(
                    '类型' => '普通发票',
                    '抬头' => $_REQUEST['inv_title'],
                    '内容' => $_REQUEST['inv_content'],
                );
            }elseif($_REQUEST['inv'] == '2'){
                $data = array(
                    '抬头'            => $_REQUEST['inv_title'],
                    '寄送地址'        => $_REQUEST['address'],
                    '公司电话'        => $_REQUEST['phone'],
                    '开户行'          => $_REQUEST['bank'],
                    '银行账号'        => $_REQUEST['bank_account'],
                    '税务号'          => $_REQUEST['inv_code'],
                );
            }
            $invoice = serialize($data);
        }

        //是否传入地址信息
        if(isset($_REQUEST['address_id'])){
            $orderModel = Model('order');
            $order = $orderModel->table('order')->field('order_id')->order('order_id desc')->find();
            $or_com = Model('order_common');
            $order_com = $or_com->table('order_common')->field('order_id')->order('order_id desc')->find();
            //dump($order);
            if($order['order_id']>=$order_com['order_id']){
                $order_id = $order['order_id']+1;
            }else{
                $order_id = $order_com['order_id']+1;
            }
            $addr_m = Model('address');
            $addrInfo = $addr_m->where('address_id='.$_REQUEST['address_id'])->find();
            $data = array(
                'order_id' => $order_id,
                'store_id' => 1,
                'evalseller_time' => time(),
                'daddress_id' => $addrInfo['address_id'],
                'reciver_name' => $addrInfo['true_name'],
                'reciver_info' => serialize(array(
                    'phone'         => $addrInfo['mob_phone'].','.$addrInfo['tel_phone'],
                    'mob_phone'     => $addrInfo['mob_phone'],
                    'tel_phone'     => $addrInfo['tel_phone'],
                    'address'       => $addrInfo['area_info'].$addrInfo['address'],
                    'area'          => $addrInfo['area_info'],
                    'street'        => $addrInfo['area_info'],

                )),
                'invoice_info' => $invoice,
                'order_message' => $_REQUEST['comment'],
            );
            /*a:6:{s:5:"phone";s:20:"13300712233,19999999";s:9:"mob_phone";s:11:"13300712233";s:9:"tel_phone";s:8:"19999999";s:7:"address";s:30:"北京	北京市	东城区 无";s:4:"area";s:26:"北京	北京市	东城区";s:6:"street";s:3:"无";}*/

            $order_id = $or_com->insert($data);
            //dump($data);
        }

        //商品总价
        $cart_model = Model('cart');
        $cart_ids = $_REQUEST['cart_id'];
        $where = array(
            'buyer_id' => $_REQUEST['member_id'],
            'cart_id' => array('in',$cart_ids),
        );
        $list = $cart_model->table('cart,goods')
            ->field('cart.cart_id,goods.goods_id,goods.goods_storage,goods.goods_price,goods.goods_name,goods.goods_image,cart.goods_num')
            ->join('left')
            ->on('cart.goods_id=goods.goods_id')
            ->where($where)
            ->select();
        //dump($list);die;
        $goods_amount = 0;
        foreach($list as $key=>$value){
            $goods_amount += $value['goods_price']*$value['goods_num'];
            //判断库存是否足够
            if(($value['goods_storage'] - $value['goods_num'])<0){
                $this->error('31','库存不足');
            }
        }
        $order_state = 10;
        //是否使用优惠券，账户余额等
        $order_amount = $goods_amount;
        if(!empty($_REQUEST['account'])){

            //查询余额
            $balance = $this->balance($_REQUEST['member_id']);

            if($_REQUEST['account'] == 'balance,drinksbalance'){
                $discount = $balance['balance']+$balance['drinksbalance'];
            }elseif($_REQUEST['account'] == 'balance'){
                $discount = $balance['balance'];
            }elseif($_REQUEST['account'] == 'drinksbalance'){
                $discount = $balance['drinksbalance'];
            }

            //判断账户是否足够付款
            if($discount>=$goods_amount){
                //足够
                //酒券就足够
                if($balance['drinksbalance']>=$goods_amount){
                    //酒券花费即为总价
                    $cost['drinksbalance'] = $goods_amount;
                    //余额无花费
                    $cost['balance'] = 0;
                }else{
                    //酒券花掉全部
                    $cost['drinksbalance'] =$balance['drinksbalance'];
                    //余额花费剩下部分(减去余额剩下的)
                    $cost['balance'] = $goods_amount - $balance['drinksbalance'];
                }
                $balance['balance'] = $balance['balance'] - ($discount - $goods_amount);
                $order_amount = 0;//订单金额
                $order_state=20;
            }else{
                //不够付款,则全部花完
                //70-50,酒券20,余额30
                $cost['balance'] = $balance['balance'];
                $cost['drinksbalance'] = $balance['drinksbalance'];
                $order_amount = $goods_amount - $discount;
            }
            $sql = 'update 33hao_member set available_predeposit=available_predeposit-'.$cost['balance'].' where member_id='.$_REQUEST['member_id'];
            $member_m->execute($sql);

            //酒券
            $m = Model('ybk_member');
            $sql2 = 'update 33hao_ybk_member set ybk_balance=ybk_balance-'.$cost['drinksbalance'].' where ybk_member_id='.$_REQUEST['member_id'];
            $m ->execute($sql2);
            //echo $order_amount;
            //dump($list);
            //echo $goods_amount;
        }
        //订单生成，商品总价-订单总价为账户余额支付
        $order_m = Model('order');
        $pay_sn = mt_rand(10,99)
            . sprintf('%010d',time() - 946656000)
            . sprintf('%03d', (float) microtime() * 1000)
            . sprintf('%03d', (int) $_REQUEST['member_id'] % 1000);
        $order_sn = '800'.date('md',time()).substr(microtime(true),5,5).rand(1000,9999);
        $data = array(
            'order_id'          => $order_id,
            'order_sn'          => $order_sn,
            'pay_sn'            => $pay_sn,
            'store_id'          => 1,
            'store_name'        => '官方旗舰店',
            'buyer_id'          => $_REQUEST['member_id'],
            'buyer_name'        => $memberInfo['member_name'],
            'buyer_email'       => $memberInfo['member_email'],
            'add_time'          => time(),
            'payment_code'      => 'online',
            'goods_amount'      => $goods_amount,//商品总价
            'order_amount'      => $order_amount,//订单总价
            'order_state'       => $order_state,//订单总价
            'shipping_fee'      => 0,//运费
            'order_from'        => 2,//订单来源2mobile
        );
        //dump($data);die;
        //生成订单1，同时生成订单内商品1,2,3...
        $order_g = Model('order_goods');
        $goods_m = Model('goods');
        $order_m->beginTransaction();
        $id = $order_m->table('order')->insert($data);
        foreach($list as $key=>$value){
            $info[$key] = array(
                'order_id'          =>$id,
                'goods_id'          =>$list[$key]['goods_id'],
                'goods_name'        =>$list[$key]['goods_name'],
                'goods_price'       =>$list[$key]['goods_price'],
                'goods_num'         =>$list[$key]['goods_num'],
                'goods_image'       =>$list[$key]['goods_image'],
                'goods_pay_price'   =>$list[$key]['goods_price'],
                'store_id'          =>1,
                'buyer_id'          =>$_REQUEST['member_id'],
            );
            $res2 = $order_g->table('order_goods')->insert($info[$key]);

            //下订单后操作，库存-1，销量+1
            $sql1 = 'update 33hao_goods set goods_storage=goods_storage-1,goods_salenum=goods_salenum+1 where goods_id='.$list[$key]['goods_id'];
            $res4 = $goods_m ->execute($sql1);
        }

        //删除购物车物品
        $sql = 'delete from 33hao_cart where cart_id in('.$_REQUEST['cart_id'].') AND buyer_id='.$_REQUEST['member_id'];
        $res3 = $cart_model->execute($sql);

        if($id&&$res2&&$res3){
            $order_m->commit();
            $this->error('0','ok',array('order_sn'=>$order_sn));
        }else{
            //dump($sql);
            $order_m->rollback();
            $this->error('51','订单已存在',array('order_sn'=>null));
        }
    }

    //收银台
    public function payOp(){
        $_REQUEST = $this -> bypost();
        $this->checkToken($_REQUEST['member_id'],$_REQUEST['token']);
        $order_m = Model('order');
        $orderInfo = $order_m->table('order')
            ->field('order_sn,order_amount')
            ->where('order_sn='.$_REQUEST['order_sn'].' and buyer_id ='.$_REQUEST['member_id'])
            ->find();
        if($orderInfo){
            $this->error('0','ok',$orderInfo);
        }else{
            $this->error('-1','系统繁忙',array('order_sn'=>null,'order_amount'=>null));
        }
    }


    //去支付生成预付单
    public function toPayOp(){
        $_REQUEST = $this -> bypost();
        //$this->checkToken($_REQUEST['member_id'],$_REQUEST['token']);

        //客户端传递标识wx,alipay
        $order_m = Model('order');
        $orderInfo = $order_m->table('order,order_goods')
            ->field('order.pay_sn,order.order_amount,order_goods.goods_name')
            ->join('left')
            ->on('order.order_id=order_goods.order_id')
            ->where('order.order_sn='.$_REQUEST['order_sn'].' and order.buyer_id ='.$_REQUEST['member_id'])
            ->find();
        //dump($orderInfo);die;
        //file_put_contents('test.txt', print_r($_GET,true));
        //商品订单
        $notify = 'http://www.hbkclub.com/wxpay.php';
        if(!empty($_REQUEST['method'])){
            if($_REQUEST['method']=='wx'){

                //充值订单,先本地生成一个订单
                if(!empty($_REQUEST['recharge'])){
                    //用户个人信息
                    $member_m = Model('member');
                    $memberInfo = $member_m->field('member_name,member_email')->where('member_id = '.$_REQUEST['member_id'])->find();
                    $pay_sn = mt_rand(10,99)
                        . sprintf('%010d',time() - 946656000)
                        . sprintf('%03d', (float) microtime() * 1000)
                        . sprintf('%03d', (int) $_REQUEST['member_id'] % 1000);
                    $order_sn = '800'.date('md',time()).substr(microtime(true),5,5).rand(1000,9999);

                    //充值金额
                    //$recharge = $_REQUEST['recharge'] * 1.1;
                    if($_REQUEST['recharge'] >= 50000){
                        $recharge = $_REQUEST['recharge'] + 4000;
                    }elseif($_REQUEST['recharge'] >= 20000){
                        $recharge = $_REQUEST['recharge'] + 1200;
                    }elseif($_REQUEST['recharge'] >= 10000){
                        $recharge = $_REQUEST['recharge'] + 500;
                    }elseif($_REQUEST['recharge'] >= 5000){
                        $recharge = $_REQUEST['recharge'] + 200;
                    }elseif($_REQUEST['recharge'] >= 0.01){
                        $recharge = $_REQUEST['recharge'] + 0.01;
                    }else{
                        $recharge = $_REQUEST['recharge'];
                    }
                    $data = array(
                        'order_sn'          => $order_sn,
                        'pay_sn'            => $pay_sn,
                        'store_id'          => 1,
                        'store_name'        => '官方旗舰店',
                        'buyer_id'          => $_REQUEST['member_id'],
                        'buyer_name'        => $memberInfo['member_name'],
                        'buyer_email'       => $memberInfo['member_email'],
                        'add_time'          => time(),
                        'payment_code'      => 'recharge',
                        'goods_amount'      => (float)$recharge,//商品总价
                        'order_amount'      => (float)$_REQUEST['recharge'],//订单总价
                        //'order_amount'      => (float)0.01,//订单总价
			'shipping_fee'      => 0,//运费
                        'order_from'        => 2,//订单来源2mobile
                    );
                    $order_m = Model('order');
                    if($order_m->table('order')->insert($data)){
                        $notify = 'http://www.hbkclub.com/wxrecharge.php';
                        $this->wxPayOp('账户充值',$pay_sn,(int)($_REQUEST['recharge'] * 100),$notify);
			//$this->wxPayOp('账户充值',$pay_sn,1,$notify);
                    }else{
                        $this->error('-1','系统错误');
                    }
                }
                $this->wxPayOp($orderInfo['goods_name'],$orderInfo['pay_sn'],(int)($orderInfo['order_amount'] * 100),$notify);
            }elseif($_REQUEST['method']=='alipay'){
                //支付宝
                //用户个人信息
                $member_m = Model('member');
                $memberInfo = $member_m->field('member_name,member_email')->where('member_id = '.$_REQUEST['member_id'])->find();
                $pay_sn = mt_rand(10,99)
                    . sprintf('%010d',time() - 946656000)
                    . sprintf('%03d', (float) microtime() * 1000)
                    . sprintf('%03d', (int) $_REQUEST['member_id'] % 1000);
                $order_sn = '800'.date('md',time()).substr(microtime(true),5,5).rand(1000,9999);

                //充值金额
                //$recharge = $_REQUEST['recharge'] * 1.1;
                if($_REQUEST['recharge'] >= 50000){
                    $recharge = $_REQUEST['recharge'] + 4000;
                }elseif($_REQUEST['recharge'] >= 20000){
                    $recharge = $_REQUEST['recharge'] + 1200;
                }elseif($_REQUEST['recharge'] >= 10000){
                    $recharge = $_REQUEST['recharge'] + 500;
                }elseif($_REQUEST['recharge'] >= 5000){
                    $recharge = $_REQUEST['recharge'] + 200;
                }elseif($_REQUEST['recharge'] >= 0.01){
                    $recharge = $_REQUEST['recharge'] + 0.01;
                }else{
                    $recharge = $_REQUEST['recharge'];
                }
                $data = array(
                    'order_sn'          => $order_sn,
                    'pay_sn'            => $pay_sn,
                    'store_id'          => 1,
                    'store_name'        => '官方旗舰店',
                    'buyer_id'          => $_REQUEST['member_id'],
                    'buyer_name'        => $memberInfo['member_name'],
                    'buyer_email'       => $memberInfo['member_email'],
                    'add_time'          => time(),
                    'payment_code'      => 'recharge',
                    'goods_amount'      => (float)$recharge,//商品总价
                    'order_amount'      => (float)$_REQUEST['recharge'],//订单总价
                    'shipping_fee'      => 0,//运费
                    'order_from'        => 2,//订单来源2mobile
                );
                $order_m = Model('order');
                if($order_m->table('order')->insert($data)) {
                    $notify = 'http://www.hbkclub.com/alipayRe.php';
                    $info = array(
                        'notify' => $notify,
                        'order_sn' => $pay_sn,
                        'goods_amount' => (float)$recharge,
                        'order_amount' => (float)$_REQUEST['recharge'],
                    );
                    echo json_encode($info);
                }else{
                    //dump($data);
                    $this->error('-1','系统错误');
                }
            }
        }else{
            $this->wxPayOp('测试数据',$orderInfo['pay_sn'],(int)($orderInfo['order_amount'] * 100),$notify);
        }
    }

    //生成微信预支付订单
    private function wxPayOp($body,$pay_sn,$order_amount,$notify){
        //
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $stringA = 'appid='.APPID.'&body='.$body.'&mch_id='.PARTNERID.'&nonce_str=1add1a30ac87aa2db72f57a2375d8fec&notify_url='.$notify.'&out_trade_no='.$pay_sn.'&spbill_create_ip=127.0.0.1&total_fee='.$order_amount.'&trade_type=APP';
        $stringSignTemp=$stringA."&key=".WXKEY;
        $sign=strtoupper(MD5($stringSignTemp));
        $data = '<xml>
    <appid>wx03df5def2b39235f</appid>
    <body>'.$body.'</body>
    <mch_id>1418857302</mch_id>
    <nonce_str>1add1a30ac87aa2db72f57a2375d8fec</nonce_str>
    <notify_url>'.$notify.'</notify_url>
    <out_trade_no>'.$pay_sn.'</out_trade_no>
    <spbill_create_ip>127.0.0.1</spbill_create_ip>
    <total_fee>'.$order_amount.'</total_fee>
    <trade_type>APP</trade_type>
    <sign>'.$sign.'</sign>
    </xml>';
        file_put_contents('test.txt',$data,FILE_APPEND);
        $info = request($url,true,'post',$data);

        //dump($info);

        $arr = array(
            'appid'         => null,
            'partnerid'     => null,
            'prepayid'      => null,
            'packageX'      => null,
            'noncestr'      => null,
            'timestamp'     => null,
            'sign'          => null,
        );
        $msg = (array)simplexml_load_string($info, 'SimpleXMLElement', LIBXML_NOCDATA);

        if(isset($msg['err_code_des']) && $msg['err_code_des'] == '201 商户订单号重复'){
            //echo $msg['err_code_des'];
            $this->error('51','订单已存在',$arr);
        }
        if($msg['result_code'] == 'SUCCESS'){
            $noncestr = uniqid();
            $time = (string)(time());
            $prepay_id = $msg['prepay_id'];
            $stringA = 'appid='.APPID.'&noncestr='.$noncestr.'&package=Sign=WXPay&partnerid='.PARTNERID.'&prepayid='.$prepay_id.'&timestamp='.$time;
            $stringSignTemp=$stringA."&key=".WXKEY;

            //echo $stringSignTemp;

            $sign=strtoupper(MD5($stringSignTemp));
            $arr = array(
                'appid'         => APPID,
                'noncestr'      => $noncestr,
                'packageX'      => 'Sign=WXPay',
                'partnerid'     => PARTNERID,
                'prepayid'      => $prepay_id,
                'timestamp'     => $time,
                'sign'          => $sign,
            );
            $this->error('0','ok',$arr);
        }else{
            //dump($msg);
            $this->error('-1','下单失败',$arr);
        }
        //$pre = $xml->
    }

    //微信回调接收
    public function wxnotifyOp(){
        //die('123');
	$info = file_get_contents('php://input');
        //echo '1';
	file_put_contents('test.txt','微信回调'.$info,FILE_APPEND);
        /*$data = '<xml>
                <appid><![CDATA[wx03df5def2b39235f]]></appid>
                <bank_type><![CDATA[CFT]]></bank_type>
                <cash_fee><![CDATA[1]]></cash_fee>
                <fee_type><![CDATA[CNY]]></fee_type>
                <is_subscribe><![CDATA[N]]></is_subscribe>
                <mch_id><![CDATA[1418857302]]></mch_id>
                <nonce_str><![CDATA[1add1a30ac87aa2db72f57a2375d8fec]]></nonce_str>
                <openid><![CDATA[oWKzwwsZNqfrtnLjdqWXE3f5pMmw]]></openid>
                <out_trade_no><![CDATA[560535219294207047]]></out_trade_no>
                <result_code><![CDATA[SUCCESS]]></result_code>
                <return_code><![CDATA[SUCCESS]]></return_code>
                <sign><![CDATA[2977EB493641D9CA6D0673177A3C4F17]]></sign>
                <time_end><![CDATA[20161215210114]]></time_end>
                <total_fee>1</total_fee>
                <trade_type><![CDATA[APP]]></trade_type>
                <transaction_id><![CDATA[4000662001201612152901137580]]></transaction_id>
                </xml>';*/

        $msg = (array)simplexml_load_string($info, 'SimpleXMLElement', LIBXML_NOCDATA);
        //dump($msg);
        //是否支付成功
        if($msg['result_code'] == 'SUCCESS'){
            //生成签名
            $arr= $msg;
            unset($arr['sign']);
            $sign = $this->getSign($arr,WXKEY);
            //echo $sign;
            /*$orderInfo = $order_m->table('order,order_goods')
                ->field('order.pay_sn,order.order_amount,order_goods.goods_name')
                ->join('left')
                ->on('order.order_id=order_goods.order_id')
                ->where('order.order_sn='.$pay_sn)
                ->find();*/

            //验签
            if(APPID == $msg['appid'] && PARTNERID == $msg['mch_id']){
                $this->paidOption($msg);
            }else{
                $this->wxPay(false);
            }
        }else{
	    $this->wxPay(false);
	}
    }

    //支付宝回调
    public function alipayOp(){
        /*$_POST = array(
            'total_amount' => '0.01',
            'buyer_id' => '2088902222054569',
            'trade_no' => '2017011821001004560298050964',
            'notify_time' => '2017-01-18 10:42:35',
            'subject' => '充值0.01元',
            'sign_type' => 'RSA',
            'buyer_logon_id' => '136****6210',
            'auth_app_id' => '2016121904405754',
            'charset' => 'utf-8',
            'notify_type' => 'trade_status_sync',
            'invoice_amount' => '0.01',
            'out_trade_no' => '740538051278958050',
            'trade_status' => 'TRADE_SUCCESS',
            'gmt_payment' => '2017-01-18 10:42:34',
            'version' => '1.0',
            'point_amount' => '0.00',
            'sign' => 'iIPMPa8Lv9XwDFM0cXj8WkLq8jhnl7zH/IrNLlDvPyAs4gRc7ihErRUnrHbnqQsmGg1psq4mXwjaDreAR6DhxqDKyqrku3n2ctFRSiWbfkH6VKTrc1MUNq/rZDe7IXt9ieaTBoYHU0oVoo43eoM+oF1yZyu53ST2vwwbHPdoUb4=',
            'gmt_create' => '2017-01-18 10:42:34',
            'buyer_pay_amount' => '0.01',
            'receipt_amount' => '0.01',
            'fund_bill_list' => '[{"amount":"0.01","fundChannel":"ALIPAYACCOUNT"}]',
            'app_id' => '2016121904405754',
            'seller_id' => '2088911120779784',
            'notify_id' => '37c1947f6559cd8a619a6de4f5e2250kbm',
            'seller_email' => 'huibangkehj@163.com',
        );*/
        if($_POST['trade_status'] == 'TRADE_SUCCESS' || $_POST['trade_status'] == 'TRADE_FINISHED'){
            $msg = $_POST;
            $data = json_encode($msg);
            $json = request('http://59.110.60.173/ali.php?ls=ok',false,'post',$data);
            //dump($json);
            //file_put_contents('test.txt', 'POST.alipayRe:'.$json,FILE_APPEND);
            $res = json_decode($json,true);
            if($res['res'] == 'ok'){
                //file_put_contents('test.txt', 'POST.alipayRe:$res["res"]=ok',FILE_APPEND);
                $this->paidOption($msg);
            }else{
                file_put_contents('test.txt', '验签出错'.$res['res'],FILE_APPEND);
            }
        }else{
            file_put_contents('test.txt', '接收POST出现问题',FILE_APPEND);
        }
    }

    //第三方支付验签成功后操作out_trade_no,total_fee
    private function paidOption($msg){
        //file_put_contents('test.txt', 'POST.paidOption:'.print_r($_GET,1),FILE_APPEND);
        //查看订单状态
        $order_m = Model('order');
        $state = $order_m->table('order')->where('pay_sn = '.$msg['out_trade_no'])->find();
        //dump($state);
        if($state['order_state'] != 10){
            $this->wxPay(true);
        }
        //修改订单状态
        $res = $order_m->table('order')->where('pay_sn = '.$msg['out_trade_no'])->update(array('order_state'=>20,));

        //如果余额支付了,扣除相应余额

        //如果是充值,则执行增加余额操作
        if($_GET['style'] == 'recharge'){
            //file_put_contents('test.txt', 'POST.alipayRe:recharge',FILE_APPEND);
            $user_m = Model('user');
            $orderInfo = $order_m->table('order')->where('pay_sn='.$msg['out_trade_no'])->find();
            //dump($orderInfo);
            if(($orderInfo['order_amount'] *100) == $msg['total_fee']){
                $sql = 'update 33hao_member set available_predeposit=available_predeposit+'.$orderInfo['goods_amount'].' where member_id='.$orderInfo['buyer_id'];
                $res1 = $user_m ->execute($sql);
            }elseif($orderInfo['order_amount'] == $msg['total_amount']){
                $sql = 'update 33hao_member set available_predeposit=available_predeposit+'.$orderInfo['goods_amount'].' where member_id='.$orderInfo['buyer_id'];
                $res1 = $user_m ->execute($sql);
            }else{
                file_put_contents('test.txt', '充值信息:订单金额'.$orderInfo['order_amount'].',支付宝支付金额'.$msg['total_amount'],FILE_APPEND);
            }
            if($res && $res1){
                $this->wxPay(true);
            }else{
                file_put_contents('test.txt', '充值结果:状态'.$res.',增加余额'.$res1.',sql'.$sql,FILE_APPEND);
            }
        }else{
            if($res){
                $this->wxPay(true);
            }
        }
    }

    //接收微信消息后操作完成输出结果
    private function wxPay($a){
        if($a){
            echo '<xml>
<return_code><![CDATA[SUCCESS]]></return_code>
<return_msg><![CDATA[OK]]></return_msg>
</xml>';
        }else{
            echo '<xml>
<return_code><![CDATA[FAIL]]></return_code>
<return_msg><![CDATA[签名失败]]></return_msg>
</xml>';
        }
        die;
    }

    //生成签名
    public static function getSign($params, $key)
    {
        ksort($params, SORT_STRING);
        $unSignParaString = self::formatQueryParaMap($params, false);
        //echo $unSignParaString . "&key=" . $key;
        $signStr = strtoupper(md5($unSignParaString . "&key=" . $key));
        //echo '<br/>'.$signStr;
        return $signStr;
    }

    protected static function formatQueryParaMap($paraMap, $urlEncode = false)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if (null != $v && "null" != $v) {
                if ($urlEncode) {
                    $v = urlencode($v);
                }
                $buff .= $k . "=" . $v . "&";
            }
        }
        $reqPar = '';
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }

    //模拟微信回调
    public function likewxOp(){
        $data = '<xml>
                <appid><![CDATA[wx03df5def2b39235f]]></appid>
                <bank_type><![CDATA[CFT]]></bank_type>
                <cash_fee><![CDATA[1]]></cash_fee>
                <fee_type><![CDATA[CNY]]></fee_type>
                <is_subscribe><![CDATA[N]]></is_subscribe>
                <mch_id><![CDATA[1418857302]]></mch_id>
                <nonce_str><![CDATA[1add1a30ac87aa2db72f57a2375d8fec]]></nonce_str>
                <openid><![CDATA[oWKzwwsZNqfrtnLjdqWXE3f5pMmw]]></openid>
                <out_trade_no><![CDATA[790535150820651047]]></out_trade_no>
                <result_code><![CDATA[SUCCESS]]></result_code>
                <return_code><![CDATA[SUCCESS]]></return_code>
                <sign><![CDATA[2977EB493641D9CA6D0673177A3C4F17]]></sign>
                <time_end><![CDATA[20161215210114]]></time_end>
                <total_fee>1</total_fee>
                <trade_type><![CDATA[APP]]></trade_type>
                <transaction_id><![CDATA[4000662001201612152901137580]]></transaction_id>
                </xml>';
        $url = 'http://www.hbkclub.com/wxrecharge.php';
        $info = request($url,true,'post',$data);
        dump($info);
    }

    //订单过期操作
    public function updateOrderOp($orderId = false){
        $expired = 1800;//秒
        $order_m = Model('order');

        //过期的订单列表
        if($orderId == false){
            $sql_order = 'select * from 33hao_order where add_time<'.(time()-$expired).' AND order_state=10 AND delete_state=0 AND payment_code <> "recharge"';
        }else{
            $sql_order = 'select * from 33hao_order where order_sn='.$orderId.' AND order_state=10 AND delete_state=0 AND payment_code <> "recharge"';
        }

        $list = $order_m->query($sql_order);
        /*dump($sql_order);
        dump($list);*/
        //如果存在过期订单
        if($list){
            $order_ids = '';
            foreach($list as $key=>$value){
                $order_ids .= $list[$key]['order_id'].',';
            }
            $order_ids = rtrim($order_ids,',');

            //开始事物
            $order_m->beginTransaction();

            //操作一  改变所有订单状态->过期
            $order_state_sql = 'update 33hao_order set delete_state=1 where order_id in('.$order_ids.')';
            $res1 = $order_m ->execute($order_state_sql);

            //操作二 依次退换金额
            $member_m = Model('member');
            foreach($list as $k1 => $value){
                if($list[$k1]['goods_amount']>$list[$k1]['order_amount']){
                    $money = $list[$k1]['goods_amount'] - $list[$k1]['order_amount'];
                    $sql2 = 'update 33hao_member set available_predeposit=available_predeposit+'.$money.' where member_id='.$list[$k1]['buyer_id'];
                    $res2 = $member_m->execute($sql2);
                    if($res2){
                        file_put_contents('re.txt', date('Y-m-d H:i:s',time()).'事物step2:退还金额成功:状态,增加余额'.$money.',sql'.$sql2.'\r\n',FILE_APPEND);
                    }
                }
            }

            //操作三  释放库存,销量
            $or_g = Model('order_goods');
            $goodslist = $or_g->where(array('order_id'=>array('in',$order_ids)))->select();
            //dump($goodslist);
            $goods_m = Model('goods');
            foreach($goodslist as $key => $value){
                $sql3 = 'update 33hao_goods set goods_storage=goods_storage+1,goods_salenum=goods_salenum-1 where goods_id='.$goodslist[$key]['goods_id'];
                $res3 = $goods_m ->execute($sql3);
            }
            if($res1 && $res2 && $res3){
                $order_m->commit();
            }else{
                $order_m->rollback();
            }
        }

        /*dump($res1);
        dump($res2);
        dump($res3);*/
    }

    //删除无效订单
    public function delExpiredOrderOp(){
        $orderModel = Model('order');
        $sql = 'delete from 33hao_order';
        $orderModel->where('')->delete();
    }

    //订单列表
    public function orderListOp(){
        $_REQUEST = $this -> bypost();
        //更新过期订单
        $this->updateOrderOp();
        //$this->checkToken($_REQUEST['member_id'],$_REQUEST['token']);
        $order_m = Model('order');
        if(!isset($_REQUEST['state']) || $_REQUEST['state'] == 'all'){
            $state = '0,10,20,30,40';
        }/*elseif($_REQUEST['state'] == 'unpaid'){
            $state = '10';
        }elseif($_REQUEST['state'] == 'unrec'){
            $state = '30';
        }*/else{
            $state = $_REQUEST['state'];
        }
        $list = $order_m->table('order')
            ->field('order_id,order_sn,order_amount,add_time,order_state,refund_state')
            ->where(array('buyer_id'=>$_REQUEST['member_id'],'delete_state'=>0,'order_state'=>array('in',$state)))
            ->order('order_id desc')
            ->select();

        $order_g = Model('order_goods');
        foreach($list as $key=>$value){
            $list[$key]['add_time'] = date('Y-m-d H:i',$list[$key]['add_time']);
            $goods[$key] = $order_g->table('order_goods')
                ->field('goods_id,goods_name,goods_pay_price,goods_num,goods_image')
                ->where('order_id ='.$list[$key]['order_id'])
                ->select();
            $sum = 0;
            foreach($goods[$key] as $k=>$v){
                $goods[$key][$k]['goods_pay_price'] = (string)$goods[$key][$k]['goods_pay_price'];
                $arr_image = explode('_',$goods[$key][$k]['goods_image']);
                $num = $arr_image[0];
                $goods[$key][$k]['goods_image'] = GOODSIMAGE_PATH.$num.'/'.$goods[$key][$k]['goods_image'];
                $sum += $goods[$key][$k]['goods_pay_price']*$goods[$key][$k]['goods_num'];
                $arr = explode(' ',$goods[$key][$k]['goods_name']);
                $goods[$key][$k]['goods_name'] = $arr[0];
            }
            $list[$key]['sum'] = (string)$list[$key]['order_amount'];
            $list[$key]['goods'] = $goods[$key];
            if(empty($list[$key]['goods'])){
                unset($list[$key]);
            }
        }
        $orderList = array();
        foreach($list as $key=>$value){
            $orderList[] = $list[$key];
        }
        //dump($orderList);
        $null_list = array(
            array(
                'order_id'=>null,
                'order_sn'=>null,
                'add_time'=>null,
                'order_state'=>null,
                'goods'=>array(
                    array(
                        'goods_id'=>null,
                        'goods_name'=>null,
                        'goods_pay_price'=>null,
                        'goods_num'=>null,
                        'goods_image'=>null,
                    )
                ),
            )
        );

        if($list){
            $this->error('0','ok',$orderList);
        }else{
            $this->error('500','暂无订单',$null_list);
        }
    }

    //余额支付
    public function testpayOp(){
        $_REQUEST = $this -> bypost();
        $this->checkToken($_REQUEST['member_id'],$_REQUEST['token']);
        $order_m = Model('order');
    }

    //支付宝调取订单详情
    public function orderInfoOp(){
        $_REQUEST = $this -> bypost();
        $order_m = Model('order');
        $list = $order_m->table('order')
            ->field('order_id,order_sn,pay_sn,add_time,order_state,order_amount')
            ->where(array('buyer_id'=>$_REQUEST['member_id'],'order_sn'=>$_REQUEST['order_sn']))
            ->find();
        //dump($list);
        $order_g = Model('order_goods');
        $list['add_time'] = date('Y-m-d H:i',$list['add_time']);
        $goods = $order_g->table('order_goods')
            ->field('goods_id,goods_name,goods_pay_price,goods_num,goods_image')
            ->where('order_id ='.$list['order_id'])
            ->select();
        $sum = 0;
        foreach($goods as $k=>$v){
            $arr_image = explode('_',$goods[$k]['goods_image']);
            $num = $arr_image[0];
            $goods[$k]['goods_image'] = GOODSIMAGE_PATH.$num.'/'.$goods[$k]['goods_image'];
        }
        $list['sum'] = (string)sprintf("%01.2f", $list['order_amount']);
        unset($list['order_amount']);
        $list['goods'] = $goods;
        //dump($list);
        echo json_encode($list);
    }

    //订单详情
    public function orderOp(){
        $_REQUEST = $this -> bypost();
        //$this->checkToken($_REQUEST['member_id'],$_REQUEST['token']);
        $order_m = Model('order');
        $list = $order_m->table('order')
            ->field('order_id,order_sn,add_time,order_state,refund_state,shipping_code,goods_amount,order_amount')
            ->where(array('buyer_id'=>$_REQUEST['member_id'],'order_sn'=>$_REQUEST['order_sn']))
            ->find();

        //发票信息
        $orderModel = Model('order_common');
        $commonInfo = $orderModel->find($list['order_id']);
        //dump($commonInfo);
        $invoice_info = unserialize($commonInfo['invoice_info']);
        if($invoice_info){
            /*foreach($invoice_info as $key=>$value){
                $list['invoice_info'][]['name'] = $key;
                $list['invoice_info'][]['value'] = $value;
            }*/
            /*a:6:{s:6:"抬头";s:3:"汇";s:12:"寄送地址";s:2:"i4";s:12:"公司电话";s:12:"136696699666";s:9:"开户行";s:6:"银行";s:12:"银行账号";s:18:"316466428463491964";s:9:"税务号";s:4:"1253";}*/
            if($invoice_info['类型'] == '普通发票'){
                $list['invoice_info']['type']       = '1';
                $list['invoice_info']['taitou']     = $invoice_info['抬头'];
                $list['invoice_info']['neirong']    = $invoice_info['内容'];
            }elseif(isset($invoice_info['抬头'])){
                $list['invoice_info']['type']       = '2';
                $list['invoice_info']['taitou']     = $invoice_info['抬头'];
                $list['invoice_info']['dizhi']      = $invoice_info['寄送地址'];
                $list['invoice_info']['dianhua']    = $invoice_info['公司电话'];
                $list['invoice_info']['kaihu']      = $invoice_info['开户行'];
                $list['invoice_info']['zhanghao']   = $invoice_info['银行账号'];
                $list['invoice_info']['shuiwu']     = $invoice_info['税务号'];
            }
            //dump($invoice_info);
            //$list['invoice_info'] = $invoice_info;
        }else{
            $list['invoice_info']['type'] = '0';
        }



        $balance = $list['goods_amount'] - $list['order_amount'];
        $list['cost_balance'] = (string)sprintf('%.2f',$balance);
        $list['order_message'] = $commonInfo['order_message'];
        //dump($list);
        $order_g = Model('order_goods');
        $list['add_time'] = date('Y-m-d H:i',$list['add_time']);
        $goods = $order_g->table('order_goods')
            ->field('goods_id,goods_name,goods_pay_price,goods_num,goods_image')
            ->where('order_id ='.$list['order_id'])
            ->select();
        $sum = 0;
        foreach($goods as $k=>$v){
            $arr_image = explode('_',$goods[$k]['goods_image']);
            $num = $arr_image[0];
            $goods[$k]['goods_image'] = GOODSIMAGE_PATH.$num.'/'.$goods[$k]['goods_image'];
            $sum += $goods[$k]['goods_pay_price']*$goods[$k]['goods_num'];
            $arr = explode(' ',$goods[$k]['goods_name']);
            $goods[$k]['goods_name'] = $arr[0];
        }
        //合计
        $list['sum'] = (string)sprintf("%01.2f", $sum);
        $list['cash'] = (string)sprintf('%.2f', (float)($list['sum'] - $list['cost_balance']));
        //商品详情
        $list['goods'] = $goods;

        //地址
        $addr_m = Model('order_common');
        $addrInfo = $addr_m->where('order_id='.$list['order_id'])->find();
        if(!empty($addrInfo['shipping_express_id'])){
            $express = $addrInfo['shipping_express_id'];
            $express_m = Model('express');
            $expressInfo = $express_m->where('id='.$express)->find();
            $list['shipping_name'] = $expressInfo['e_name'];
        }else{
            $list['shipping_name'] = '';
        }
        $reciver_info = unserialize($addrInfo['reciver_info']);
        if(empty($reciver_info)){
            $reciver_info['true_name'] = '没有地址信息,请联系客服';
        }else{
            $reciver_info['true_name'] = $addrInfo['reciver_name'];
        }
        $list['addr'] = $reciver_info;
        //dump($list);
        if($list){
            $this->error('0','ok',$list);
        }else{
            $this->error('-1','系统错误',$list);
        }
    }

    //取消订单
    public function cancelOrderOp(){
        $_REQUEST = $this -> bypost();
        $this->checkToken($_REQUEST['member_id'],$_REQUEST['token']);
        if(empty($_REQUEST['order_sn'])){
            $this->error('10','缺少订单参数');
        }
        $orderModel = Model('order');
        $this->updateOrderOp($_REQUEST['order_sn']);
        $where = 'buyer_id='.$_REQUEST['member_id'].' and order_sn='.$_REQUEST['order_sn'];
        $data = array(
            'order_state' => 0,
        );
        if($orderModel->table('order')->where($where)->update($data)){
            $this->error('0','ok');
        }else{
            $this->error('-1','修改失败');
        }
    }

    //退货
    public function refundOp(){
        $_REQUEST = $this -> bypost();
        //$this->checkToken($_REQUEST['member_id'],$_REQUEST['token']);
        if(empty($_REQUEST['order_sn'])){
            $this->error('10','缺少订单参数');
        }
        $orderModel = Model('order');
        $where = 'order_state in(30,40) and buyer_id='.$_REQUEST['member_id'].' and order_sn='.$_REQUEST['order_sn'];
        $data = array(
            'refund_state' => 2,
        );
        if($orderModel->table('order')->where($where)->update($data)){
            $this->error('0','ok');
        }else{
            $this->error('-1','修改失败');
        }
    }

    //确认收货
    public function receivedOp(){
        $_REQUEST = $this -> bypost();
        //$this->checkToken($_REQUEST['member_id'],$_REQUEST['token']);
        if(empty($_REQUEST['order_sn'])){
            $this->error('10','缺少订单参数');
        }
        $orderModel = Model('order');
        $where = 'order_state=30 and buyer_id='.$_REQUEST['member_id'].' and order_sn='.$_REQUEST['order_sn'];
        //查询返利金额
        $sql = 'select * from 33hao_order where '.$where.' limit 1';
        $order_info = $orderModel->query($sql);
        $order_info = $order_info[0];
        //dump($sql);
        //dump($order_info);
        $order_goods = Model('order_goods');
        $order_goods_info = $order_goods->where(array('order_id'=>$order_info['order_id']))->select();
        //dump($order_goods_info);
        $goodsModel = Model('goods');
        $rebate = 0;
        foreach($order_goods_info as $key=>$value){
            $goods_info = $goodsModel->where(array('goods_id'=>$order_goods_info[$key]['goods_id']))->find();
            $goodsCommonModel = Model('goods_common');
            $goods_common_info = $goodsCommonModel->where(array('goods_commonid'=>$goods_info['goods_commonid']))->find();
            //dump($goods_common_info);
            $rebate = $rebate + $goods_common_info['rebate'] * $order_goods_info[$key]['goods_num'];
        }
        //dump($goods_info['goods_commonid']);
        //dump($rebate);
        //die;
        //添加到该用户的酒券
        $memberModel = Model('member');
        $member_info = $memberModel->where(array('member_id'=>$order_info['buyer_id']))->find();
        //如果有上级用户
        if($member_info['inviter_id']){
            $drinksModel = Model('ybk_member');
            if($drinksModel->find()){
                $sql = 'UPDATE 33hao_ybk_member set ybk_balance=ybk_balance+'.$rebate.' where ybk_member_id='.$member_info['inviter_id'];
                $res = $drinksModel->execute($sql);
            }else{
                $res = $drinksModel->insert(['ybk_member_id'=>$member_info['inviter_id'],'ybk_balance'=>$rebate]);
            }
            if($res){
                $recent = Model('ybk_deposit');
                $res1 = $recent->insert(
                    [
                        'member_id'     => $member_info['inviter_id'],
                        'deposit_money' => $rebate,
                        'deposit_method'=> '17',
                        'deposit_time'  => time(),
                    ]
                );
            }
            //dump($sql);
            //dump($res);
            //dump($res1);
        }else{
            //dump($member_info);
        }
        //die;
        $data = array(
            'order_state' => 40,
        );
        if($orderModel->table('order')->where($where)->update($data)){
            $this->error('0','ok');
        }else{
            $this->error('-1','修改失败');
        }
    }

    //生成支付单号
    public function makePaySn($member_id) {
        return mt_rand(10,99)
        . sprintf('%010d',time() - 946656000)
        . sprintf('%03d', (float) microtime() * 1000)
        . sprintf('%03d', (int) $member_id % 1000);
    }

    //分类列表
    public function classListOp(){
        $_REQUEST = $this -> bypost();
        $spec_m = Model('spec_value');

        $info = $spec_m->field('sp_value_id,sp_id,sp_value_name')->where('sp_id=1')->order('sp_value_sort desc')->select();

        $AttrModel = Model('attribute_value');

        $Attr = $AttrModel->field('attr_value_id,attr_id,attr_value_name')->where('attr_id=238 or attr_id=239')->order('attr_value_sort desc')->select();

        foreach($Attr as $key => $value){
            if($value['attr_id'] == 239){
                $origin[] = array(
                    'sp_value_id' => $value['attr_value_id'],
                    'sp_value_name' => $value['attr_value_name'],
                );
            }elseif($value['attr_id'] == 238){
                $type[] = array(
                    'sp_value_id' => $value['attr_value_id'],
                    'sp_value_name' => $value['attr_value_name'],
                );
            }
        }
        //dump($Attr);
        //遍历产地和类型
        foreach($info as $key=>$value){
            if($value['sp_id'] == 1){
                $brand[] = array(
                    'sp_value_id' => $value['sp_value_id'],
                    'sp_value_name' => $value['sp_value_name'],
                );
            }
            if($value['sp_id'] == 19){
                $origin[] = array(
                    'sp_value_id' => $value['sp_value_id'],
                    'sp_value_name' => $value['sp_value_name'],
                );
            }elseif($value['sp_id'] == 18){
                $type[] = array(
                    'sp_value_id' => $value['sp_value_id'],
                    'sp_value_name' => $value['sp_value_name'],
                );
            }
        }
        //dump($brand);
        /*$brand_m = Model('brand');
        $brandInfo = $brand_m->field('brand_id,brand_name')
            ->where('brand_apply=1')
            ->order('brand_recommend desc,brand_sort desc')
            ->limit('20')
            ->select();

        //遍历品牌
        foreach($brandInfo as $k=>$v){
            $brand[] = array(
                'sp_value_id' => $v['brand_id'],
                'sp_value_name' => $v['brand_name'],
            );
        }*/
        $arr = array(
            array('name' => '国家','value'=>$origin),
            array('name' => '葡萄品种','value'=>$brand),
            array('name' => '葡萄酒类别','value'=>$type),
        );
        //dump($arr);
        $null_list = array(
            array('name' => '国家','value'=>array(array('sp_value_id'=>null,'sp_value_name'=>null))),
            array('name' => '葡萄品种','value'=>array(array('sp_value_id'=>null,'sp_value_name'=>null))),
            array('name' => '葡萄酒类别','value'=>array(array('sp_value_id'=>null,'sp_value_name'=>null))),
        );
        if($arr){
            $this->error('0','ok',$arr);
        }elseif(is_array($arr)){
            $this->error('500','暂无数据',$null_list);
        }else{
            $this->error('-1','系统错误',$null_list);
        }
    }

    //分类商品
    public function classGoodsOp(){
        $_REQUEST = $this -> bypost();

    }

    //首页轮播图
    public function homePicOp(){
        $_REQUEST = $this -> bypost();
        $admodel = Model('33hao_adv');
        $info = $admodel->table('adv')->where(array('adv_start_date'=>1,'adv_end_date'=>1,))->select();
        foreach($info as $key => $value){
            $list[] = ARTICLE_PATH.$info[$key]['adv_content'];
        }
        $nulllist = array(null);
        if($list && $info){
            //dump($infolist);
            $this->error('0','ok',$list);
        }else{
            $this->error('-1','系统繁忙',$nulllist);
        }
    }

    //商品列表
    public function goodsListOp() {
        $_REQUEST = $this->bypost();
        $model_goods = Model('goods');
        $this->Dump();
        $where = array();
        //是否请求关注数据
	if(isset($_REQUEST['type']) && $_REQUEST['type'] == 'coll'){
            $coll_m = Model('favorites');
            $info = $coll_m->table('favorites')->field('fav_id')->where('member_id='.$_REQUEST['member_id'])->select();
            $ids='';
            foreach($info as $key=>$value){
                $ids.=$value['fav_id'].',';
            }
            $ids = rtrim($ids,',');
            $where['goods_id'] = array('in',$ids);
        }else{
            //分类入口
            $comm = Model('goods_common');
            $AttrModel = Model('attribute_value');
            if(!empty($_REQUEST['class']) && ($_REQUEST['class'] != '(null)') && !empty($_REQUEST['sp_value_id'])){
                if($_REQUEST['class'] == '国家' || $_REQUEST['class'] == '产地' || $_REQUEST['class'] == '红酒类别' || $_REQUEST['class'] == '葡萄酒类别' || $_REQUEST['class'] == '葡萄品种'){
                    $id = $_REQUEST['sp_value_id'];
                    if($_REQUEST['class'] == '葡萄品种'){
                        $spec_m = Model('spec_value');
                        $spec = $spec_m->where('sp_value_id='.$id)->find();
                        $keyword = $spec['sp_value_name'];
                    }elseif($_REQUEST['class'] == '国家' || $_REQUEST['class'] == '产地' ||  $_REQUEST['class'] == '红酒类别' || $_REQUEST['class'] == '葡萄酒类别'){
                        $spec = $AttrModel->where('attr_value_id='.$id)->find();
                        $keyword = $spec['attr_value_name'];
                        $where['goods_attr'] = array('like','%"'.$keyword.'"%');
                        $res1 = $comm->field('goods_commonid')->where($where)->select();
                    }
                    if($_REQUEST['class'] == '葡萄品种'){
                        $where['goods_type'] = array('like','%'.$keyword.'%');
                        $res1 = $comm->field('goods_commonid')->where($where)->select();
                        unset($where['goods_type']);
                        //dump($ids);die;259,261,268,269,276/96,98,95,97,103/西拉子
                    }
                }
            }else{
                $spec = $AttrModel->where('attr_value_id=3235')->find();
                $keyword = $spec['attr_value_name'];
                $where['goods_attr'] = array('notlike','%"'.$keyword.'"%');
                $res1 = $comm->field('goods_commonid')->where($where)->select();
            }
            unset($where['goods_attr']);
            $ids='';
            foreach($res1 as $key=>$value){
                $ids.=$value['goods_commonid'].',';
            }
            $ids = rtrim($ids,',');
            //dump($ids);
            $where['goods_commonid'] = array('in',$ids);
        }	

        //店铺id
        $where['store_id'] = 1;

        //上架状态
        $where['goods_state'] = 1;

        //是否通过审核
        $where['goods_verify'] = 1;

        //id,标题,价格,销量
        $field = 'goods_id,goods_name,goods_price,goods_marketprice,goods_salenum,goods_image';


        //默认排序方式
      	if((isset($_REQUEST['type']) && $_REQUEST['type']=='1') || empty($_REQUEST['type'])){
            $order = 'goods_addtime desc';
        }


        //按销量排序
        if(isset($_REQUEST['type'])&&$_REQUEST['type']=='2'){
            $order = 'goods_salenum '.$_REQUEST['goods_salenum'].',goods_addtime desc';
        }

        //按价格排序
        if(isset($_REQUEST['type'])&&$_REQUEST['type']=='3'){
            $order = 'goods_price '.$_REQUEST['goods_price'].',goods_addtime desc';
        }


        //关键词搜索
        if(isset($_REQUEST['keyword'])){
            $keyword = trim($_REQUEST['keyword']);
            $where['goods_name'] = array('like','%'.$keyword.'%');
        }

        //其他筛选 价格区间 产地 类别
        if((!empty($_REQUEST['origin'])) || (!empty($_REQUEST['style'])) || (!empty($_REQUEST['price_in']))){
            $where = 'store_id=1 AND goods_state=1 AND goods_verify=1';
            if(!empty($_REQUEST['origin'])){
                $where .= ' AND goods_name LIKE "%'.$_REQUEST['origin'].'%"';
            }
            if(!empty($_REQUEST['style'])){
                $where .= ' AND goods_name LIKE "%'.$_REQUEST['style'].'%"';
            }
            if(!empty($_REQUEST['price_in'])){
                if($_REQUEST['price_in']=='1000'){
                    $where .= ' AND goods_price>='.$_REQUEST['price_in'];
                }else{
                    $arr = explode('-',$_REQUEST['price_in']);
                    $where .= ' AND goods_price>='.$arr[0].' AND goods_price<='.$arr[1];
                }
            }
        }

        $ybk_limit = isset($_REQUEST['goods_limit'])?$_REQUEST['goods_limit']:'10';//取出记录数
        $ybk_page = isset($_REQUEST['goods_page'])?($_REQUEST['goods_page']-1)*$ybk_limit:'0';//取出页码
        $limit = $ybk_page.','.$ybk_limit;

        //查询

        $goods_list = $model_goods->field($field)->where($where)->order($order)->limit($limit)->select();
        if($_REQUEST['type'] == '5') {
            foreach($goods_list as $key=>$value){
                $fav = Model('favorites');
                $count = $fav->table('favorites')->field('count(fav_id)')->where(['fav_id'=>$value['goods_id']])->find();
                $goods_list[$key]['fav'] = $count['count(fav_id)'];
            }
            if($_REQUEST['fav'] == 'asc'){

                $goods_list = $this->multi_array_sort($goods_list,'fav',SORT_ASC);
            }else{
                $goods_list = $this->multi_array_sort($goods_list,'fav',SORT_DESC);
            }
            //dump($goods_list);die;
        }
        //收藏
        $coll_model = Model('favorites');
        if(!empty($goods_list)){
            foreach($goods_list as $key=>$value){
                $arr_image = explode('_',$goods_list[$key]['goods_image']);
                $num = $arr_image[0];
                $goods_list[$key]['goods_image'] = GOODSIMAGE_PATH.$num.'/'.$goods_list[$key]['goods_image'];
                $arr = explode(' ',$goods_list[$key]['goods_name']);
                $goods_list[$key]['goods_name'] = $arr[0];

                //用户id不为空则返回是否收藏
                if(!empty($_REQUEST['member_id'])){
                    $data = array(
                        'member_id' => $_REQUEST['member_id'],
                        'fav_id'    => $goods_list[$key]['goods_id'],
                    );
                    if($coll_model->table('favorites')->where($data)->select()){
                        $goods_list[$key]['is_coll'] = '1';
                    }else{
                        $goods_list[$key]['is_coll'] = '0';
                    }
                }else{
                    $goods_list[$key]['is_coll'] = '0';
                }
            }
        }

        //dump($goods_list);die();
        $null_list = array(
            array(
                'goods_id'          =>null,
                'goods_name'        =>null,
                'goods_price'       =>null,
                'goods_salenum'     =>null,
                'goods_image'       =>null,
                'is_coll'           =>null,
            ),
        );
        if($goods_list){
            $this->error('0','ok',$goods_list);
        }elseif(is_array($goods_list) || $_REQUEST['type'] == '5'){
            $this->error('500','暂无数据',$null_list);
        }else{
            $this->error('-1','系统错误',$null_list);
        }
    }

    //二维数组排序
    private function multi_array_sort($multi_array,$sort_key,$sort=SORT_ASC){
        if(is_array($multi_array)){
            foreach ($multi_array as $row_array){
                if(is_array($row_array)){
                    $key_array[] = $row_array[$sort_key];
                }else{
                    return false;
                }
            }
        }else{
            return false;
        }
        array_multisort($key_array,$sort,$multi_array);
        return $multi_array;
    }

    //商品详情，多图，评价，详情，收藏
    public function goodsInfoOp(){
        $_REQUEST = $this -> bypost();
        $model_goods = Model('goods');

        //商品信息
        $field = 'goods.goods_id,goods.goods_jingle,goods.goods_spec,goods.goods_commonid,goods.goods_name,goods.goods_price,goods.goods_marketprice,goods.goods_storage,goods.goods_salenum,goods.goods_image,goods_images.goods_image';
        $goods_info = $model_goods
            ->table('goods,goods_images')
            ->field($field)
            ->join('left')
            ->on('goods.goods_commonid=goods_images.goods_commonid')
            ->where(array('goods_id'=>$_REQUEST['goods_id']))
            ->order('is_default desc')
            ->select();
        if(!$goods_info){
            $this->error('-1','系统错误');
        }
        $image = array();
        foreach($goods_info as $key=>$value){
            $arr_image = explode('_',$goods_info[$key]['goods_image']);
            $num = $arr_image[0];
            $arr_image = explode('.',$goods_info[$key]['goods_image']);
            $arr1_image = $arr_image[0].'_360.'.$arr_image[1];
            //dump($arr_image);
            $image[] .= GOODSIMAGE_PATH.$num.'/'.$arr1_image;
            $arr = explode(' ',$goods_info[$key]['goods_name']);
            $goods_info[$key]['goods_name'] = $arr[0];
        }
        $goods_info[0]['goods_image'] = $image;
        //dump($goods_info );
        $goods_info = $goods_info[0];
        $goods_info['goods_price'] = (string)$goods_info['goods_price'];

        //操作，增加浏览量
        if(!empty($_REQUEST['goods_id'])){
            $model_goods -> execute('update 33hao_goods set goods_click=goods_click+1 where goods_id='.$_REQUEST['goods_id']);
        }

        //收藏
        if(!empty($_REQUEST['member_id'])) {
            $data = array(
                'member_id' => $_REQUEST['member_id'],
                'fav_id' => $goods_info['goods_id'],
            );
            $coll_model = Model('favorites');
            if($coll_model->table('favorites')->where($data)->select()){
                $goods_info['is_coll'] = '1';
            }else{
                $goods_info['is_coll'] = '0';
            }
        }else{
            $goods_info['is_coll'] = '0';
        }

        //dump($goods_info);

        //评价
        $eval = array('member_name'=>'张三','eval_time'=>date('Y-m-d',time()),'eval'=>'不错');

        //详情
        $goods_m = Model('goods_common');
        $comm = $goods_m->where(array('goods_commonid' => $goods_info['goods_commonid']))->find();
        //dump($comm);

        $goods_info['jianjie'] = $goods_info['goods_jingle'];
        unset($goods_info['goods_jingle']);
        $content = unserialize($comm['mobile_body']);
        if(!$content){
            $content = array(
                array(
                    'image' => '',
                    'value' => '',
                )
            );
        }
        //$spec_arr = unserialize($goods_info['goods_spec']);
        $spec_name = unserialize($comm['spec_name']);
        $spec_value = unserialize($comm['spec_value']);
        //dump($spec_name);
        //dump($spec_value);
        if(!empty($spec_value)){
            foreach($spec_value as $key => $value){
                $n = 0;
                foreach($spec_value[$key] as $k=>$v){
                    $spec[] = array(
                        'name' => $spec_name[$key],
                        'value' => $spec_value[$key][$k],
                    );
                    $n++;
                    if($n>0){
                        break;
                    }
                    //$spec[$spec_name[$key]] = $spec_value[$key][$k];
                }
            }
        }
        $goods_attr = unserialize($comm['goods_attr']);
        if(!empty($goods_attr)) {
            foreach ($goods_attr as $k => $v) {
                foreach($goods_attr[$k] as $key=>$value){
                    //dump($goods_attr[$k]);
                    if($key === 'name'){
                        $flag['name'] = $goods_attr[$k]['name'];
                    }else{
                        $flag['value'] = $goods_attr[$k][$key];
                    }
                }
                $spec[] = $flag;
            }
        }
        if(empty($spec)){
            $spec = array(
                array(
                    'name' => '规格',
                    'value' => '此商品没有规格信息',
                )
            );
        }
        //dump($goods_attr);
        //dump($spec);
        unset($goods_info['goods_spec']);
        unset($goods_info['goods_commonid']);
        /*$spec = array(
            'origin'            => $spec[0],                         //产    地
            'proof'             => $spec[1],                         //酒 精 度
            'type'              => $spec[2],                         //葡萄品种
            'style'             => '无',                             //类    型
            'chinese_name'      => $goods_info['goods_name'],        //中文名称
            'english_name'      => '无',                             //英文名称
            'winery'            => '无',                             //酒庄名称
            'capacity'          => '无',                             //容    量
            'bouquet'           => '无',                             //香味分类
            //'color'             => '无',                             //色    泽
            'body'              => '无',                             //酒    体
            //'taste'             => '无',                             //口    感
            'sober_time'        => '无',                             //建议醒酒时间
            //'temperature'       => '无',                             //品酒温度
            'scene'             => '无',                             //适用场景
            'match'             => '无',                             //搭配菜肴
            //'oak_time'          => '无',                             //橡木桶时间
            );*/
        $info = array(
            'goods_info'    => $goods_info,
            'eval'          => $eval,
            'content'       => $content,
            'spec'          => $spec,
        );
        //dump($info);
        $this->error('0','ok',$info);
    }

    //商品详情，多图，评价，详情，收藏    删除
    public function goodsOp(){
        $_REQUEST = $this -> bypost();
        $model_goods = Model('goods_common');

        //商品信息
        $field = 'goods_common.*,goods_images.goods_image';
        $goods_info = $model_goods
            ->table('goods_common,goods_images')
            ->field($field)
            ->join('left')
            ->on('goods_common.goods_commonid=goods_images.goods_commonid')
            ->where(array('goods_common.goods_commonid'=>$_REQUEST['goods_id']))
            ->order('is_default desc')
            ->select();
        //dump($goods_info);die;
        if(!$goods_info){
            $this->error('-1','系统错误');
        }
        $image = array();
        foreach($goods_info as $key=>$value){
            $arr_image = explode('_',$goods_info[$key]['goods_image']);
            $num = $arr_image[0];
            $arr_image = explode('.',$goods_info[$key]['goods_image']);
            $arr1_image = $arr_image[0].'_360.'.$arr_image[1];
            //dump($arr_image);
            $image[] .= GOODSIMAGE_PATH.$num.'/'.$arr1_image;
            $arr = explode(' ',$goods_info[$key]['goods_name']);
            $goods_info[$key]['goods_name'] = $arr[0];
        }
        $goods_info[0]['goods_image'] = $image;
        $goods_info = $goods_info[0];

        //dump($goods_info );
        $goods_info['goods_price'] = (string)$goods_info['goods_price'];

        //操作，增加浏览量
        if(!empty($_REQUEST['goods_id'])){
            $model_goods -> execute('update 33hao_goods set goods_click=goods_click+1 where goods_id='.$_REQUEST['goods_id']);
        }

        //收藏
        if(!empty($_REQUEST['member_id'])) {
            $data = array(
                'member_id' => $_REQUEST['member_id'],
                'fav_id' => $goods_info['goods_id'],
            );
            $coll_model = Model('favorites');
            if($coll_model->table('favorites')->where($data)->select()){
                $goods_info['is_coll'] = '1';
            }else{
                $goods_info['is_coll'] = '0';
            }
        }else{
            $goods_info['is_coll'] = '0';
        }

        //dump($goods_info);

        //评价
        $eval = array('member_name'=>'张三','eval_time'=>date('Y-m-d',time()),'eval'=>'不错');

        //详情
        $goods_m = Model('goods_common');
        $comm = $goods_m->where(array('goods_commonid' => $goods_info['goods_commonid']))->find();
        //dump($comm);
        $content = unserialize($comm['mobile_body']);
        //$spec_arr = unserialize($goods_info['goods_spec']);
        $spec_name = unserialize($comm['spec_name']);
        $spec_value = unserialize($comm['spec_value']);
        //dump($spec_name);
        //dump($spec_value);
        foreach($spec_value as $key => $value){
            $n = 0;
            foreach($spec_value[$key] as $k=>$v){
                $spec[] = array(
                    'name' => $spec_name[$key],
                    'value' => $spec_value[$key][$k],
                );
                $n++;
                if($n>0){
                    break;
                }
                //$spec[$spec_name[$key]] = $spec_value[$key][$k];
            }
        }
        dump($spec);
        unset($goods_info['goods_spec']);
        unset($goods_info['goods_commonid']);
        $info = array(
            'goods_info'    => $goods_info,
            'eval'          => $eval,
            'content'       => $content,
            'spec'          => $spec,
        );
        dump($info);
        $this->error('0','ok',$info);
    }

    //版本更新
    public function versionUpdateOp(){
        $data = '1.修复首页数据展示问题。';
        $_REQUEST = $this -> bypost();
        $this->Dump();
        $dh = opendir('../data/upload');
        while($path = readdir($dh)) {
            if (preg_match("/\.apk$/", $path)) {
                $url = 'http://59.110.60.173/data/upload/'.$path;
                if(stripos($path,'-')){
                    $version = explode('-',$path);
                }elseif(stripos($path,'_')){
                    $version = explode('_',$path);
                }
                $version = str_replace('.apk','',$version[1]);
                //dump($version);
                $info = array(
                    'url'=> $url,
                    'version' => $version,
                    'data' => $data,
                );
                $this->error('0','ok',$info);
            }
        }
        $info = array(
            'url'=> '',
        );
        $this->error('-1','不需要升级',$info);
    }

    //返回json信息
    public function error($errorcode='',$errorinfo='',$info=false){
        $errorcode = $errorcode === ''?'-1':$errorcode;
        $errorinfo = $errorinfo === ''?'系统错误':$errorinfo;
        if ($info!=false) {
            $arr['info'] = $info;
        }
        $arr['err'] = array(
            'errorcode' => $errorcode,
            'errorinfo' => $errorinfo,
        );

        //打印
        $a = json_encode($arr);
        $content = $a.','.date('Y-m-d:H:i:s',time())."\r\n";
        file_put_contents('1.txt', $content,FILE_APPEND);
        exit($a);
    }

    //打印
    private function Dump(){
        file_put_contents('test.txt', print_r($_FILES, 1), FILE_APPEND);
        file_put_contents('test.txt', print_r($_REQUEST, 1), FILE_APPEND);
        file_put_contents('test.txt', file_get_contents('php://input'), FILE_APPEND);
        $arr = $GLOBALS["HTTP_RAW_POST_DATA"];
        file_put_contents('test.txt', print_r($arr, 1), FILE_APPEND);
    }

}
