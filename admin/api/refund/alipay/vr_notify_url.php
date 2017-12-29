<?php
/**
 * 支付宝服务器异步通知页面
 *
 * v3-b12 
 *
 * by 好商城V3 www.33hao.com 运营版
 */
$_GET['act']	= 'notify_refund';
$_GET['op']		= 'alipay';
$_GET['refund']		= 'vr';//虚拟订单退款
require_once(dirname(__FILE__).'/../../../index.php');
?>