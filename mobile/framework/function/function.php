<?php
/**
 * mobile公共方法
 *
 * 公共方法
 *
 * by 33hao.com 好商城V4 运营版
 */
defined('InShopNC') or exit('Access Invalid!');

function output_data($datas, $extend_data = array()) {
    $data = array();
    $data['code'] = 200;

    if(!empty($extend_data)) {
        $data = array_merge($data, $extend_data);
    }

    $data['datas'] = $datas;

    if(!empty($_GET['callback'])) {
        echo $_GET['callback'].'('.json_encode($data).')';die;
    } else {
        echo json_encode($data);die;
    }
}

function output_error($message, $extend_data = array()) {
    $datas = array('error' => $message);
    output_data($datas, $extend_data);
}

function mobile_page($page_count) {
    //输出是否有下一页
    $extend_data = array();
    $current_page = intval($_GET['curpage']);
    if($current_page <= 0) {
        $current_page = 1;
    }
    if($current_page >= $page_count) {
        $extend_data['hasmore'] = false;
    } else {
        $extend_data['hasmore'] = true;
    }
    $extend_data['page_total'] = $page_count;
    return $extend_data;
}

function get_server_ip() {
    if (isset($_SERVER)) {
        if($_SERVER['SERVER_ADDR']) {
            $server_ip = $_SERVER['SERVER_ADDR'];
        } else {
            $server_ip = $_SERVER['LOCAL_ADDR'];
        }
    } else {
        $server_ip = getenv('SERVER_ADDR');
    }
    return $server_ip;
}

function http_get($url) {
    return file_get_contents($url);
}

function http_post($url, $param) {
    $postdata = http_build_query($param);

    $opts = array('http' =>
        array(
            'method'  => 'POST',
            'header'  => 'Content-type: application/x-www-form-urlencoded',
            'content' => $postdata
        )
    );

    $context  = stream_context_create($opts);

    return @file_get_contents($url, false, $context);
}

function http_postdata($url, $postdata) {
    $opts = array('http' =>
        array(
            'method'  => 'POST',
            'header'  => 'Content-type: application/x-www-form-urlencoded',
            'content' => $postdata
        )
    );

    $context  = stream_context_create($opts);

    return @file_get_contents($url, false, $context);
}
