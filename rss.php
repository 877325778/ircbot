<?php
/*
 *	RSS生成器
 *
 * 说明：
 * 	自动将irc中的信息自动截取输出
 * */
$test = array();
$output;

@exec("tail log/arch-cn-".date("Ymd").".log", &$test);
foreach($test as $t)	$output .=htmlspecialchars($t)."<br />\r\n";

echo $output;

?>
