<?php
/**
 * 江苏大学教务系统对接微信
 * @author: 孙阳明
 */
require 'BaseSystem.class.php';


class JiangDa implements BaseSystem
{
    private $cookieFile;
    private $cookieTemp;
    private $year = '2015-2016'; //学年
    private $term = '1'; //学期
    private $sno;
    private $pwd;
    private $code;

    function __construct($sno, $pwd, $openid='', $code=''){
        $this->sno = $sno;
        $this->pwd = $pwd;
        if (empty($openid) && empty($code)) {
            $this->cookieFile = tempnam('./temp', 'cookie');
        }
        else {
            $this->code = $code;
            $this->cookieTemp = tempnam('./temp', 'cookie');
            $this->cookieFile = $_SERVER['DOCUMENT_ROOT'].'/rpc/temp/'.$openid.'.cookie';
        }
    }

    function login(){}

    //登陆信息门户
    private function _login(){
        //$url = "http://my.ujs.edu.cn/captchaValidate.portal?captcha=1&what=captcha&value=1";
        //_http($url, '', $this->cookieFile);
        if (empty($this->code)) {
            copy($this->cookieFile, $this->cookieTemp);
            $this->cookieFile = $this->cookieTemp;
            return true;
        }
        $url = "http://my.ujs.edu.cn/userPasswordValidate.portal";
        $post = "captchaField={$this->code}&Login.Token1={$this->sno}&Login.Token2={$this->pwd}&goto=http%3A%2F%2Fmy.ujs.edu.cn%2FloginSuccess.portal&gotoOnFail=http%3A%2F%2Fmy.ujs.edu.cn%2FloginFailure.portal";
        _http($url, $post, $this->cookieFile, $this->cookieFile);
        copy($this->cookieFile, $this->cookieTemp);
        $this->cookieFile = $this->cookieTemp;
        return true;
    }

    //登陆京江教务系统
    function loginJingJiang(){
        $url = "http://jjxke.ujs.edu.cn/default6.aspx";
        $str = _http($url);
        preg_match('/<input type="hidden" name="__VIEWSTATE" value="(.*)" \/>/i', $str, $matches);
        $view = urlencode($matches[1]);
        preg_match('/<input type="hidden" name="__VIEWSTATEGENERATOR" value="(.*)" \/>/i', $str, $matches);
        $view2 = urlencode($matches[1]);
        $post = "__VIEWSTATE={$view}&__VIEWSTATEGENERATOR={$view2}&tname=&tbtns=&tnameXw=yhdl&tbtnsXw=yhdl%7Cxwxsdl&txtYhm={$this->sno}&txtXm=&txtMm={$this->pwd}&rblJs=%D1%A7%C9%FA&btnDl=%B5%C7+%C2%BC";
        $res = _http($url, $post, '', $this->cookieFile);
        //登陆并保存cookie
    }

    //登陆研究生教务系统
    function loginGraduate(){
        $url = "http://yjsgl.ujs.edu.cn/yjsweb2009/login.aspx?id=3";
        $str = _http($url);
        preg_match('/<input type="hidden" name="__VIEWSTATE" id="__VIEWSTATE" value="(.*)" \/>/i', $str, $matches);
        $view = urlencode($matches[1]);
        preg_match('/<input type="hidden" name="__EVENTVALIDATION" id="__EVENTVALIDATION" value="(.*)" \/>/i', $str, $matches);
        $view2 = urlencode($matches[1]);
        $post = "__VIEWSTATE={$view}&userName={$this->sno}&txtPassword={$this->pwd}&ImageButton1.x=21&ImageButton1.y=7&__EVENTVALIDATION={$view2}";
        $res = _http($url, $post, '', $this->cookieFile);
        //登陆并保存cookie
    }
    //登陆本科教务系统
    function loginBenKe(){
        /*
        $url = "http://xk1.ujs.edu.cn/default_ldap.aspx";
        $str = _http($url);
        preg_match('/<input type="hidden" name="__VIEWSTATE" value="(.*)" \/>/i', $str, $matches);
        $view = urlencode($matches[1]);
        $post = "__VIEWSTATE={$view}&tbYHM={$this->sno}&tbPSW={$this->pwd}&Button1=&lbLanguage=";
        $res = _http($url, $post, '', $this->cookieFile);
*/
        $this->_login();
        $url = 'http://xk1.ujs.edu.cn/default_zzjk.aspx';
        _http($url, '', $this->cookieFile, $this->cookieFile);
        //登陆并保存cookie
    }

    //获取成绩
    function getScore($year='', $term=''){
        if(strlen($this->sno) == 10){
            if(preg_match('/^3\d{9}/', $this->sno)){
                $this->loginBenKe();
                return $this->getBenKeScore();
            }
            else if(preg_match('/^4\d{9}/', $this->sno)){//京江学院
                $this->loginJingJiang();
                return $this->getJingJiangScore();
            }
            else if(preg_match('/^2\d{9}/', $this->sno)){ //研究生
                return '';
            }
            else{
                return '';
            }
        }
        else{
            //研究生
            $this->loginGraduate();
            return $this->getGraduateScore();
        }
    }

    //获取本科成绩
    function getBenKeScore($year='', $term=''){
        $url = 'http://xk1.ujs.edu.cn/xscjcx.aspx?xh='.$this->sno.'&xm=&gnmkdm=N121615';
        //$url = 'http://xk1.ujs.edu.cn/xscj_gc.aspx?xh='.$this->sno.'&xm=&gnmkdm=N121605';
        $first = _http($url,'',$this->cookieFile);//查成绩
        preg_match('/<input type="hidden" name="__VIEWSTATE" value="(.*)" \/>/i', $first, $matches);
        $view = urlencode($matches[1]);
        $data = "__EVENTTARGET=&__EVENTARGUMENT=&__VIEWSTATE={$view}&ddlXN={$year}&ddlXQ={$term}&ddl_kcxz=&btn_xq=%D1%A7%C6%DA%B3%C9%BC%A8";
        $output = _http($url,$data,$this->cookieFile);//按学期查成绩
        $output = iconv('gb2312','utf-8//IGNORE',$output);
        $td = get_td_array($output);
        $grade = array();
        foreach($td as $v){
            if($v[6]){
                $grade[] = array($v[3], $v[8], $v[6], $v[7], $v[10], $v[11]);
            }
        }
        unset($grade[0]);
        if(empty($grade)){
            return false;
        }
        return $grade;
    }

    //京江学院成绩
    function getJingJiangScore($year='', $term=''){
        $url = 'http://jjxke.ujs.edu.cn/xscjcx.aspx?xh='.$this->sno.'&xm=&gnmkdm=N121605';
        $first = _http($url,'',$this->cookieFile);//查成绩
        preg_match('/<input type="hidden" name="__VIEWSTATE" value="(.*)" \/>/i', $first, $matches);
        $view = urlencode($matches[1]);
        preg_match('/<input type="hidden" name="__VIEWSTATEGENERATOR" value="(.*)" \/>/i', $first, $matches);
        $view2 = urlencode($matches[1]);
        $data = "__VIEWSTATE={$view}&__VIEWSTATEGENERATOR={$view2}&__EVENTTARGET=&__EVENTARGUMENT=&hidLanguage=&ddlXN={$year}&ddlXQ={$term}&ddl_kcxz=&btn_xq=学期成绩";
        $output = _http($url,$data,$this->cookieFile);//按学期查成绩
        $output = iconv('gb2312','utf-8//IGNORE',$output);
        $td = get_td_array($output);
        $grade = array();
        foreach($td as $v){
            if($v[6]){
                $grade[] = array($v[3], $v[8], $v[6], $v[7], $v[10], $v[11]);
            }
        }
        unset($grade[0]);
        if(empty($grade)){
            return false;
        }
        return $grade;
    }

    //研究生成绩
    function getGraduateScore(){
        $url = 'http://yjsgl.ujs.edu.cn/yjsweb2009/yjs/yjscj.aspx';
        $output = _http($url,'',$this->cookieFile);//按学期查成绩
        $output = iconv('gb2312','utf-8//IGNORE',$output);
        $td = get_td_array($output);
        $grade = array();
        foreach($td as $v){
            if($v[6]){
                $grade[] = array($v[1], $v[9], '', '', '', '');
            }
        }
        unset($grade[0]);
        if(empty($grade)){
            return false;
        }
        return $grade;
    }

    //获取全部成绩
    function getAllScore(){
        if(strlen($this->sno) == 10){
            if(preg_match('/^3\d{9}/', $this->sno)){
                $this->loginBenKe();
                return $this->getBenKeAllScore();
            }
            else if(preg_match('/^4\d{9}/', $this->sno)){//京江学院
                $this->loginJingJiang();
                return $this->getJingJiangAllScore();
            }
            else if(preg_match('/^2\d{9}/', $this->sno)){ //研究生
                return '';
            }
            else{
                return '';
            }
        }
        else{
            //研究生
            $this->loginGraduate();
            return $this->getGraduateScore();
        }
    }

    //获取本科成绩
    function getBenKeAllScore(){
        //新版查成绩
        //$url = 'http://xk1.ujs.edu.cn/xscjcx.aspx?xh='.$this->sno.'&xm=&gnmkdm=N121615';
        //旧版查成绩
        $url = 'http://xk1.ujs.edu.cn/xscj_gc.aspx?xh='.$this->sno.'&xm=&gnmkdm=N121616';
        $first = _http($url,'',$this->cookieFile);//查成绩
        preg_match('/<input type="hidden" name="__VIEWSTATE" value="(.*)" \/>/i', $first, $matches);
        $view = urlencode($matches[1]);
        //新版查成绩
        //$data = "__EVENTTARGET=&__EVENTARGUMENT=&__VIEWSTATE={$view}&hidLanguage=&ddlXN=&ddlXQ=&ddl_kcxz=&btn_zcj=%C0%FA%C4%EA%B3%C9%BC%A8";
        //旧版查成绩
        $data = "__VIEWSTATE={$view}&ddlXN=&ddlXQ=&Button2=%D4%DA%D0%A3%D1%A7%CF%B0%B3%C9%BC%A8%B2%E9%D1%AF";
        $output = _http($url,$data,$this->cookieFile);//按学期查成绩
        $output = iconv('gb2312','utf-8//IGNORE',$output);
        $td = get_td_array($output);
        preg_match('/平均学分绩点：(.*)<\/b>/',$output,$mr);
        $allAverageJd = trim($mr[1]);
        $grade = array();
        $termArr = array(); //学期数据
        foreach($td as $v){
            if($v[6]){
                $term = (int)$v[1];
                if ($term <= 0) continue;
                $termKey = trim($v[0]).'第'.$term.'学期';
                $termArr[$termKey][] = array(
                    'KCM' => $v[3],//课程名
                    'XF' => $v[6],//学分
                    'JD' => $v[7],//绩点
                    'CJ' => $v[8],//成绩
                    'BKCJ' => $v[10],//补考成绩
                    'CXCJ' => $v[11],//重修成绩
                );
            }
        }
        if (empty($termArr)) {
            return false;
        }
        krsort($termArr);
        $return = array();
        foreach ($termArr as $key => $val) {
            $countXf = 0;
            $countAll = 0;
            foreach ($val as $row) {
                if (empty($row['JD'])) continue;
                $countXf += floatval($row['XF']);
                $countAll += $row['XF']*$row['JD'];
            }
            $averageJd = round($countAll / $countXf, 2);
            $add1 = array('KCM' =>'本学期平均绩点','XF' =>'','JD' =>$averageJd,'CJ' =>'','BKCJ' =>'','CXCJ' =>'');
            $add2 = array('KCM' =>'全部成绩平均绩点','XF' =>'','JD' =>$allAverageJd,'CJ' =>'','BKCJ' =>'','CXCJ' =>'');
            array_push($val, $add1, $add2);
            $return[] = array(
                'XQ'=>$key,
                'CJ'=>$val,
            );
        }
        return $return;
    }

    //京江学院成绩
    function getJingJiangAllScore(){
        $url = 'http://jjxke.ujs.edu.cn/xscjcx.aspx?xh='.$this->sno.'&xm=&gnmkdm=N121605';
        $first = _http($url,'',$this->cookieFile);//查成绩
        preg_match('/<input type="hidden" name="__VIEWSTATE" value="(.*)" \/>/i', $first, $matches);
        $view = urlencode($matches[1]);
        preg_match('/<input type="hidden" name="__VIEWSTATEGENERATOR" value="(.*)" \/>/i', $first, $matches);
        $view2 = urlencode($matches[1]);
        $data = "__VIEWSTATE={$view}&__VIEWSTATEGENERATOR={$view2}&__EVENTTARGET=&__EVENTARGUMENT=&hidLanguage=&ddlXN=&ddlXQ=&ddl_kcxz=&btn_zcj=历年成绩";
        $output = _http($url,$data,$this->cookieFile);//按学期查成绩
        $output = iconv('gb2312','utf-8//IGNORE',$output);
        $td = get_td_array($output);
        $grade = array();
        $termArr = array(); //学期数据
        foreach($td as $v){
            if($v[6]){
                $term = (int)$v[1];
                if ($term <= 0) continue;
                $termKey = trim($v[0]).'第'.$term.'学期';
                $termArr[$termKey][] = array(
                    'KCM' => $v[3],//课程名
                    'XF' => $v[6],//学分
                    'JD' => $v[7],//绩点
                    'CJ' => $v[8],//成绩
                    'BKCJ' => $v[10],//补考成绩
                    'CXCJ' => $v[10],//重修成绩
                );
            }
        }
        if (empty($termArr)) {
            return false;
        }
        krsort($termArr);
        $return = array();
        foreach ($termArr as $key => $val) {
            $countXf = 0;
            $countAll = 0;
            foreach ($val as $row) {
                if (empty($row['JD'])) continue;
                $countXf += floatval($row['XF']);
                $countAll += $row['XF']*$row['JD'];
            }
            $averageJd = round($countAll / $countXf, 2);
            $add1 = array('KCM' =>'本学期平均绩点','XF' =>'','JD' =>$averageJd,'CJ' =>'','BKCJ' =>'','CXCJ' =>'');
            array_push($val, $add1);
            $return[] = array(
                'XQ'=>$key,
                'CJ'=>$val,
            );
        }
        return $return;
    }

    //获取课表
    function getLesson(){
        if(strlen($this->sno) == 10){
            if(preg_match('/^3\d{9}/', $this->sno)){
                $this->loginBenKe();
                return $this->getBenKeLesson();
            }
            else if(preg_match('/^4\d{9}/', $this->sno)){//京江学院
                $this->loginJingJiang();
                return $this->getJingJiangLesson();
            }
            else if(preg_match('/^2\d{9}/', $this->sno)){ //研究生
                return '';
            }
            else{
                return '';
            }
        }
        else{
            //研究生
            $this->loginGraduate();
            return $this->getGraduateLesson();
        }
    }

    //获取本科课表
    function getBenKeLesson(){
        $url = "http://xk1.ujs.edu.cn/xskbcx.aspx?xh={$this->sno}&xm=&gnmkdm=N121602";
        $first = _http($url, '', $this->cookieFile);
        preg_match('/<input type="hidden" name="__VIEWSTATE" value="(.*)" \/>/i', $first, $matches);
        $view = urlencode($matches[1]);
        $data = "__EVENTTARGET=xnd&__EVENTARGUMENT=&__VIEWSTATE={$view}&xnd={$this->year}&xqd={$this->term}";
        $output = _http($url, $data, $this->cookieFile);
        $output = iconv('gb2312','utf-8//IGNORE',$output);
        $td = get_td_html_array($output);
        if(empty($td[2][2])&&empty($td[2][3])&&empty($td[2][4])&&empty($td[2][5])){
            $output = iconv('gb2312','utf-8//IGNORE',$first);
            $td = get_td_html_array($output);
        }
        $item = array("1-2" => $td[2], "3-4" => $td[4], "5-6" => $td[6], "7-8" => $td[8], "9-10" => $td[10]);
        $kb = array();
        foreach ($item as $k => $v) {
            for($i=1;$i<8;$i++){
                if($k == '1-2'| $k == '5-6'| $k == "9-10"){
                    $lesson = trim($v[$i+1]);
                }
                else{
                    $lesson = trim($v[$i]);
                }
                if(!$lesson){
                    continue;
                }
                //$pattern = '/([^>]*?)<br>(.*?)<br>周.*?第\d,\d{1,2}节{第(\d{1,2}-.*?周)}<br>([^<]*?)<br>(.*?)<br>/';
                $item = $this->_filterLesson($lesson);
                if (empty($item)) continue;
                $kb[$i][$k] = $item;
            }
        }
        return $kb;
    }

    //获取课表
    function getGraduateLesson(){
        $url = "http://yjsgl.ujs.edu.cn/yjsweb2009/py/ZKCB.aspx?xz=3";
        $output = _http($url, '', $this->cookieFile);
        $output = iconv('gb2312','utf-8//IGNORE',$output);
        $td = get_td_array($output);
        //return $td;
        $item = array("1-2" => $td[3], "3-4" => $td[4], "5-6" => $td[5], "7-8" => $td[6], "9-11" => $td[7]);
        $kb = array();
        foreach ($item as $k => $v) {
            for($i=1;$i<8;$i++){
                $kb[$i][$k] = $v[$i+1];
            }
        }
        return $kb;
    }

    function getJingJiangLesson(){
        $url = "http://jjxke.ujs.edu.cn/xskbcx.aspx?xh={$this->sno}&xm=&gnmkdm=N121603";
        $first = _http($url, '', $this->cookieFile, $this->cookieFile);
        preg_match('/<input type="hidden" name="__VIEWSTATE" value="(.*)" \/>/i', $first, $matches);
        $view = urlencode($matches[1]);
        preg_match('/<input type="hidden" name="__VIEWSTATEGENERATOR" value="(.*)" \/>/i', $first, $matches);
        $view2 = urlencode($matches[1]);
        $data = "__EVENTTARGET=xqd&__EVENTARGUMENT=&__VIEWSTATE={$view}&__VIEWSTATEGENERATOR={$view2}&xnd={$this->year}&xqd={$this->term}";
        $output = _http($url,$data,$this->cookieFile);//按学期查成绩
        $output = iconv('gb2312','utf-8//IGNORE',$output);
        $td = get_td_html_array($output);
        if(empty($td[2][2])&&empty($td[2][3])&&empty($td[2][4])&&empty($td[2][5])){
            $output = iconv('gb2312','utf-8//IGNORE',$first);
            $td = get_td_html_array($output);
        }
        $item = array("1-2" => $td[2], "3-4" => $td[4], "5-6" => $td[6], "7-8" => $td[8], "9-10" => $td[10]);
        $kb = array();
        foreach ($item as $k => $v) {
            for($i=1;$i<8;$i++){
                if($k == '1-2'| $k == '5-6'| $k == "9-10"){
                    $lesson = trim($v[$i+1]);
                }
                else{
                    $lesson = trim($v[$i]);
                }
                if(!$lesson){
                    continue;
                }
                //$pattern = '/([^>]*?)<br>周.*?第(\d,\d{1,2})节{第(.*?周)}<br>(.*?)<br>(.*?)<br>/';
                $item = $this->_filterLesson($lesson);
                if (empty($item)) continue;
                $kb[$i][$k] = $item;
            }
        }
        return $kb;
    }

    private function _filterLesson($lesson) {
        $pattern = '/([^>]*?)<br>(必修|选修|校选)<br>周.*?第.*?节{第(.*?周)}<br>(.*?)<br>(.*?)<br>/';
        preg_match_all($pattern, $lesson.'<br>', $arr);
        if (empty($arr[0])) {
            $pattern = '/([^>]*?)<br>(周.*?)第.*?节{第(.*?周)}<br>(.*?)<br>(.*?)<br>/';
            preg_match_all($pattern, $lesson.'<br>', $arr);
            if (empty($arr[0])) return false;
        }
        $item = array();
        unset($arr[0]);
        foreach ($arr as $key => $val) {
            foreach ($val as $k1 => $v1) {
                $item[$k1][$key] = $v1;
            }
        }
        foreach ($item as $key => &$row) {
            $row['NM'] = $row[1];//课程名
            $row['WE'] = $row[3];//周次
            $row['AD'] = $row[5];//地址
            $row['TE'] = $row[4];//老师
            unset($row[1],$row[2],$row[3],$row[4],$row[5]);
            $key--;
            if ($key >= 0 && !empty($item[$key])) {
                if ($item[$key]['NM'] == $row['NM']) {//同一门课
                    if (!empty($item[$key]['AD']) && $item[$key]['AD'] != $row['AD'])
                        $row['AD'] .= ','.$item[$key]['AD'];//合并地址
                    if (!empty($item[$key]['TE']) && $item[$key]['TE'] != $row['TE'])
                        $row['TE'] .= ','.$item[$key]['TE'];//合并老师
                    if (!empty($item[$key]['WE']) && $item[$key]['WE'] != $row['WE'])
                        $row['WE'] .= ','.$item[$key]['WE'];//合并周次
                    unset($item[$key]);
                }
            }
        }
        return $item;
    }

    //获取个人信息
    function getInfo(){
        if(strlen($this->sno) == 10){
            if(preg_match('/^3\d{9}/', $this->sno)){
                $this->loginBenKe();
                return $this->getBenKeInfo();
            }
            else if(preg_match('/^4\d{9}/', $this->sno)){//京江学院
                $this->loginJingJiang();
                return $this->getJingJiangInfo();
            }
            else if(preg_match('/^2\d{9}/', $this->sno)){ //研究生
                return $this->getGraduateInfo2015();
            }
            else{
                return '';
            }
        }
        else{
            //研究生
            $this->loginGraduate();
            return $this->getGraduateInfo();
        }
    }

    //获取个人信息
    /*
    function getBenKeInfo(){
        $url = 'http://xk1.ujs.edu.cn/xsgrxx.aspx?xh='.$this->sno.'&xm=&gnmkdm=N121501';
        $output = _http($url, '', $this->cookieFile);
        $output = iconv('gb2312', 'utf-8//IGNORE', $output);
        preg_match_all('/<span id="xm">(.*)<\/span>/',$output,$nm);
        preg_match_all('/<span id="lbl_xb">(.*)<\/span>/',$output,$se);
        preg_match_all('/<span id="lbl_dqszj">(.*)<\/span>/',$output,$gr);
        preg_match_all('/<span id="lbl_xzb">(.*)<\/span>/',$output,$cl);

        $td = array();
        $td['name'] = $nm[1][0];
        if($se[1][0] == "男"){
            $gender = 1;
        }
        else if($se[1][0] == "女"){
            $gender = 2;
        }
        else{
            $gender = 0;
        }
        $td['gender'] = $gender;
        $td['grade'] = $gr[1][0];
        $td['class'] = $cl[1][0];
        return $td;
    }
    */
    //获取个人信息
    function getBenKeInfo(){
        $url = 'http://xk1.ujs.edu.cn/xs_main_zzjk.aspx?xh='.$this->sno;
        $output = _http($url, '', $this->cookieFile);
        $output = iconv('gb2312', 'utf-8//IGNORE', $output);
        preg_match('/<span id="xhxm">\d{10}(.*)同学<\/span>/', $output, $nm);
        preg_match('/3([0-9]{2})[0-9]{7}/', $this->sno, $m);
        if($m[1]){
            $grade = '20'.$m[1];
        }
        else{
            $grade = '';
        }
        $td = array();
        $td['name'] = trim($nm[1]);
        $td['gender'] = 0;
        $td['grade'] = $grade;
        $td['class'] = '';
        return $td;
    }

    function getGraduateInfo2015(){
        $this->_login();
        $url = 'http://my.ujs.edu.cn/index.portal?.pn=p123';
        $output = _http($url, '', $this->cookieFile);
        if (preg_match('#>(.{6,20}?),[\w\W]*?<script>[\w\W]*?当前身份：研究生#', $output, $m)) {
            if (!empty($m[1])) {
                $td = array();
                $td['name'] = trim($m[1]);
                $td['gender'] = 0;
                $td['grade'] = '';
                $td['class'] = '';
                return $td;
            }
            return false;
        }
        return false;
    }

    //获取研究生个人信息
    function getGraduateInfo(){
        $url = 'http://yjsgl.ujs.edu.cn/yjsweb2009/yjs/jibenxinxi.aspx?xz=2';
        $output = _http($url, '', $this->cookieFile);
        //$output = iconv('gb2312','utf-8',  $output);
        $output = mb_convert_encoding($output, "utf-8", "gb2312");
        //return $output;
        preg_match('/<input name="txtKSXM" type="text" value="(.*)" id="txtKSXM"/i', $output, $nm);
        preg_match_all('/<select name="dplXingBie" id="dplXingBie">.*?<\/select>/is', $output, $matches);
        preg_match('/<option selected="selected" value=".">(.*)<\/option>/', $matches[0][0], $match);
        $sex = trim($match[1]);
        preg_match('/<input name="txtNian" type="text" value="(.*)" id="txtNian"/',$output,$gr);
        $td = array();
        $td['name'] = $nm[1];
        if($sex == "男"){
            $gender = 1;
        }
        else if($sex == "女"){
            $gender = 2;
        }
        else{
            $gender = 0;
        }
        $td['gender'] = $gender;
        $td['grade'] = $gr[1];
        return $td;
    }

    //获取京江个人信息
    function getJingJiangInfo(){
        $url = 'http://jjxke.ujs.edu.cn/xs_main_zzjk.aspx?xh='.$this->sno;
        $output = _http($url, '', $this->cookieFile);
        $output = iconv('gb2312', 'utf-8//IGNORE', $output);
        $sno = $this->sno;
        preg_match('/<span id="xhxm">\d{10}(.*)同学<\/span>/', $output, $nm);
        preg_match('/4([0-9]{2})[0-9]{7}/', $this->sno, $m);
        if($m[1]){
            $grade = '20'.$m[1];
        }
        else{
            $grade = '';
        }
        $td = array();
        $td['name'] = trim($nm[1]);
        $td['gender'] = 0;
        $td['grade'] = $grade;
        $td['class'] = '';
        return $td;
    }

    //获取考试安排
    function getTestArrange(){
        if(strlen($this->sno) == 10){
            //本科生
            if(preg_match('/^3/', $this->sno)){
                $this->loginBenKe();
                $url = "http://xk1.ujs.edu.cn/xskscx.aspx?xh={$this->sno}&xm=&gnmkdm=N121608";
                $output = _http($url, '', $this->cookieFile);
                $output = iconv('gb2312', 'utf-8//IGNORE', $output);
                $td = get_td_array($output, 1);
                $arrange = '';
                $i = 0;
                foreach($td as $v){
                    if ($i == 0) {
                        $arrange .="<thead><tr><th>{$v[1]}</th><th>{$v[3]}</th><th>{$v[4]}</th><th>{$v[6]}</th></tr></thead><tbody>";
                    }
                    else {
                        $arrange .="<tr><td>{$v[1]}</td><td>{$v[3]}</td><td>{$v[4]}</td><td>{$v[6]}</td></tr>";
                    }
                    $i = 1;
                }
                if(!$arrange){
                    return "查不到你的考试安排，可能教务系统坏了或者你改了密码，如果你改了密码请发：绑定";
                }
                return $arrange;
            }
            else{
                //京江学院
                return "京江学院教务系统暂不支持此功能。";
            }
        }
        else{
            //研究生
            return "研究生教务系统暂不支持此功能。";
        }
    }

    //获取补考安排
    function getMakeUpArrange(){
        if(strlen($this->sno) == 10){//本科生
            if(preg_match('/^3/', $this->sno)){
                $this->loginBenKe();
                $url = "http://xk1.ujs.edu.cn/XsBkKsCx.aspx?xh={$this->sno}&xm=&gnmkdm=N121608";
                $output = _http($url, '', $this->cookieFile);
                $output = iconv('gb2312', 'utf-8//IGNORE', $output);
                $td = get_td_array($output);
                $arrange = '';
                $i = 0;
                foreach($td as $v){
                    if ($i == 0) {
                        $arrange .="<thead><tr><th>{$v[1]}</th><th>{$v[3]}</th><th>{$v[4]}</th><th>{$v[6]}</th></tr></thead><tbody>";
                    }
                    else {
                        $arrange .="<tr><td>{$v[1]}</td><td>{$v[3]}</td><td>{$v[4]}</td><td>{$v[6]}</td></tr>";
                    }
                    $i = 1;
                }
                if(!$arrange){
                    return "查不到你的补考安排，可能教务系统坏了或者你改了密码，如果你改了密码请发：绑定";
                }
                return $arrange;
            }
            else{
                //京江学院
                return "京江学院教务系统暂不支持此功能。";
            }
        }
        else{//研究生
            return "研究生教务系统暂不支持此功能。";
        }
    }

    function getRoom(){
        return "本学校的空教室功能暂未开通";
    }

    //获取学分学费
    function getXueFei(){
        if(strlen($this->sno) == 10){
            /*
            if(preg_match('/^4/', $this->sno)){
                return "京江学院教务系统暂不支持此功能。";
            }*/
            $arr = $this->getAllScore();
            if(empty($arr)){
                return "查不到你的学分学费，可能本年级未开通此功能或者教务系统坏了或者你改了密码，如果你改了密码请发：绑定";
            }
            $count = 0.0;
            foreach ($arr as $row) {
                if ($row['XQ'] == '2014-2015第2学期' || $row['XQ'] == '2014-2015第1学期') {
                    foreach ($row['CJ'] as $r) {
                        $count += $r['XF'];
                    }
                }
            }
            if ($count < 1){
                return "查不到你的学分学费，可能教务系统坏了或者你改了密码，如果你改了密码请发：绑定";
            }
            if(preg_match('/^[34]14/', $this->sno)) {
                $count1 = $count * 80;
            }
            else {
                $count1 = $count * 60;
            }
            $count2 = $count1 + 1600 + 1200 + 80;
            return "您开学后需交学费大概:{$count2}元\n其中学分学费总额:".$count1."元({$count}学分)，按住宿费1200专业注册费1600计算得出。\n\n学费=学分学费(上一年的学分总数x60或80，大一交大四的)+住宿费（650-1200等）+专业注册费（1600左右，具体请咨询班长或辅导员）+80元(助手君估计是城市医疗保险)\n\n注：以上计算仅供参考，具体以财务处公布为准";
        }
        else{//研究生
            return "研究生教务系统暂不支持此功能。";
        }
    }

    //查等级考试成绩功能
    function getGradeScore(){
        if(strlen($this->sno) == 10){
            //本科生
            if(preg_match('/^3/', $this->sno)){
                $this->loginBenKe();
                $url = "http://xk1.ujs.edu.cn/xsdjkscx.aspx?xh={$this->sno}&xm=&gnmkdm=N121608";
                $output = _http($url, '', $this->cookieFile);
                $output = iconv('gb2312', 'utf-8//IGNORE', $output);
                $td = get_td_array($output);
                $grade = '';
                $i = 0;
                foreach($td as $v){
                    if($v[2]){
                        if ($i == 0) {
                            $grade .="<thead><tr><th>{$v[2]}</th><th>{$v[4]}</th><th>{$v[5]}</th></tr></thead><tbody>";
                        }
                        else {
                            $grade .="<tr><td>{$v[2]}</td><td>{$v[4]}</td><td>{$v[5]}</td></tr>";
                        }
                        $i = 1;
                    }
                }
                if(!$grade){
                    return "查不到你的等级考试成绩，可能教务系统坏了或者你改了密码，如果你改了密码请发：绑定";
                }
                return $grade;
            }
            else{
                //京江学院
                return "京江学院教务系统暂不支持此功能。";
            }
        }
        else{
            //研究生
            return "研究生教务系统暂不支持此功能。";
        }
    }

    function check(){
        if(strlen($this->sno) == 10){
            if(preg_match('/^3\d{9}/', $this->sno)){
                $this->loginBenKe();
                return $this->checkBenke();
            }
            else if(preg_match('/^4\d{9}/', $this->sno)){//京江学院
                $this->loginJingJiang();
                return $this->checkJingJiang();
            }
            else if(preg_match('/^2\d{9}/', $this->sno)){ //研究生
                if ($this->getGraduateInfo2015()) {
                    return true;
                }
                return false;
            }
            else{
                return false;
            }
        }
        else{
            //研究生
            $this->loginGraduate();
            return $this->checkGraduate();
        }
    }

    function checkJingJiang(){
        $url = 'http://jjxke.ujs.edu.cn/xs_main_zzjk.aspx?xh='.$this->sno;
        $output = _http($url, '', $this->cookieFile);
        $output = iconv('gb2312', 'utf-8//IGNORE', $output);
        if(strpos($output, "欢迎您") !== false){//认证成功
            return 1;
        }
        else{
            return 0;
        }
    }

    function checkGraduate(){
        $url = 'http://yjsgl.ujs.edu.cn/yjsweb2009/yjs/jibenxinxi.aspx?xz=2';
        $output = _http($url, '', $this->cookieFile);
        $output = mb_convert_encoding($output, "utf-8//IGNORE", "gb2312");
        preg_match('/<input name="txtKSXM" type="text" value="(.*)" id="txtKSXM"/i', $output, $nm);
        $name = $nm[1];
        if(trim($name)){//认证成功
            return 1;
        }
        else{
            return 0;
        }
    }

    function checkBenke(){
        $info = $this->getBenKeInfo();
        if (empty($info) || empty($info['name'])) {
            return 0;
        }
        return 1;
/*
        $url = 'http://xk1.ujs.edu.cn/xs_main_zzjk.aspx?xh='.$this->sno;
        $output = _http($url, '', $this->cookieFile);
        $output = iconv('gb2312', 'utf-8//IGNORE', $output);
        if(strpos($output, "欢迎您") !== false){//认证成功
            return 1;
        }
        else{
            return 0;
        }
        */
    }

    /*一卡通*/
    function getCard() {
        $this->_login();
        if(preg_match('/^3\d{9}/', $this->sno)){
            $url = "http://my.ujs.edu.cn/pnull.portal?.f=f1346&.pmn=view&action=informationCenterAjax";
        }
        else if(preg_match('/^2\d{9}/', $this->sno)){ //研究生
            $url = "http://my.ujs.edu.cn/pnull.portal?.f=f729&.pmn=view&action=informationCenterAjax&.ia=false&.pen=pe570";
        }
        else {
            return '';
        }
        $ch=curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
        curl_setopt($ch, CURLOPT_REFERER,$url);
        curl_setopt($ch,CURLOPT_TIMEOUT,1);
        curl_exec($ch);
        $output = curl_exec($ch);
        curl_close($ch);
        $j = json_decode($output, TRUE);
        if(empty($j)){//
            return ;
        }
        $m = '<br>';
        foreach ($j as $v) {
            $m .= $v['title'].$v['description']."<br>";
        }
        $m = str_replace('<span>', ':', $m);
        $m = str_replace('</span>', '', $m);
        return $m;
    }

}


//header("Content-type:text/html;charset=utf-8");
//error_reporting(0);
//$s = new JiangDa('4141165048', 'wz201469');
//$s = new JiangDa('3110604039', '565292');
//$s = new JiangDa('4121104003', '261110');
//$s = new JiangDa('z1308036', '3491679');
//$s = new JiangDa('3111403032', '1991514huxiya');
//$s = new JiangDa('3121003012', 'ujsxttwq');
//$s = new JiangDa('3111304003', 'Djy527');
//$s = new JiangDa('3141814004', '192446');
//$s = new JiangDa('4131163011', '126206');
//$s = new JiangDa('4131166018', '225566');
//$res = $s->getAllScore();
//echo createForm('./show.php', array('result'=>base64_encode(json_encode($res))));


?>
