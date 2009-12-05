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
				$data->nick.":我说你叫什么叫，没看见我在挖矿吗？
					有问题对我说“帮助”。
					记住，是对我说，一个字都不许差，否则……");
	}

	// 接收指令退出
	function quit(&$irc, &$data) {
		if($data->nick=="athurg"){
			$irc->quit("Mumm call me to have meal...");
		}
	}
	function help(&$irc, &$data){
		$irc->message(SMARTIRC_TYPE_CHANNEL, $data->channel, "开发中，不要着急！");
		$irc->message(SMARTIRC_TYPE_CHANNEL, $data->channel, "聊天记录，请围观 http://www.gooth.cn/ircbot/log/ ！");
		$irc->message(SMARTIRC_TYPE_CHANNEL, $data->channel, "输入“地名NN小时天气如何”可以查询天气。如“成都72小时天气如何”");
		$irc->message(SMARTIRC_TYPE_CHANNEL, $data->channel, "直接输入“排行榜”可以查询今天聊天室的统计信息。即：“排行榜”");
		$irc->message(SMARTIRC_TYPE_CHANNEL, $data->channel, "对我输入“排行榜”可以查询自己统计信息，如“at:排行榜”。");
		$irc->message(SMARTIRC_TYPE_CHANNEL, $data->channel, "有任何建议或意见，请联系Athurg Gooth <athurg#gooth.cn>！");
	}

	// 记录房间说话日志并更新统计信息
	function log(&$irc, &$data){
		// 记录日志
		$filename = "log/".date("Y-m-d").".log";
		if(!is_file($filename)){
			file_put_contents($filename, "本日志记录了irc://irc.oftc.net/#arch-cn房间的公共聊天记录，如有任何问题，请联系athurg#gooth.cn\n");
		}
		file_put_contents($filename, date("[Y-m-d H:i:s]<").$data->nick.">:".$data->message."\n", FILE_APPEND);

		// 更新统计信息
		update_stat($data->nick, $data->message);
	}

	function stat(&$irc, &$data){
		$irc->message(SMARTIRC_TYPE_CHANNEL, $data->channel, get_stat());
	}
	function mystat(&$irc, &$data){
		$irc->message(SMARTIRC_TYPE_CHANNEL, $data->channel, get_my_stat($data->nick));
	}

	// 天气查询
	function weather(&$irc, &$data){
		$str = $data->message;
		$str = preg_replace("/^at:*\s*([^0-9]*)([247][482])小时天气如何$/", "\${1} \${2}", $str);
		$str = explode(" ", $str);
		$irc->message(SMARTIRC_TYPE_CHANNEL, $data->channel, weather_check($str[0], $str[1]));
	}
}

$host = "irc.oftc.net";
$port = 6667;
$nick = "at";
$chan = "#arch-cn";
$bot = &new bot();
$irc = &new Net_SmartIRC();
$irc->setUseSockets(TRUE);
// registerActionhandler(SMARTIRC_TYPE_CHANNEL, '正则表达式', 调用的类, 调用的方法)
$irc->registerActionhandler(SMARTIRC_TYPE_CHANNEL, "^${nick}$", $bot, 'hello');
//注册退出指令
$irc->registerActionhandler(SMARTIRC_TYPE_CHANNEL, '^你妈喊你回去吃饭$', $bot, 'quit');
// 机器人使用帮助功能
$irc->registerActionhandler(SMARTIRC_TYPE_CHANNEL, "^${nick}:? *帮助", $bot, 'help');
// 注册记录日志记录、状态统计功能
$irc->registerActionhandler(SMARTIRC_TYPE_CHANNEL, '.*', $bot, 'log');
// 注册统计信息查询功能
$irc->registerActionhandler(SMARTIRC_TYPE_CHANNEL, '^排行榜$', $bot, 'stat');
$irc->registerActionhandler(SMARTIRC_TYPE_CHANNEL, "^${nick}:*\s*排行榜$", $bot, 'mystat');
// 天气预报功能
$irc->registerActionhandler(SMARTIRC_TYPE_CHANNEL, "^at:*\s*[^0-9]+[247][482]小时天气如何$", $bot, 'weather');

$irc->connect($host,$port);
// 登录机器人
// login(昵称, 机器人说明)
// 更多参数参考文档
$irc->login($nick, "Athurg Gooth's bot");
// 加入聊天室
// join(要登录的房间数组)
$irc->join(array($chan));
$irc->listen();	//进入监听的死循环
$irc->disconnect();
?>
