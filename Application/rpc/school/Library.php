<?php
/*
 * 图书馆在借图书查询类
 * @author sun
 * 2015.3.2
 */
//require_once $_SERVER ['DOCUMENT_ROOT'] . '/school/Library.php';

class Library{
	private $_sno;
	private $_password;
	private $_cookie;
    private $cookieTemp;
	private $_login_data;
    private $code;

	public function __construct($sno, $password, $openid='', $code='') {
		$this->_sno = $sno;
		$this->_password = $password;
        if (empty($openid) && empty($code)) {
            $this->cookieFile = tempnam('./temp', 'cookie');
        }
        else {
            $this->code = $code;
            $this->cookieTemp = tempnam('./temp', 'cookie');
            $this->_cookie = $_SERVER['DOCUMENT_ROOT'].'/rpc/temp/'.$openid.'.cookie';
        }
		$this->_login_data = $this->_login();	
	}

	private function _login() {
		//$url = "http://my.ujs.edu.cn/captchaValidate.portal?captcha=1&what=captcha&value=1";
	    //_http($url, '', $this->_cookie);
	    //登陆信息门户
        if (empty($this->code)) {
            copy($this->_cookie, $this->cookieTemp);
            $this->_cookie = $this->cookieTemp;
            //登陆图书馆
            $url = 'http://huiwen.ujs.edu.cn:8080/reader/hwthau.php';
            $ret = _http($url, '', $this->_cookie, $this->_cookie);
            //var_dump($ret);
            return true;
        }
	    $url = "http://my.ujs.edu.cn/userPasswordValidate.portal";
	    $post = "captchaField={$this->code}&Login.Token1={$this->_sno}&Login.Token2={$this->_password}&goto=http%3A%2F%2Fmy.ujs.edu.cn%2FloginSuccess.portal&gotoOnFail=http%3A%2F%2Fmy.ujs.edu.cn%2FloginFailure.portal";
	    _http($url, $post, $this->_cookie, $this->_cookie);
        copy($this->_cookie, $this->cookieTemp);
        $this->_cookie = $this->cookieTemp;
	    //登陆图书馆
	    $url = 'http://huiwen.ujs.edu.cn:8080/reader/hwthau.php';
	    $ret = _http($url, '', $this->_cookie, $this->_cookie);
        //var_dump($ret);
		return $ret;
	}

	/*
	 *判断是否登陆成功
	 */
	public function check() {
		if (strstr($this->_login_data, '您好') !== false) {
			return true;
		}
		return false;
	}

	public function getList() {
        $historyList = $this->getHistoryList();
        if (empty($historyList)) {
            return false;
        }
        $bookurl = 'http://huiwen.ujs.edu.cn:8080/reader/book_lst.php';
		$ch = curl_init($bookurl);
		curl_setopt($ch,CURLOPT_HEADER,0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//0显示,1不显示
        curl_setopt($ch, CURLOPT_REFERER,'http://huiwen.ujs.edu.cn:8080/reader/redr_info.php');
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->_cookie);//使用cookie
		$data = curl_exec($ch);
		$data =  html_entity_decode($data,ENT_NOQUOTES,"UTF-8");
		$ret = get_td_array($data);
        $i = 0;
		$j = 1;
        $currentList = array();
        $delayList = array();
		$today = date('Y-m-d');
		foreach ($ret as $key => $tr) {
			$name = trim($tr[1]);
			$date = trim($tr[3]);
            $date_begin = trim($tr[2]);
            if (empty($name) || empty($date) || $i == 0) {
                $i++;
                continue;
            }
            $arr = explode('/', $name);
            $bookname = $arr[0];
            $bookname = preg_replace('/[a-zA-Z]{10,}/', '', $bookname);
            $authorname = $arr[1];
            $authorname = preg_replace('/[a-zA-Z]{10,}/', '', $authorname);
            if ($date < $today) {
                $delayList[] = array(
                    'ID' => $j,
                    'NAME' => $bookname,
                    'FROM' => $authorname,
                    'DATE1' => $date_begin,
                    'DATE2' => $date,
                );
                $j++;
            }
            $currentList[] = array(
                'ID' => $i,
                'NAME' => $bookname,
                'FROM' => $authorname,
                'DATE1' => $date_begin,
                'DATE2' => $date,
            );
			$i++;
		}
        $return = array();
        $return[] = array(
            'XQ'=>!empty($currentList)?'在借图书':'您没有在借的图书，可以多到图书馆逛逛哦！',
            'CJ'=>$currentList,
        );
        $return[] = array(
            'XQ'=>!empty($delayList)?'逾期图书':'您没有逾期图书图书',
            'CJ'=>$delayList,
        );
        $return[] = array(
            'XQ'=>!empty($historyList)?'借书历史':'您没有借书历史',
            'CJ'=>$historyList,
        );
		return $return;
	}

	public function getHistoryList() {
		$bookurl = 'http://huiwen.ujs.edu.cn:8080/reader/book_hist.php';
		$data = array();
		$data['para_string'] = 'all';
		$ch = curl_init($bookurl);
		curl_setopt($ch,CURLOPT_HEADER,0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//0显示,1不显示
		curl_setopt($ch, CURLOPT_POST,1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $this->_cookie);//使用cookie
		$data = curl_exec($ch);
		$data =  html_entity_decode($data,ENT_NOQUOTES,"UTF-8");
		curl_close($ch);
		$data = get_td_array($data);
		$i = 0;
        $historyList = array();
        foreach ($data as $key=>$tr) {
			if($i == 0){
				$i++;
				continue;
			}
			else{
                $bookname = $tr[2];
                $bookname = preg_replace('/[a-zA-Z]{10,}/', '', $bookname);
                $authorname = $tr[3];
                $authorname = preg_replace('/[a-zA-Z]{10,}/', '', $authorname);

                $historyList[] = array(
                    'ID' => $i,
                    'NAME' => trim($bookname),
                    'FROM' => trim($authorname),
                    'DATE1' => trim($tr[4]),
                    'DATE2' => trim($tr[5]),
                );
            }
			$i++;
		} 
		return $historyList;
	}

	public function getDelayList() {
		$bookurl = 'http://huiwen.ujs.edu.cn:8080/reader/book_lst.php';
		$ch = curl_init($bookurl);
		curl_setopt($ch,CURLOPT_HEADER,0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//0显示,1不显示
		curl_setopt($ch, CURLOPT_COOKIEFILE, $this->_cookie);//使用cookie
		$data = curl_exec($ch);
		$data =  html_entity_decode($data,ENT_NOQUOTES,"UTF-8");
		curl_close($ch);
		$ret = get_td_array($data);
		$books = "";
		$deadline = date("Y-m-d",strtotime("1 day"));
		foreach ($ret as $key=>$tr) {
            $bookname = $tr[1];
            $bookname = preg_replace('/[a-zA-Z]{10,}/', '', $bookname);
            $name = trim($bookname);
			$date = trim($tr[3]);
            if (empty($name) || empty($date)) {
                continue;
            }
			if($date == $deadline){
				//明天到期
				if($books) $books .= '、《'.$name.'》';
				else $books .= '《'.$name.'》';
			}
		}
		return $books;
	}
}

/*
header("Content-type:text/html;charset=utf-8");
$id = '3110604039';
$pwd = '565292';
$lib = new Library($id, $pwd);
$ret = $lib->getDelayList();
var_dump($ret);
*/