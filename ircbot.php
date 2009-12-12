<?php
//包含SmartIRC模块
include_once("Net/SmartIRC.php");
include_once("weather.php");
include_once("stat.php");

date_default_timezone_set("Asia/Chongqing");

#新建一个类
class bot{
	function hello(&$irc,&$data){
		$irc->message(SMARTIRC_TYPE_CHANNEL,
		       		$data->channel, 
				$data->nick.":叫什么叫，烦死了。没看见我在挖矿吗？有问题大叫“帮助”就可以了。");
		$irc->message(SMARTIRC_TYPE_CHANNEL,
		       		$data->channel, "/action 话说现在的活人怎么比机器人还无聊，囧orz.");

	}

	// 接收指令退出
	function quit(&$irc, &$data) {
		if($data->nick=="athurg"){
			$irc->quit("Mumm call me to have meal...");
		}
	}
	
	// 打赏
	function money(&$irc, &$data) {
		$irc->message(SMARTIRC_TYPE_CHANNEL, $data->channel, "谢".$data->nick."公公打赏！");
		$irc->message(SMARTIRC_TYPE_CHANNEL, $data->channel, $data->nick."公公吉祥万福:！");
	}

	function help(&$irc, &$data){
		$help = array(
			"机器人功能开发中，不要着急！",
			"-----------------------------华丽的分割线-----------------------------",
			"要查阅聊天记录，请围观 http://www.gooth.cn/ircbot/log/ ！",
			"输入“今（明、后）天地名天气”可以查询天气。如“今天成都天气”",
			"输入“排行榜”可以查询今天聊天室的统计信息。即：“排行榜”",
			"对着机器人输入“排行榜”可以查询你自己今天的统计信息，如“at:排行榜”。",
			"-----------------------------华丽的分割线-----------------------------",
			"有想法？找Athurg <athurg#gooth.cn>！"
		);
		foreach($help as $msg){
			$irc->message(SMARTIRC_TYPE_CHANNEL, $data->channel, $msg);
		}
	}

	// 记录房间说话日志并更新统计信息
	function log(&$irc, &$data){
		$filename = "log/".substr($data->channel,1).date("-Ymd").".log";

		//新创建的文件，在行首加入文件说明
		if(!is_file($filename)){
			file_put_contents($filename, 
					"本日志记录着irc://irc.oftc.net/#arch-cn房间的公共聊天记录，
					如您有任何问题，烦请联系Athurg<athurg#gooth.cn>\n"
				);
		}
		file_put_contents(
				$filename, 
				date("[Y-m-d H:i:s]<").$data->nick.">:".$data->message."\n", 
				FILE_APPEND
			);

		// 更新统计信息
		update_stat($data->nick, $data->message);
	}

	// 查询统计状态
	function stat(&$irc, &$data){
		// 根据消息长度，判断是查询类型
		//	如果长度等于6，即只有“排行榜”三个字，则查询系统统计信息；
		//	否则查询发起人的个人统计信息
		$msg = (mb_strlen($data->message,"UTF-8") > 3) ? get_my_stat($data->nick) : get_stat();
		$irc->message(SMARTIRC_TYPE_CHANNEL, $data->channel, $msg);
	}

	// 查询天气
	function weather(&$irc, &$data){
		preg_match("/^(今|明|后)天(.+)天气.*/",$data->message, $rst);
		$irc->message(SMARTIRC_TYPE_CHANNEL, 
				$data->channel, 
				weather_check($rst[2], $rst[1]));
	}
}
// 机器人登陆位置设置
$host = "irc.oftc.net";
$port = 6667;
$nick = "at";
$nick_desc = "Athurg's Bot";
$chan = array(
		"#arch-cn",
		"#steamedfish",
	);

$lockfile = "/tmp/ircbot.lock";

// 避免多个机器人同时上线
if(is_file($lockfile))	die("机器人已经在线了");

/* 注册功能 */
$funcs = array(
		//array("匹配正则表达式", "回调方法名"),
		array("^${nick}:*\s*￥[1-9][0-9]*$", "money"),
		array("^${nick}$","hello"),
		array("^你妈喊你回去吃饭$","quit"),
		array("^${nick}:*\s*帮助","help"),
		array("排行榜$","stat"),
		array("^(今|明|后)天.+天气","weather"),
		array(".*","log")
	);


// 初始化对象
$bot = &new bot();
$irc = &new Net_SmartIRC();
$irc->setUseSockets(TRUE);

// 注册所需的监听功能
foreach($funcs as $func){
	$irc->registerActionhandler(SMARTIRC_TYPE_CHANNEL,$func[0], $bot, $func[1]);
}

// 联接服务器登陆机器人，然后进入房间
$irc->connect($host,$port);
$irc->login($nick, $nick_desc);
$irc->join($chan);

// 成功登陆后建立锁定文件，用于WEB界面查询登陆状态
// 然后开始监听
touch($lockfile);
$irc->listen();

// 注销后删除用于查询登陆状态的锁定文件
$irc->disconnect();
unlink($lockfile);
?>
