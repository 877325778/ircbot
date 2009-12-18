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
	$uri="http://dict.youdao.com/search?q=${word}";
	$content=file_get_contents($uri);
	$content=strstr($content, '<td valign');
	if (!$content){
		return "抱歉，没有查到{$word}";
	}else{
		$content=strstr($content, '</table>', true);
		// 这里采用正则表达式效率稍高，且代码较优雅
		$content=preg_replace('/<([^>]+?)>/s', '',$content);
		$content=preg_replace('/\s/s', '', $content);
		$content=str_replace('&nbsp;', '', $content);
	}
	return $content;
}
?>

