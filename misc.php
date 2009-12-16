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
 * 	返回有道网的词语释义
 */
function check_dict($word){
	$uri="http://dict.youdao.com/search?q=${word}";
	$content=file_get_contents($uri);

	$rst=preg_replace("/(.+?)<div id=\"engchnblock\">(.+?)<script>.*/s", "$2", $content);
	$rst=preg_replace("/<script[^>]+?>(.+?)<\/script>/", '', $rst);
	$rst=preg_replace("/<[^>]+?>|\n|\s/", '', $rst);
	$rst=preg_replace("/&nbsp;/", ' ', $rst);
	return $rst;
}

?>
