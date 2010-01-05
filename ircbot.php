<?php
//包含SmartIRC模块
include_once("Net_SmartIRC/SmartIRC.php");
include_once("weather.php");	//天气预报功能
include_once("stat.php");	//状态查询功能
include_once("message.php");	//留言功能
include_once("misc.php");	//其他功能

// 用于防止本脚本重复运行，造成重复多个机器人
$lockfile = "/tmp/ircbot.lock";

// 避免多个机器人同时上线
if($debug && is_file($lockfile))	die("机器人已经在线了");

/* 需要注册功能 */
$cmd_pre="^AT";
$cmd_pre.="\s*:*\s*";
$funcs = array(
		//array("监听的消息类型","匹配正则表达式字串", "回调方法名"),
		array(SMARTIRC_TYPE_CHANNEL, ".*", "log"),
		array(SMARTIRC_TYPE_JOIN, ".*", "sayhello"),	// 来的时候打招呼！
		#array(SMARTIRC_TYPE_QUIT | SMARTIRC_TYPE_PART, ".*", "saybye"),	//离开的时候

		array(SMARTIRC_TYPE_CHANNEL, "^{$nick}\s$",			"hello"),
		array(SMARTIRC_TYPE_CHANNEL, $cmd_pre."查\s.*", "dict"),	// 查字典
		array(SMARTIRC_TYPE_CHANNEL, $cmd_pre."￥[1-9][0-9]*$", "money"),	// 给点钱就是大爷
		array(SMARTIRC_TYPE_QUERY, $cmd_pre."告诉[^\s]*\s.*$", "leave_mesg"),
		array(SMARTIRC_TYPE_CHANNEL, $cmd_pre."退下$", "quit"),
		array(SMARTIRC_TYPE_CHANNEL, $cmd_pre."帮助", "help"),
		array(SMARTIRC_TYPE_CHANNEL, $cmd_pre."排行榜$", "rank"),
		array(SMARTIRC_TYPE_CHANNEL, $cmd_pre."统计$", "mystat"),
		array(SMARTIRC_TYPE_CHANNEL, $cmd_pre."[今|明|后]天.+天气", "weather"),
	);


// 初始化对象
$bot = &new bot();
$irc = &new Net_SmartIRC();

$irc->setUseSockets(TRUE);	//优先使用Sockets
$irc->setAutoReconnect(TRUE);	//掉线自动重登录
$irc->setChannelSyncing(TRUE);	//启动房间同步，自动重重登录需要

// 注册所需的监听功能
foreach($funcs as $func)
	$irc->registerActionhandler($func[0], "/{$func[1]}/u", $bot, $func[2]);

$irc->registerTimehandler(80000, $bot, "loop");
// 联接服务器登陆机器人，然后进入房间
$irc->connect($host, $port);
$irc->login($nick, $nick_desc);
$irc->join($chan);

// 成功登陆后建立锁定文件，用于WEB界面查询登陆状态
// 然后开始监听
$debug || touch($lockfile);
$irc->listen();

// 注销后删除用于查询登陆状态的锁定文件
$irc->disconnect();
unlink($lockfile);

// 机器人类
class bot{
	function sayhello(&$irc,&$data){
		global $login_info, $nick;

		if($data->nick==$nick)	return;
		else	$nickname = strtolower($data->nick);

		// 来人了就给他打个招呼
		// 可以在配置文件中设置$login_info，以对特定的人打招呼
		if ( array_key_exists($nickname, $login_info) ){
			$irc->message(SMARTIRC_TYPE_ACTION, $data->channel, $login_info["$nickname"]);
		}

		// 查阅收件箱里的未读留言并发送
		$msgs = read_message($data->nick);
		if($msgs){
			foreach($msgs as $msg){
				$irc->message(SMARTIRC_TYPE_QUERY, $data->nick,$msg);
			}
		}
	}


	// 接收指令退出
	function quit(&$irc, &$data) {
		if(strtolower($data->nick)=="athurg"){
			$irc->quit("Mumm call me to have meal...");
		}
	}

	// 定时重复的功能，可以防止掉线、被踢等
	function loop(&$irc) {
		global $chan;
		//$irc->join($chan);// 被踢了自动上线

	}

	// 查字典功能
	function dict(&$irc, &$data) {
		$pattern = "/^AT\s*:*\s*查\s(.*)/";
		$word = preg_replace($pattern, "$1", $data->message);
		$irc->message(SMARTIRC_TYPE_CHANNEL, $data->channel, $data->nick.": ".check_dict($word));
	}

	// 打赏
	function money(&$irc, &$data) {
		$irc->message(SMARTIRC_TYPE_CHANNEL, $data->channel, "谢".$data->nick."公公打赏！");
		$irc->message(SMARTIRC_TYPE_CHANNEL, $data->channel, $data->nick."公公吉祥万福:！");
	}
	
	// 留言
	function leave_mesg(&$irc, &$data) {
		$pregstr="/^AT\s*:*\s*告诉([^\s]*)\s*(.*)$/";
		preg_match($pregstr, $data->message, $str);
		send_message($data->nick,$str[1], $str[2]);
		$irc->message(SMARTIRC_TYPE_CHANNEL, $data->channel,
			$data->nick.":留言已记录！{$str[1]}下次到聊天室的时候就会收到了。");
	}

	function help(&$irc, &$data){
		$help = array(
			"irc聊天机器人，等待你的参与！聊天记录请围观 http://www.gooth.cn/ircbot/log/",
			"--------------",
			"所有指令语法为：“AT”+“功能指令”，功能指令表如下：",
			"查 单词\t\t查字典的功能（中英互译）。如：AT:查 china",
			"今/明/后天地名天气\t\t可以查询该地方的天气。如：AT今天成都天气",
			"告诉XX 留言内容\t\t给人留言，此命令须以私信发送。如：/msg AT 告诉贾君鹏 回去吃饭",
			"统计\t\t查看今天自己发言统计信息。如：AT统计",
			"排行榜\t\t查看聊天室发言排行榜。如：AT排行榜",
		);
		foreach($help as $msg){
			$irc->message(SMARTIRC_TYPE_QUERY, $data->nick, $msg);
		}
	}

	// 记录房间说话日志并更新统计信息
	function log(&$irc, &$data){
		$filename = "log/".substr($data->channel,1).date("-Ymd").".log";

		//新创建的文件，在行首加入文件说明
		if(!is_file($filename)){
			file_put_contents($filename, "本日志记录着irc://irc.oftc.net/#arch-cn房间的公共聊天记录，\r\n如您有任何问题，烦请联系Athurg<athurg#gooth.cn>\n");
		}
		file_put_contents($filename, date("[Y-m-d H:i:s]<").$data->nick.">:".$data->message."\n", FILE_APPEND);

		// 更新统计信息
		update_stat($data->nick, $data->message);
	}

	// 查询排行榜
	function rank(&$irc, &$data){
		$irc->message(SMARTIRC_TYPE_CHANNEL, $data->channel, get_stat());
	} 
	// 查询自己的统计状态
	function mystat(&$irc, &$data){
		$irc->message(SMARTIRC_TYPE_CHANNEL, $data->channel, get_my_stat($data->nick));
	} 

	// 查询天气
	function weather(&$irc, &$data){
		preg_match("/^AT\s*:*\s*([今明后])天(.+)天气.*/u", $data->message, $rst);
		$irc->message(SMARTIRC_TYPE_CHANNEL, $data->channel, weather_check($rst[2], $rst[1]));
	}
}
?>
