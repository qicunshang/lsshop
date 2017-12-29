<?php
/**
 * 邮币卡后台管理分类
 * @authors 刘帅
 * @date    2016-09-01 13:48:16
 */

class ybk_adminControl extends SystemControl
{

    public function __construct()
    {
        parent::__construct();
        Language::read('ybk_class');
    }

    public function indexOp(){
        echo '123';
    }



}