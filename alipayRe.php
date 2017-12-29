<?php
$info = file_get_contents('php://input');
file_put_contents('test.txt', 'php://input'.$info,FILE_APPEND);
file_put_contents('test.txt', 'POST.alipayRe:'.print_r($_POST,1),FILE_APPEND);
$_GET['act']	= 'ybkapi';
$_GET['op']		= 'alipay';
$_GET['style']  = 'recharge';
require_once(dirname(__FILE__).'/index.php');
?>
