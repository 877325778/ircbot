<?php
include_once("config.php");
/*
 * 	更新统计信息函数
 *
 * 描述
 * 	输入被更新的昵称及其发表的信息
 * 	更新数据库中的统计信息
 */
function update_stat($nick, $message)
{
	global $dbname;
	$when=mktime(0,0,0);

	// 我们不统计机器人
	$bot=array("at","ZFish","psycho");
	if(in_array($nick,$bot))	return;

	$keywords = array(
			"happy"	=> array('哈','嘿','嘻',':)',':-)',':P',':D','^_^','^-^','-_-'),
			"jiong"	=> array('囧','无语','...','。。。','……'),
			"yumen"	=> array('郁闷',':('),
			"sad"	=> array('唉','呜呜','555','哭泣','T_T'),
		);

	// 统计特殊状态
	foreach($keywords as $keyword=>$strs){
		$stat[$keyword]=0;

		foreach($strs as $str){
			// 统计当前类别的关键词是否有出现
			// 	如果有，则设置当前类别统计值并退出；
			// 	否则继续查询
			// 	不重复统计同一类中的多个关键词
			if(strstr($message,$str)){
				$stat[$keyword]=1;
				break;
			}
		}

	}
	$stat['words']=mb_strlen($message,"UTF-8");

	// 先查询数据库中是否已有记录
	$sql = "select * from `$dbname`.`stat` 
		where `nick`='${nick}' and `when`='${when}'";
	$rst = mysql_fetch_array(mysql_query($sql), MYSQL_ASSOC);

	// 如数据库已有记录则更新，否则添加
	if ($rst){
		$sql = "update `$dbname`.`stat` ";

		// 依次更新字数统计、单句最大字数、句数统计
		$rst['words'] += $stat['words'];
		($stat['words'] < $rst['length']) || $rst['length'] = $stat['words'];
		$rst['sentences']++;

		$sql .= "set `words`='{$rst['words']}', 
				`length`='{$rst['length']}', 
				`sentences`='{$rst['sentences']}' ";
		// 更新特殊状态
		foreach($keywords as $keyword => $strs){
			$rst[$keyword] += $stat[$keyword];
			$sql .= ",`${keyword}`='{$rst[${keyword}]}' ";
		}

		// 更新数据库
		$sql .= " where `nick`='{$rst[nick]}' and `when`='{$rst[when]}'";
		mysql_query($sql);
	}else{
		// 如数据库中无记录，直接插入即可
		$sql = "insert `${dbname}`.`stat` 
				(
					`when`, 
					`nick`, 
					`words`, 
					`length`, 
					`sentences`,
					`happy`,
					`jiong`,
					`yumen`,
					`sad`) 
				values(
					'${when}',
					'${nick}',
					'{$stat[words]}',
					'{$stat[words]}',
					'1',
					'{$stat[happy]}',
					'{$stat[jiong]}',
					'{$stat[yumen]}',
					'{$stat[sad]}')";
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
	global $dbname;

	$when = mktime(0,0,0);
	$columns = array('words', 'length', 'sentences','happy','jiong','yumen','sad');
	foreach($columns as $column){
		$sql = "select `nick` from `{$dbname}`.`stat`
				where `when`='{$when}'
				order by `{$column}` DESC limit 1";
		$rst = mysql_fetch_array(mysql_query($sql), MYSQL_ASSOC);
		$stat[$column]=$rst['nick'];
	}
	$rtn = "今天{$stat['words']}最水，{$stat['length']}肺活量最大，{$stat['sentences']}最喜欢说话。{$stat['happy']}最开心了，{$stat['jiong']}无语到钻囧肚子里了，{$stat['yumen']}很是郁闷，{$stat['sad']}的伤心事真多，搞得大家开心得不行";
	return $rtn;
}

/*
 *	获取指定昵称的统计信息（报表）函数
 *
 * 说明
 * 	输入昵称，返回该昵称截止到目前
 * 	当天的聊天统计信息
 */
function get_my_stat($nickname){
	global $dbname;
	$when = mktime(0,0,0);
	$sql = "select * from `{$dbname}`.`stat` where `when`='{$when}' and `nick`='{$nickname}'";
	$rst = mysql_fetch_array(mysql_query($sql), MYSQL_ASSOC);
	if(!$rst){
		return "妈呀，你是鬼啊！";
	}else{
		return "{$nickname}老弟，你今儿{$rst['sentences']}次发言中絮叨了{$rst['words']}个字，最多一次吐了{$rst['length']}个，平均每句".$rst['words']/$rst['sentences']."个唾沫！另外，你今天高兴{$rst['happy']}次，囧了{$rst['jiong']}次，郁闷了{$rst['yumen']}次，伤心了{$rst['sad']}次！";
	}
}
?>
