<?php
/**
 * 微信支付通知地址
 *
 * @好商城V4 (c) 2015-2016 33hao Inc.
 * @license    http://www.33hao.com
 * @link       交流群号：216611541
 * @since      好商城提供技术支持 授权请购买shopnc授权
 */
$_GET['act']	= 'payment';
$_GET['op']		= 'notify';
$_GET['payment_code'] = 'wxpay3';
require_once(dirname(__FILE__).'/../../../index.php');
