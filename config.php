<?php
/*
 * 系统配置
 *
 * 根据实际情况进行修改：
 * 	包含本文件后，会自动联接数据库
 */

/* 数据库服务器、用户名、密码、数据库名 */
$dbhost = 'localhost';
$dbuser = 'ircbot';
$dbpass = 'test';
global $dbname;
$dbname = 'ircbot';

// 机器人登陆位置设置
$host = 'irc.oftc.net';
$port = 6667;
$nick = 'somebot';
$nick_desc = "Athurg's Bot";
$chan = array(
		'#arch-cn',
		'#gooth',
	);

// 设置要自定义打招呼的昵称和打招呼的字串
global $login_info;
$login_info = array(
	'steamedfish' => '新鲜出炉的蒸鱼，谁客气跟谁急啊！',
	'muxueqz' => 'AD:卖虚拟主机啦！卖VPS啦！卖muxueqz啦！',
	'ghosTM55' => '妈呀，鬼来了！',
	'athurg' => '皇上驾到……',
	);

// 设置默认时区
date_default_timezone_set('Asia/Chongqing');


/* 配置修改到此结束 */
mysql_connect($dbhost, $dbuser, $dbpass) || die('无法联接服务器');
mysql_select_db($dbname) || die('数据库不存在');

?>
