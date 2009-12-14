<?php
/*
 * 获取中国天气网（http://www.weather.com.cn）
 *
 * 功能说明：
 * 	能够自动获取中国天气网的城市代码，并更新到数据库、记录到文件；
 */
include_once("config.php");

function connect_dbhost(){
	global $dbhost, $dbuser, $dbpass, $dbname;
	mysql_connect($dbhost, $dbuser, $dbpass) || die("无法联接服务器");
	mysql_select_db($dbname) || die("数据库不存在");
}

function city_code_check($cityname){
	global $dbname;
	$sql = "select `citycode` from `$dbname`.`citycode` where `cityname`='{$cityname}'";
	$result = mysql_fetch_array(mysql_query($sql), MYSQL_NUM);
	return $result[0];
}

function weather_check($city, $when){

	$city_code = city_code_check($city);
	switch($when){
		case "明":
			$hours=48;
			break;
		case "后":
			$hours=72;
			break;
		default:$hours=24;
	}

	$result = file_get_contents("http://wap.weather.com.cn/wap/${city_code}/h${hours}.shtml");
	$result = preg_replace("/.*结束-->\r\n\r\n/s",'',$result);//去头
	$result = preg_replace("/<br>\r\n<a.*/s",'',$result);//去尾
	$result = preg_replace("/<[^>]*>|\r\n/", '', $result);//去HTML代码
	return $result;
}

/*
 * 从中国天气网更新城市代码
 *
 * 功能说明：
 * 	获取中国天气网的城市代码
 * 	并更新到数据库
 * 	更新过程会自动输入结果
 */
function update_city_code(){
	global $dbname;
	$i=0;	//用于计数，满10就输出次状态
	
	//清空原有数据
	mysql_query("truncate table `ircbot`.`citycode`") || die("无法清空数据库");
	
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
				//update here
				$sql = "insert `$dbname`.`citycode` (`citycode`,`cityname`) ";
				$sql .= " values('${file[1]}','${place[1]}')";
				mysql_query($sql);
				if (!(($i++) % 10)) {
					echo "已经完成${i}条了";
					ob_flush();
					flush();
				}
			}
		}
	}
	echo "所有城市更新完毕！";
}


?>
