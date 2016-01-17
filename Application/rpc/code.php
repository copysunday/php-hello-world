<?php
header("Content-Type: image/jpeg;text/html; charset=utf-8");
$openid = empty($_GET['id'])?'':trim($_GET['id']);
if (empty($openid) || !preg_match("/^[\da-zA-Z_-]{28,40}$/", $openid)) {
    die('access deny');
}
$url = 'http://my.ujs.edu.cn/captchaGenerate.portal?s='.time();
$cookie_jar = dirname(__FILE__)."/temp/{$openid}.cookie";
$hander = curl_init();
curl_setopt($hander,CURLOPT_URL,$url);
curl_setopt($hander,CURLOPT_HEADER,0);
curl_setopt($hander, CURLOPT_COOKIEJAR, $cookie_jar);
curl_setopt($hander,CURLOPT_TIMEOUT,10);
curl_exec($hander);
curl_close($hander);
?>