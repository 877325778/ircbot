<?php
//包含SmartIRC模块
include_once("Net_SmartIRC/SmartIRC.php");
include_once("weather.php");
include_once("stat.php");
include_once("message.php");	//留言功能

// 用于防止本脚本重复运行，造成重复多个机器人
$lockfile = "/tmp/ircbot.lock";

// 避免多个机器人同时上线
if(is_file($lockfile))	die("机器人已经在线了");

/* 需要注册功能 */
$funcs = array(
		//array("监听的消息类型","匹配正则表达式字串", "回调方法名"),
		array(SMARTIRC_TYPE_JOIN,	".*",	"sayhello"),	// 来的时候说你好！
		array(SMARTIRC_TYPE_QUIT | SMARTIRC_TYPE_PART,	".*",	"saybye"),	//离开的时候说再见！
		array(SMARTIRC_TYPE_CHANNEL,	"^${nick}:*\s*￥[1-9][0-9]*$",	"money"),	// 给点钱就是大爷
		array(SMARTIRC_TYPE_CHANNEL,	"^${nick}:*\s*告诉[^\s]*\s.*$",	"leave_mesg"),
		array(SMARTIRC_TYPE_CHANNEL,	"^${nick}$",			"hello"),
		array(SMARTIRC_TYPE_CHANNEL,	"^退出$",			"quit"),
		array(SMARTIRC_TYPE_CHANNEL,	"^${nick}:*\s*帮助",		"help"),
		array(SMARTIRC_TYPE_CHANNEL,	"排行榜$",			"stat"),
		array(SMARTIRC_TYPE_CHANNEL,	"^(今|明|后)天.+天气",		"weather"),
		array(SMARTIRC_TYPE_CHANNEL,	".*",				"log")
	);


// 初始化对象
$bot = &new bot();
$irc = &new Net_SmartIRC();

$irc->setUseSockets(TRUE);

// 注册所需的监听功能
foreach($funcs as $func)
	$irc->registerActionhandler($func[0], $func[1], $bot, $func[2]);

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

// 机器人类
class bot{
	function sayhello(&$irc,&$data){
		global $login_info;

		$nick = strtolower($data->nick);
		// 来人了就给他打个招呼
		// 可以在配置文件中设置$hello_str，以对特定的人打招呼
		if ( array_key_exists($nick, $login_info) ){
			$irc->message(SMARTIRC_TYPE_ACTION, $data->channel, $login_info["$nick"]);
		}else{
			$irc->message(SMARTIRC_TYPE_CHANNEL, $data->channel, "${nick}:hi，等你老久了");
		}

		// 查阅收件箱里的未读留言并发送
		$msgs = read_message($data->nick);
		if($msgs){
			foreach($msgs as $msg){
				$irc->message(SMARTIRC_TYPE_QUERY, $data->nick,$msg);
			}
		}
	}

	function saybye(&$irc,&$data){
		$irc->message(SMARTIRC_TYPE_CHANNEL, $data->channel, "我的妈啊，".$data->nick."终于走了");

		//TODO:
		//	然后就查阅他的收件箱里是否有留言
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
	
	// 留言
	function leave_mesg(&$irc, &$data) {
		global $nick;
		$pregstr="/^${nick}:*\s*告诉([^\s]*)\s*(.*)$/";
		echo $pregstr;
		preg_match($pregstr, $data->message, $str);
		print_r($str);
		send_message($data->nick,$str[1], $str[2]);

	}

	function help(&$irc, &$data){
		global $nick;
		$help = array(
			"机器人功能开发中，不要着急！",
			"-----------------------------华丽的分割线-----------------------------",
			"要查阅聊天记录，请围观 http://www.gooth.cn/ircbot/log/ ！",
			"输入“今（明、后）天地名天气”可以查询天气。如“今天成都天气”",
			"输入“排行榜”可以查询今天聊天室的统计信息。即：“排行榜”",
			"对机器人输入“告诉XX 消息”（注意昵称后有个空格）可以给XX留言。如：“{$nick}:告诉贾君鹏 你妈妈喊你回去吃饭”，这样对方在下次登录的时候，机器人会以私聊的方式将你的留言的信息告诉他。",
			"对着机器人输入“排行榜”可以查询你自己今天的统计信息，如“at:排行榜”。",
			"-----------------------------华丽的分割线-----------------------------",
			"有想法？找Athurg <athurg#gooth.cn>！"
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
			file_put_contents($filename, 
					"本日志记录着irc://irc.oftc.net/#arch-cn房间的公共聊天记录，
					如您有任何问题，烦请联系Athurg<athurg#gooth.cn>\n"
				);
		}
		file_put_contents($filename, date("[Y-m-d H:i:s]<").$data->nick.">:".$data->message."\n", FILE_APPEND);

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
?>
