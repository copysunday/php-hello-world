<?php
/**
 * 用户绑定程序
 * @author：孙阳明
 */
require './school/JiangDa.class.php';
require './school/JiangDaNoCode.class.php';
error_reporting(0);
//ini_set("display_errors", "On"); error_reporting(E_ALL | E_STRICT);
if(isset($_POST)){

    $sno = empty($_POST['sno'])?'':trim($_POST['sno']);
    $pwd = empty($_POST['pwd'])?'':trim($_POST['pwd']);
    $openid = trim($_POST['openid']);
    $code = empty($_POST['code'])?'':$_POST['code'];
    $type = empty($_POST['type'])?'score':$_POST['type'];
    $sysType = isset($_POST['sys'])?$_POST['sys']:0;

    if (empty($sno) && empty($pwd)) {
        die('pwd is empty');
    }

    $bd_url = './ujsbd.php?openid='.$openid;

    if ($sysType == 1) {
        $school = new JiangDaNoCode($sno, $pwd);
    }
    else {
        $school = new JiangDa($sno, $pwd, $openid, $code);
    }

    switch ($type) {
        case 'check':
            if(!$school->check()) {//验证失败
                $arr = array('msg'=>'error', 'data'=>'验证失败，可能是教务系统不稳定或密码错误。请稍后重试。');
                exit(json_encode($arr));
            }
            $info = $school->getInfo();//获取个人信息
            $arr = array('msg'=>'success', 'data'=>$info);
            exit(json_encode($arr));
            break;
        case 'card':
            $text = $school->getCard();
            $arr = array('msg'=>'success', 'data'=>$text);
            exit(json_encode($arr));
            break;
        case 'score':
            $score = $school->getAllScore();
            if(empty($score)){
                $arr = array('msg'=>'error', 'data'=>'暂时查不到成绩，请稍后重试。可能的原因：1、教务系统服务器故障；2、本年级功能未开通3、你改了教务系统密码(重绑发：绑定)。');
                exit(json_encode($arr));
            }
            $html = createForm('../t/show.php', array('result'=>base64_encode(json_encode($score))));
            $arr = array('msg'=>'success', 'operate'=>'form', 'data'=>$html);
            exit(json_encode($arr));
            break;
        case 'updateLesson':
            $lesson = $school->getLesson();//获取课表数组
            if (empty($lesson)) {
                $arr = array(
                    'msg'=> 'error',
                    'data'=> '获取课表失败，可能是系统出错或密码错误，后者请重新绑定',
                    'url'=> $bd_url,
                    'operate'=> 'jump'
                );
                exit(json_encode($arr));
            }
            $arr = array('msg'=>'success', 'data'=>$lesson);
            exit(json_encode($arr));
            break;
        case 'bookList':
            include './school/Library.php';
            $lib = new Library($sno, $pwd, $openid, $code);
            $libArr = $lib->getList();
            if(empty($libArr)){
                $arr = array('msg'=>'error', 'data'=>'查不到您的图书馆信息，可能您的借书历史为空，如果不为空请重试');
                exit(json_encode($arr));
            }
            $html = createForm('../t/show_library.php', array('result'=>base64_encode(json_encode($libArr))));
            $arr = array('msg'=>'success', 'operate'=>'form',  'data'=>$html);
            exit(json_encode($arr));
            break;
        case 'xuefei':
            $text = $school->getXueFei();
            $text = str_replace("\n", '<br>', $text);
            $arr = array('msg'=>'success', 'data'=>$text);
            exit(json_encode($arr));
            break;
        case 'getMakeUpArrange':
            $text = $school->getMakeUpArrange();
            $text = str_replace("\n", '<br>', $text);
            $arr = array('msg'=>'success', 'data'=>$text);
            exit(json_encode($arr));
            break;
        case 'getTestArrange':
            $text = $school->getTestArrange();
            $text = str_replace("\n", '<br>', $text);
            $arr = array('msg'=>'success', 'data'=>$text);
            exit(json_encode($arr));
            break;
        case 'cet':
            $text = $school->getGradeScore();
            $text = str_replace("\n", '<br>', $text);
            $arr = array('msg'=>'success', 'data'=>$text);
            exit(json_encode($arr));
            break;
        default:
            $arr = array(
                'msg'=> 'error',
                'data'=> '无法识别请求类型',
                'url'=> $bd_url,
                'operate'=> 'jump'
            );
            exit(json_encode($arr));
    }
}






