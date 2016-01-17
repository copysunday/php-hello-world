<?php
header("content-type:text/html;charset=utf-8;");

$url = 'http://my.ujs.edu.cn/captchaGenerate.portal?s='.time();
$ch = curl_init();
curl_setopt($ch,CURLOPT_URL,$url);
curl_setopt($ch, CURLOPT_HEADER, 1);//返回response头部信息
curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);//不直接输出response   
curl_setopt($ch,CURLOPT_TIMEOUT,10);
curl_exec($ch);
$info=curl_getinfo($ch);
curl_close($ch);
$return = array(
    'http_code' => $info['http_code'],
    'msg' => 'success',
);
die(json_encode($return));