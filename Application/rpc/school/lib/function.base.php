<?php

/**
 * @description 基础函数库
 * @copyright (c) Entao All Rights Reserved
 */
/**
 * POST and GET method of CURL
 * @param string $url 
 * @param string $data : the data will post
 * @param string $cookiefile : the path of from-cookie 
 * @param string $cookiejar : the path of to-cookie
 * @return string
 */
function _http($url, $data='', $cookiefile='', $cookiejar='', $timeout=5){
    $ch=curl_init();
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    if($cookiefile){
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiefile);
    }
    curl_setopt($ch, CURLOPT_REFERER,$url);
    if($data){
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));      
    }
    if($cookiejar){
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiejar);
    }
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    $str=curl_exec($ch);
    curl_close($ch);
    return $str;
}

function curl_get($url, $timeout=5){
    $ch=curl_init();
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch, CURLOPT_REFERER,$url);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    $res = curl_exec($ch);
    $httpInfo = curl_getinfo($ch);
    curl_close($ch);
    if($httpInfo['http_code'] == 200){
        return $res;
    }
    return false;
}

//CURL post 请求，支持SSL
function curl_post($url, $data, $is_ssl=0, $timeout=5) {
	$ch=curl_init();
    curl_setopt($ch, CURLOPT_URL,$url);
    if ($is_ssl == 0) {
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);
    }
    else {
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,TRUE);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,TRUE);
    }
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    $r = curl_exec($ch);
    curl_close($ch);
    return $r;
}

//去除表格
function get_td_array($table, $withoutdate="") {
    $table = preg_replace("/<table[^>]*?>/is","",$table);
    $table = preg_replace("/<tr[^>]*?>/si","",$table);
    $table = preg_replace("/<td[^>]*?>/si","",$table);
    $table = str_replace("</tr>","{tr}",$table);
    $table = str_replace("</td>","{td}",$table);
    //去掉 HTML 标记
    $table = preg_replace("'<[/!]*?[^<>]*?>'si","",$table);
    //去掉空白字符
    $table = preg_replace("'([rn])[s]+'","",$table);
    $table = str_replace(" ","",$table);
    $table = str_replace(" ","",$table);
    $table = str_replace("&nbsp;","",$table);
    if(!$withoutdate){
        $table = preg_replace("/20[0-9][0-9]年[0-9]+月[0-9]+日\(.{11}\)/","\n",$table);
    }
  
    $table = explode('{tr}', $table);
    array_pop($table);
    foreach ($table as $key=>$tr) {
      $td = explode('{td}', $tr);
      $td = explode('{td}', $tr);
      array_pop($td);
      $td_array[] = $td;
    }
    return $td_array;
}

function get_td_html_array($table) {
    $table = preg_replace("/<table[^>]*?>/is","",$table);
    $table = preg_replace("/<tr[^>]*?>/si","",$table);
    $table = preg_replace("/<td[^>]*?>/si","",$table);
    $table = str_replace("</tr>","{tr}",$table);
    $table = str_replace("</td>","{td}",$table);
    $table = preg_replace("/20[0-9][0-9]年[0-9]+月[0-9]+日\(.{11}\)/","\n",$table);
    //
    $order   = array("\r\n", "\n", "\r", "<font color='red'>", "</font>");
    $replace = '';
    $table=str_replace($order, $replace, $table);
    //去掉空白字符
    //$table = preg_replace("'([rn])[s]+'","",$table);
    $table = str_replace(" ","",$table);
    $table = str_replace("&nbsp;","",$table);
    $table = explode('{tr}', $table);
    array_pop($table);
    foreach ($table as $key=>$tr) {
      $td = explode('{td}', $tr);
      $td = explode('{td}', $tr);
      array_pop($td);
      $td_array[] = $td;
    }
    return $td_array;
}

if (!function_exists('createForm')) {
    function createForm($action, $params, $method = 'post', $butName = '提交', $title = null)
    {
        if (!$action) {
            throw new Exception('Parameters "action" cannot be empty');
        }
        if (empty($title)) {
            $title = '江苏大学成绩查询';
        }

        $html = '<html><meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
        $html .= '<head><title>' . $title . '</title></head>';
        $html .= '<body> Loading... ';
        $html .= '<form style="display:none" name="dataSubmit" action="' . $action . '" method="' . $method . '">';

        foreach($params as $key => $val) {
            $html .= '<input type="hidden" name="' . $key . '" value="' . $val . '" />';
        }

        $html .= '<input type="submit" value="' . $butName . '" /></form>';
        $html .= "<script type='text/javascript'>document.forms['dataSubmit'].submit();</script>";
        $html .= '</body></html>';
        return $html;
    }
}