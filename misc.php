<?php
/*
 * 	未归档的功能函数库
 * 
 * 包含以下功能函数：
 * 	查字典：check_dict()
 */


/*
 *	查字典函数
 * 
 * 说明：
 * 	输入要查询的中、英文单词
 * 	返回有道网的词语释义或者单词不存在的提示
 */

function check_dict($word){
	$word=trim($word);
	$custom_dict=array(
		'muxueqz'=>'那个卖服务器的！',
		'athurg'=>'找我老大有事儿？',
		'steamedfish'=>'传说中用油炸的蒸鱼……',
		'ghostm55'=>'鬼门关左护卫！',
		'zfish'=>'机器人！！',
		'at'=>'万能的神，伟大的真主。',
		'huntxu'=>'机器人附身了！',
		//''=>'',
	);
	if(array_key_exists(strtolower($word),$custom_dict)){
		$special_mean = $custom_dict[strtolower($word)];
	}
	$uri="http://dict.youdao.com/search?q=${word}";
	$content=file_get_contents($uri);
	$content=strstr($content, '<td valign');
	if (!$content){
		$content= $special_mean ? $special_mean : "抱歉，没有查到{$word}。";
	}else{
		$content=strstr($content, '</table>', true);
		// 这里采用正则表达式效率稍高，且代码较优雅
		$content=preg_replace('/<([^>]+?)>/s', '',$content);
		$content=preg_replace('/\s/s', '', $content);
		$content=str_replace('&nbsp;', '', $content);
		$content.="      也可能意指".$special_mean;
	}
	return $content;
}
?>

