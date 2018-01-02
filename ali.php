<?php
define("AOP_SDK_WORK_DIR", "/tmp/");
define("AOP_SDK_DEV_MODE", true);
//define("APPID", '2016072900119743');//沙箱
include_once("./shop/api/alipay/AopSdk.php");
//接收支付宝信息
$c = new AopClient;
$c->gatewayUrl = "https://openapi.alipay.com/gateway.do";
$c->appId = APPID;
$c->rsaPrivateKey = 'MIICXQIBAAKBgQDHnTuupsbthYrsseBtk7XaxoA4bIHQcMjO3YFs5o5PzByRzRuD2Wg5eBeEto1rDph+jRtcNTEaApON0NVyUDHoOXhBV8IspB6giFbBxdtQ09Eap1UF2oAodNe5QOchswUybZg096Z+3EZevRjYNnVF5znIRhoW6K1GuUeNXIuD3wIDAQABAoGAJlHL1EJV6+D4A2o+QCrb/Uyf7rT90qrkEbo6D1LPPQhc76xlPNFujaG9Og/lZAjgQ2MJPpFDhM+7zbyqTRCSE8CsGv3ANCdPH6hPk/Yv6YkjfI7LLXLXOslHuVNphaJAKokC7xnYaAo/SbVOpPVEfU4rTeabRG5+vKx659IuKRECQQDnNk5ULEpLeEZ6Q8pPAPPPqGFYHNMEVlMdvp2MuIXz0m0L591pQI5TPVXotCctl0iNIGde6bwFZ/Cyn5iVpLO5AkEA3QO2DcvZcZdLehPGaOBZ8VD1VK2fbyrrXey3ch1fYOi6feG+uc+RsxvL3/bYy/S/YOIM7Zhahr6RHNLidj3wVwJBAOUFcckGpgDjfkwVYgunkdmbm/C/fHXLXEEWUtDU5jqBsOoeHb7n5xKkqrf52uTZ+U9xTXNCaq+gKVeFpopzvmECQQCDCjVB3tBw6fxlSB5Ghmatjou3Bi5WkkL08Gof7cxkP/h6tIMJ1kkTwfJSOyB1ZQqnXb++i6t44rpVNa/x3W2HAkBpS+XgeNJ1azRrxkFA5RP9Bmwpgc5ZD9TmvpE5UT9GAO8fLpLQslKdm3o6ArI98PQSgCK6j+DG1gzfC8j5DJRsJ';
//$c->rsaPrivateKey = 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDHnTuupsbthYrsseBtk7XaxoA4bIHQcMjO3YFs5o5PzByRzRuD2Wg5eBeEto1rDph+jRtcNTEaApON0NVyUDHoOXhBV8IspB6giFbBxdtQ09Eap1UF2oAodNe5QOchswUybZg096Z+3EZevRjYNnVF5znIRhoW6K1GuUeNXIuD3wIDAQAB';
$c->format = "json";
$c->charset= "utf-8";
$c->alipayrsaPublicKey = 'MIGfMA0GCSqGSIb3DsQEBAQUAA4GNADCBiQKBgQDDI6d306Q8fIfCOaTXyiUeJHkrIvYISRcc73s3vF1ZT7XN8RNPwJxo8pWaJMmvyTn9N4HQ632qJBVHf8sxHi/fEsraprwCtzvzQETrNRwVxLO5jVmRGi60j8Ue1efIlzPXV9je9mkjzOmdssymZkh2QhUrCmZYI/FCEa3/cNMW0QIDAQAB';
//实例化具体API对应的request类,类名称和接口名称对应,当前调用接口名称：alipay.open.public.template.message.industry.modify
/*$request = new AlipayTradeAppPayRequest();
//SDK已经封装掉了公共参数，这里只需要传入业务参数
//此次只是参数展示，未进行字符串转义，实际情况下请转义subject,out_trade_no,total_amount,product_code QUICK_MSECURITY_PAY
$request->setBizContent = "{
    \"primary_industry_name\":\"IT科技/IT软件与服务\",
    \"primary_industry_code\":\"10001/20102\",
    \"secondary_industry_code\":\"10001/20102\",
    \"secondary_industry_name\":\"IT科技/IT软件与服务\"
  }";
$response= $c->execute($request);
var_dump($response);*/
//回调验签
//$_REQUEST = bypost();
//$this->Dump();
if(isset($_GET['ls']) && $_GET['ls'] == 'ok'){
    //$json = '{"total_amount":"0.01","buyer_id":"2088902222054569","trade_no":"2017011821001004560298279316","notify_time":"2017-01-18 13:58:12","subject":"\u5145\u503c0.01\u5143","sign_type":"RSA","buyer_logon_id":"136****6210","auth_app_id":"2016121904405754","charset":"utf-8","notify_type":"trade_status_sync","invoice_amount":"0.01","out_trade_no":"540538063040364047","trade_status":"TRADE_SUCCESS","gmt_payment":"2017-01-18 13:58:11","version":"1.0","point_amount":"0.00","sign":"QRTxgZlV8hJFY8i\/JGQdj1CEMhN3HiVgAKyXJAoJUW3mMHQ\/oDLKL0ECGKYOtx9eQSdgrzgAW925Ff3CtqcTsryHsKHbTvyhvv0z522DvEHo7\/BqRxsjHZOuOHPuJAd4MqawyCygziMwttkYvR280crwUZ0D80LgsMkyqnbC83U=","gmt_create":"2017-01-18 13:58:11","buyer_pay_amount":"0.01","receipt_amount":"0.01","fund_bill_list":"[{&quot;amount&quot;:&quot;0.01&quot;,&quot;fundChannel&quot;:&quot;ALIPAYACCOUNT&quot;}]","app_id":"2016121904405754","seller_id":"2088911120779784","notify_id":"b048b783808048f04959dc2cb6aaef3kbm","seller_email":"huibangkehj@163.com"}';
    //echo '123';
    $json = file_get_contents('php://input');
    //echo $json;
    //file_put_contents('test.txt', '支付宝参数:'.$json,FILE_APPEND);
    $info = json_decode($json,true);
    $info['fund_bill_list'] = str_replace('&quot;','"',$info['fund_bill_list']);
    //$info['fund_bill_list'] = urldecode($info['fund_bill_list']);
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
    //var_dump($info);
    //var_dump($_POST);
    $res = $c->rsaCheckV1($info,'./alipay_public_key.txt');
    if($res){
        $res = 'ok';
    }else{
        $res = 'no';
    }
    $data = array(
        'res' => $res,
    );
    exit(json_encode($data));
}
//充值
if(isset($_REQUEST['method']) && $_REQUEST['method'] == 'alipay'){
    $json = request('http://59.110.60.173/shop/ybkapi.php?act=ybkapi&op=toPay&method=alipay&recharge='.$_REQUEST['recharge'].'&member_id='.$_REQUEST['member_id']);
    //$json = '{"notify":"http:\/\/shop.zhulongwan.com\/alipay.php","order_sn":"8000111283154795","order_amount":500}';
    $info = json_decode($json,true);
    $_REQUEST['order_sn'] = $info['order_sn'];
    $info['goods_amount'] = (string)sprintf("%01.2f", $info['goods_amount']);
    $biz =  '{"subject":"充值'.$info['order_amount'].'元","out_trade_no":"'.$info['order_sn'].'","total_amount":"'.$info['order_amount'].'","product_code":"QUICK_MSECURITY_PAY"}';
    $notify = $info['notify'];
}else{
    $json = request('http://59.110.60.173/shop/ybkapi.php?act=ybkapi&op=orderinfo&order_sn='.$_REQUEST['order_sn'].'&member_id='.$_REQUEST['member_id']);
    $info = json_decode($json,true);
    //echo '123'.$_REQUEST['order_sn'].$_REQUEST['member_id'];die;
    //var_dump($info);
    $notify = 'http://www.hbkclub.com//alipay.php';
    $biz =  '{"subject":"'.$info['goods'][0]['goods_name'].'","out_trade_no":"'.$info['pay_sn'].'","total_amount":"'.$info['sum'].'","product_code":"QUICK_MSECURITY_PAY"}';
}

//echo $biz;
$time = date('Y-m-d H:i:s',time());
$sign = $c->rsaSign(array(
    'app_id'=> APPID,
    'biz_content'=>$biz,
    'charset'=>'utf-8',
    'method'=>'alipay.trade.app.pay',
    'notify_url'=> $notify,
    'timestamp'=> $time,
    'version'=> '1.0',
    'sign_type'=> 'RSA',
));
$info = array(
    'sign'          => $sign,
    'app_id'        => APPID,
    'biz_content'   => $biz,
    'charset'       => 'utf-8',
    'method'        => 'alipay.trade.app.pay',
    'notify_url'    => $notify,
    'timestamp'     => $time,
    'version'       => '1.0',
    'sign_type'     => 'RSA',
);
$url = formatQueryParaMap($info,true);
//$url = $url.'&sign='.urlencode($sign);
$info['url'] = $url;

$arr['info'] = $info;
//file_put_contents('test.txt',print_r($arr['info'],1),FILE_APPEND);
$arr['err'] = array(
    'errorcode' => '0',
    'errorinfo' => 'ok',
);
$sign = json_encode($arr);
//var_dump($arr);
echo $sign;
//echo '{"info":{"sign":"D8e2Q9dog20nd1fSGHg\/\/V5LVXlEmTBMJr3w5b39EUhPUqLIVP0EKoUmfccHnZLhLQ2V01Vnih0Bp+YqMdcE6S\/wNnX\/ZYFMZjm2e6NuEXQ3ZZ8VtgSVc9KM422EaQM5V9Dy0tmtfcZ3clqOtFYBFo5Byj0N8eMgP+4+bfs0QCw=","app_id":"2016121904405754","biz_content":"{\"subject\":\"\u5145\u503c0.01\u5143\",\"out_trade_no\":\"490538064894191047\",\"total_amount\":\"0.01\",\"product_code\":\"QUICK_MSECURITY_PAY\"}","charset":"utf-8","method":"alipay.trade.app.pay","notify_url":"http:\/\/shop.zhulongwan.com\/alipayRe.php","timestamp":"2017-01-18 14:27:33","version":"1.0","sign_type":"RSA","url":"app_id=2016121904405754&biz_content=%7B%22subject%22%3A%22%E5%85%85%E5%80%BC0.01%E5%85%83%22%2C%22out_trade_no%22%3A%22490538064894191047%22%2C%22total_amount%22%3A%220.01%22%2C%22product_code%22%3A%22QUICK_MSECURITY_PAY%22%7D&charset=utf-8&method=alipay.trade.app.pay&notify_url=http%3A%2F%2Fshop.zhulongwan.com%2FalipayRe.php&sign=D8e2Q9dog20nd1fSGHg%2F%2FV5LVXlEmTBMJr3w5b39EUhPUqLIVP0EKoUmfccHnZLhLQ2V01Vnih0Bp%2BYqMdcE6S%2FwNnX%2FZYFMZjm2e6NuEXQ3ZZ8VtgSVc9KM422EaQM5V9Dy0tmtfcZ3clqOtFYBFo5Byj0N8eMgP%2B4%2Bbfs0QCw%3D&sign_type=RSA&timestamp=2017-01-18+14%3A27%3A33&version=1.0"},"err":{"errorcode":"0","errorinfo":"ok"}}';
/*
 *验签参数
app_id=2014072300007148&biz_content={"button":[{"actionParam":"ZFB_HFCZ","actionType":"out","name":"话费充值"},{"name":"查询","subButton":[{"actionParam":"ZFB_YECX","actionType":"out","name":"余额查询"},{"actionParam":"ZFB_LLCX","actionType":"out","name":"流量查询"},{"actionParam":"ZFB_HFCX","actionType":"out","name":"话费查询"}]},{"actionParam":"http://m.alipay.com","actionType":"link","name":"最新优惠"}]}&charset=GBK&method=alipay.mobile.public.menu.add&sign_type=RSA&timestamp=2014-07-24 03:07:50&version=1.0
 * */
//curl函数,获取接口数据
function formatQueryParaMap($paraMap, $urlEncode = false)
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
function request($url,$https=true,$method='get',$data=null){
    //1.初始化url
    $ch = curl_init($url);
    //2.设置相关的参数
    //字符串不直接输出,进行一个变量的存储
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //判断是否为https请求
    if($https === true){
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    }
    //判断是否为post请求
    if($method == 'post'){
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    //3.发送请求
    $str = curl_exec($ch);
    //4.关闭连接
    curl_close($ch);
    //返回请求到的结果
    return $str;
}
//{ "subject":"蜥蜴赤霞珠梅洛干红葡萄酒 澳洲 17° 赤霞珠 梅洛", "out_trade_no":"8001217719036489", "total_amount":"600", "product_code":"QUICK_MSECURITY_PAY", }
//打印
function bypost()
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
function Dump(){
    file_put_contents('test.txt', print_r($_FILES, 1), FILE_APPEND);
    file_put_contents('test.txt', print_r($_REQUEST, 1), FILE_APPEND);
    file_put_contents('test.txt', file_get_contents('php://input'), FILE_APPEND);
    $arr = $GLOBALS["HTTP_RAW_POST_DATA"];
    file_put_contents('test.txt', print_r($arr, 1), FILE_APPEND);
}
