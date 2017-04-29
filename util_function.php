
<?php
function util_function_RemoveSpace($str) {
	/*
		全角スペース/半角スペースを取り除く
		　引数:取り除きたい文字列 / 戻り値:取り除いた後の文字列
	*/
	$str = str_replace(" ","",$str);
	$str = str_replace("　","",$str);
	return $str;
}

?>
