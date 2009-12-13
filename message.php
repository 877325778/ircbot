<?php
/*
 *	留言功能函数库
 *
 * 说明：
 *	主要为ircbot提供留言箱的功能；
 * 	需要调用config.php文件以联结数据库；
 */
include_once('config.php');

/*
 * 留言（发送信息）功能
 *
 * 参数：
 * 	收发人的昵称、发送的内容
 * 	系统的时间（全局变量中获取）
 */
function send_message($sender, $receiver, $content){
	global $dbname;

	$when = time();

	$sql = "insert `$dbname`.`message` (`sender`,`receiver`,`when`,`content`,`unread`)
		values('$sender','$receiver','$when','$content','1')";
	mysql_query($sql) || printf("${when},${sender}给${receiver}留言失败");
}
/*
 * 阅读留言功能
 *
 * 参数：
 * 	收件人的昵称
 * 	系统的时间（全局变量中获取）
 *
 * 返回值：
 * 	所有未读消息组成的数组
 */
function read_message($receiver){
	global $dbname;
	$rtn;

	$sql = "select `sender`,`when`,`content` from `${dbname}`.`message`
		where `receiver`='${receiver}' and `unread`='1'";
	if ( ($result = mysql_query($sql)) ){
		while($tmp = mysql_fetch_array($result,MYSQL_ASSOC)){
			$tmp['when'] = date("m月d日H时i分",$tmp['when']);
			$rtn[] = "（{$tmp['when']}）{$tmp['sender']}说：{$tmp['content']}";
		}
		$sql = "update `{$dbname}`.`message` set `unread`='0' where `receiver`='{$receiver}'";
		mysql_query($sql);
	}
	return $rtn;
}
?>
