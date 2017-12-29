<?php
/**
 * 入口文件
 *
 * 统一入口，进行初始化信息
 *
 *
 * @copyright Copyright (c) 2007-2013 ShopNC Inc. (http://www.cnnewyork.com)
 * @license  http://www.cnnewyork.com/
 * @link    http://www.cnnewyork.com/
 * @since   File available since Release v1.1
 */
define('BASE_PATH',str_replace('\\','/',dirname(__FILE__)));
require_once(dirname(dirname(__FILE__)).'/global.php');
session_save_path(BASE_DATA_PATH.DS.'session');
require_once(BASE_DATA_PATH.DS.'config/config.ini.php');
if(!empty($config) && is_array($config)){
  $site_url = $config['shop_site_url'];
  $version = $config['version'];
  $setup_date = $config['setup_date'];
  $gip = $config['gip'];
  $dbtype = $config['dbdriver'];
  $dbcharset = $config['db'][1]['dbcharset'];
  $dbserver = $config['db'][1]['dbhost'];
  $dbserver_port = $config['db'][1]['dbport'];
  $dbname = $config['db'][1]['dbname'];
  $db_pre = $config['tablepre'];
  $dbuser = $config['db'][1]['dbuser'];
  $dbpasswd = $config['db'][1]['dbpwd'];
  $lang_type = $config['lang_type'];
  $cookie_pre = $config['cookie_pre'];
}
  
define('SHOP_SITE_URL',$site_url);
include 'api/qq/oauth/qq_callback.php';