<?php
/*
 * 获取中国天气网（http://www.weather.com.cn）
 *
 * 功能说明：
 * 	能够自动获取中国天气网的城市代码，并更新到数据库、记录到文件；
 */
ob_end_clean();
$urlbase = "http://www.weather.com.cn/data/listinfo/city";
$file = file_get_contents($urlbase.".xml");
$file = preg_replace("/\|[^,]*/","",$file);	//省级代码需要递归，直接去掉省的名字
$provinces = explode(",",$file);
foreach($provinces as $province ){
	$file = file_get_contents($urlbase.$province.".xml");
	$file = preg_replace("/\|[^,]*/", "", $file);
	$cities = explode(",", $file);
	
	foreach($cities as $city){
		$file = file_get_contents($urlbase.$city.".xml");
		$places = explode(",", $file);
		foreach($places as $place){
			$place = explode("|", $place);
			$file = file_get_contents($urlbase.$place[0].".xml");
			$file = explode("|", $file);
			echo $place[1].$file[1]."<br />";flush();
		}

	}
}
?>
