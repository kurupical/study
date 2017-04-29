
<?php

/*
	xbrl_read.php
		目的:
			財務諸表の各科目の金額をSQLで検索できるようにするため
			たとえば:
			◆割安株の検索の自動化
				以下条件を満たす企業を探す
					時価総額 < 現金及び現金に換金できる資産(有価証券)


		処理概要:
			/data/xbrlに格納されているXBRLデータから、財務諸表の各科目の数値を取得し
			データベースに格納する
*/

/* 定数の定義 */
define("BALANCE_SHEET_FILE","0104010_honbun");
define("HEADER_FILE","0000000_header");

/* 共通ライブラリ */
require('..\util_db.php');

/* タイムアウト時間の設定 デフォルトは30秒になってる*/
ini_set("max_execution_time",600);

/* 共通処理 */


if ($_REQUEST['test'] === "1"){
	// DB接続
	$db = util_db_Dbconnect();

	$sql = "select * from stock_finance";
	$recordSet = mysqli_query($db,$sql);
	var_dump(mysqli_fetch_assoc($recordSet));
}
if ($_REQUEST['getbalancesheet'] === "1" ){

	/* XBRLファイルの取得 */
		//手動でやる。。（高難易度のためとりあえずパス！）

	/* 財務諸表ファイル名の取得 */
	$xbrl_dir = dirname(__FILE__).'/data/xbrl/';
	$gotfilename = getfilename($xbrl_dir,BALANCE_SHEET_FILE);
	/* html取得→配列に格納 */
	$gotBalanceSheetData = array();
	foreach ($gotfilename as $key => $value){
		//htmlファイルから、財務諸表のデータを取得する
		$ary = getBalanceSheetData($value);
		//取得したデータをすべて配列に格納する
		foreach($ary as $key2 => $value2){
			array_push($gotBalanceSheetData,$value2);
		}
	}
	//var_dump($gotBalanceSheetData);

	/* テーブルロード */
	foreach($gotBalanceSheetData as $key => $value){
		insertData($value);
	}

}

function getfilename($dir,$target){
	/*
		処理概要:
			ディレクトリ内(サブディレクトリ含む)のファイル名を取得し、
			配列で返す　※サブディレクトリは再帰呼び出しして対応
		引数:
			$dir : 検索ディレクトリ
			$target : 取得するファイルの種類
		戻り値:
			array $gorfilename : ファイル名の配列
	*/

	// 変数の型をあらかじめ指定しとかんとエラーになる
	$gotfilename = array();

	if ( is_dir( $dir ) and $handle = opendir( $dir ) ){

		while( ($xbrl_file = readdir($handle)) !== false ) {
			$gotdata = $dir . $xbrl_file;
			switch (filetype($gotdata)){
				case "file":
					//財務諸表のファイルのみ、arrayに追加
					if(strstr($gotdata,$target)){
						array_push($gotfilename,$gotdata);
					}
					break;
				case "dir":
					if (!($xbrl_file == "." OR $xbrl_file == "..")){
						//ディレクトリなら、再帰呼び出し
						$ary = getfilename( $gotdata."/", $target);
						foreach($ary as $key => $value){
							//取得したデータをすべて戻り値にセットする
							array_push($gotfilename,$value);
						}
					}
					break;
				default:
					echo filetype($gotdata);
					echo "error_gotdata!".var_dump($gotdata);
					echo "<br>";
					break;
			}
		}
	} else {
		var_dump(is_dir($dir));
		var_dump($handle);
		echo "eroor_handleopen!";
	}
	return $gotfilename;
}

function getBalanceSheetData($data_path){
	/*
		処理概要:
			・渡されたディレクトリに存在するファイルから、勘定科目を抽出し
			　配列に格納する
			・科目取得ボタンを押下した場合は、科目名を表示する
			　（それをソースにコピペして、科目を定義する）
		引数:
			$data_path : XBRLデータのパス
		戻り値:
			array $gotBalanceSheetData : 財務諸表のデータの配列
							1 stockCode : 企業コード
							2 data_day : 財務諸表の情報の年月日
							3 account_segment : 勘定科目の種類
							4 account_name : 勘定科目名(正式な名前 現金および預金とか)
							5 amount_of_value : 金額
	*/

	/* 変数の宣言 */
	$gotBalanceSheetData = array();
	$gotDataAry = array();

	/* 情報の読み取り(ヘッダ(企業の基本情報)) */
	//財務諸表の発表日付 (ファイル名からYYYYMMDD形式で取得)
	$data_pathinfo = pathinfo($data_path);
	$data_filename = $data_pathinfo['basename'];
	$data_day = substr($data_filename,46,4)
								.substr($data_filename,51,2)
								.substr($data_filename,54,2);

	//企業コード
	$data_dir = $data_pathinfo['dirname'];
	$gotDataAry = getfilename($data_dir."/",HEADER_FILE);
	$gotData = file_get_contents($gotDataAry[0]);
	$gotData = explode( "\n", $gotData);	// getfilenameで返される件数は1件だけのはず
	foreach($gotData as $key => $line){
		if (strstr($line," name=\"jpdei_cor:SecurityCodeDEI\"")){
			$stockCode = strstr($line," name=\"jpdei_cor:SecurityCodeDEI\"");
			$str_length_start = strpos($stockCode,">");
			$str_length_end = strpos($stockCode,"<");
			//企業コードxxxxの該当行
			//　→name="jpdei_cor:SecurityCodeDEI" contextRef="FilingDateInstant">xxxx0<...
			$stockCode = substr($stockCode,$str_length_start + 1,($str_length_end - $str_length_start - 2));
		}
	}

	/* 情報の読み取り(データ(勘定科目)) */
	$gotData = file_get_contents( $data_path );
	$gotData = explode( "\n", $gotData );
	foreach($gotData as $key => $line){
		switch (true) {
			//金額の単位の取得
			case strstr($line,"単位："):
				switch (true) {
					case strstr($line,"百万円"):
						$unit = 1000000;
						break;
					case strstr($line,"千円"):
						$unit = 1000;
						break;
					default:
						print("ERROR!unit_not_found!! file:" . $data_path);
						break;
				}
				break;

			//勘定科目の種類を取得
			case ($seg = getAccountSegment($line)) !== "":
				$account_segment = $seg;
				break;

			//明細の取得
			case strstr($line,"unitRef=\"JPY\"") && strstr($line,"CurrentQuarterInstant"):
				//科目名の取得
				if(strstr($line,"ＭＳ 明朝")) {
					$account_name = strstr($gotData[$key-7],">");
				} else {
					$account_name = strstr($gotData[$key-10],">");
				}
				$str_length_start = strpos($account_name,">");
				$str_length_end = strpos($account_name,"<");
				$account_name = substr(	$account_name
																,$str_length_start + 1
																,($str_length_end - $str_length_start - 1));

				//金額の取得 ixt:numdotdecimal">99,999,999 < ...から、カンマを抜いた値(99999999)を取得する
				$amount_of_value = strstr($line,"ixt:numdotdecimal");
				$str_length_start = strpos($amount_of_value,">");
				$str_length_end = strpos($amount_of_value,"<");
				$amount_of_value = substr($amount_of_value
																	,$str_length_start + 1
																	,($str_length_end - $str_length_start - 1));
				//不要な文字を抜く
 				$amount_of_value = str_replace(",","",$amount_of_value);
				$amount_of_value = str_replace("※","",$amount_of_value);

				//マイナスにする
				if(strstr($line,"△")){
					$amount_of_value = $amount_of_value * (-1);
				}
				//単位をかける
				$amount_of_value = $amount_of_value * $unit;
				array_push($gotBalanceSheetData,array($stockCode
																							,$data_day
																							,$account_segment
																							,$account_name
																							,$amount_of_value));
		}
	}
	return $gotBalanceSheetData;
}

function getAccountSegment($line){
	/*
		処理概要:
			・渡された行から、勘定科目の項目を取得する
		引数:
			$line : htmlファイルから取得した行
		戻り値:
			$account_segment : 勘定科目の種類
	*/
	switch (true){
		case strstr($line,">流動資産<"):
			$account_segment = "流動資産";
			break;
		case strstr($line,">流動資産合計<"):
			$account_segment = "流動資産合計";
			break;
		case strstr($line,">有形固定資産<"):
			$account_segment = "有形固定資産";
			break;
		case strstr($line,">無形固定資産<"):
			$account_segment = "無形固定資産";
			break;
		case strstr($line,">投資その他の資産<"):
			$account_segment = "投資その他の資産";
			break;
		case strstr($line,">資産合計<"):
			$account_segment = "資産合計";
			break;
		case strstr($line,">固定資産合計<"):
			$account_segment = "固定資産合計";
			break;
		case strstr($line,">流動負債<"):
			$account_segment = "流動負債";
			break;
		case strstr($line,">固定負債<"):
			$account_segment = "固定負債";
			break;
		case strstr($line,">負債合計<"):
			$account_segment = "負債合計";
			break;
		case strstr($line,">株主資本<"):
			$account_segment = "株主資本";
			break;
		case strstr($line,">その他の包括利益累計額<"):
			$account_segment = "その他の包括利益累計額";
			break;
		case strstr($line,">非支配株主持分<"):
			$account_segment = "非支配株主持分";
			break;
		case strstr($line,">純資産合計<"):
			$account_segment = "純資産合計";
			break;
		case strstr($line,">負債純資産合計<"):
			$account_segment = "負債純資産合計";
			break;
		default:
			$account_segment = "";
			break;
	}

	return $account_segment;
}
function insertData($ary){

	// DB接続
	$db = util_db_Dbconnect();

	//情報の取得
	$stockcode = $ary[0];
	$data_day = $ary[1];
	$account_segment = $ary[2];
	$account_name =$ary[3];
	$amount_of_value = $ary[4];

	// キーの消去
	$sql = "delete from stock_finance
					where stockcode = $stockcode and
								data_day = $data_day and
								segment = '$account_segment' and
								account_name = '$account_name'";
	mysqli_query($db, $sql);
	if (mysqli_sqlstate($db) > 0){
		print("delete error! sqlstate = ".mysqli_sqlstate($db) . "<br>");
	}

	// キーの挿入
	$sql = "insert into stock_finance
					values($stockcode
								,$data_day
								,'$account_segment'
								,'$account_name'
								,$amount_of_value)";
	mysqli_query($db, $sql);
	if (mysqli_sqlstate($db) > 0){
		print("<br> insert error! sqlstate = ".mysqli_sqlstate($db) . "<br>");
		var_dump($ary);
	}
}

?>
<!DOCUTYPE html>
<html lang="ja">
	<head>
	  <meta charset="utf-8">
		<title>株価取得</title>
		<style>
		</style>
		<link rel="stylesheet" href="default.css">
	</head>
	<body>
		<h1>株価取得</h1>
		<hr>
		<div>
			<form action="" method="post">
				<input type="hidden" name="getbalancesheet" value="1">
				<input type="submit" value="株価取得"><br>
			</form>
			<form action="" method="post">
				<input type="hidden" name="test" value="1">
				<input type="submit" value="テスト"><br>
			</form>
		</div>
	</body>
