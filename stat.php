<?php
include_once("config.php");

global $when;
$when=date("Ymd");

function update_stat($nick, $message)
{
	global $dbname, $when;
	$words = strlen($message);

	// 先查询数据库中是否已有记录
	$sql = "select * from `$dbname`.`stat` 
		where `nick`='${nick}' and `date`='${when}'";
	$rst = mysql_fetch_array(mysql_query($sql), MYSQL_ASSOC);

	// 如数据库已有记录则更新，否则添加
	if ($rst){
		echo "更新";
		$sql = "update `$dbname`.`stat` ";

		// 依次更新字数统计、单句最大字数、句数统计
		$rst[words] += $words;
		($words < $rst[length]) || $rst[length] = $words;
		$rst[sentences]++;

		// 更新数据库
		$sql .= "set `words`='${rst[words]}', 
				`length`='${rst[length]}', 
				`sentences`='${rst[sentences]}'
				where `nick`='${rst[nick]}' and `date`='${rst[date]}'";
		echo $sql;
		mysql_query($sql);
	}else{
		// 如数据库中无记录，直接插入即可
		$sql = "insert `${dbname}`.`stat` 
				(`date`, `nick`, `words`, `length`, `sentences`) 
			values('${when}', '${nick}', '${words}', '${words}', '1')";
		mysql_query($sql);
	}
}

/*
 *	获取统计信息（报表）函数
 *
 * 说明
 * 	无需任何参数，直接返回统计信息
 */
function get_stat(){
	global $dbname, $when;
	$columns = array("words", "length", "sentences");
	echo "test";
	foreach($columns as $column){
		$sql = "select `nick` from `${dbname}`.`stat`
				order by `$column` DESC limit 1";
		$rst = mysql_fetch_array(mysql_query($sql), MYSQL_ASSOC);
		$stat[$column]=$rst['nick'];
	}
	$rtn = "今天${stat[words]}最水，${stat[length]}肺货量最大，${stat[sentences]}最喜欢说话";
	return $rtn;
}

function get_my_stat($nick){
	global $dbname, $when;
	$sql = "select * from `${dbname}`.`stat` where `date`='${when}' and `nick`='${nick}'";
	$rst = mysql_fetch_array(mysql_query($sql), MYSQL_ASSOC);
	if(!$rst)	return "妈呀，你是鬼啊！";
	else{
		return "${nick}老弟，你娃今天在${rst[sentences]}次发言中共说了${rst[words]}个字，最多一次吐完了${rst[length]}个唾沫，平均每次".$rst[words]/$rst[sentences]."个！";
	}
}
?>
