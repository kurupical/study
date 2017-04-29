
<?php
/*
	DBに接続するメソッド
	　引数:無し / 戻り値:DB接続情報
*/
function util_db_Dbconnect() {
	DEFINE("LOCAL_ENV",1);		//田邊ノートpc

	$sv_location = LOCAL_ENV;
	//$sv_location = XSERVER_ENV;

	switch ($sv_location){
		case LOCAL_ENV:
			$db = mysqli_connect('localhost','root','','Qwitter')
						or die(mysqli_connect_error());
			break;
	}
	mysqli_set_charset($db, 'utf-8');
	return $db;
}

?>
