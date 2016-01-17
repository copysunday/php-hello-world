<?php
/**
 * @todo 教务系统类接口
 * @author: 孙阳明
 */
require_once 'lib/function.base.php';
//require_once 'lib/CurlUtil.class.php';
//require_once 'lib/simple_html_dom.php';

interface BaseSystem{
	/**
	 * @todo TODO
	 * @return string
	 */
	function login();

	/**
	 * @todo 获取成绩 
	 * @return string
	 */
	function getScore();

	/**
	 * @todo 获取课表
	 * @return array('1'=>array('1-2'=>'') ....'7'=>)
	 */
	function getLesson();

	/**
	 * @todo 获取考试安排
	 * @return_type
	 */
	function getTestArrange();

	/**
	 * @todo 获取个人信息
	 * @return array('name'=>,
	 * 'gender'=>性别(0:保密 1:男 2:女),
	 * 'grade'=>年级：2013)
	 */
	function getInfo();

	/**
	 * @todo 验证用户
	 * @return bool
	 */
	function check();
	
	/**
	 * @todo 查空教室
	 * @return_type
	 */
	function getRoom();
}