<?php
/* 定数 */
define("SUCCESS",1);					// 登録成功
define("ID_IS_ENPTY",101);		// 入力チェック(ID未入力)
define("PW_IS_ENPTY",102);		// 入力チェック(PW未入力)
define("PRIMARY_ERROR",201);	// 登録失敗(ID重複)
define("DUPLICATE_POST",301);	// 二重投稿
define("OTHER_ERROR",999);		// 登録失敗(その他エラー)


/* 共通ライブラリ */
require('util_db.php');
require('util_function.php');

/* 処理 */
session_start();

$db = util_db_Dbconnect();

if ($_POST['regist_act'] == 1){
	/* 登録ボタン押下時の処理 */
	$regist_ret = SUCCESS;
	$regist_id = htmlspecialchars($_REQUEST['regist_id'], ENT_QUOTES);
	$regist_pw = htmlspecialchars($_REQUEST['regist_pw'], ENT_QUOTES);

	//二重投稿チェック
	$session_token = isset($_SESSION['token']) ? $_SESSION['token'] : '';
	$post_token = isset($_POST['token']) ? $_POST['token'] : '';
	if ($session_token !== $post_token){
		$regist_ret = DUPLICATE_POST;
	}

	//入力チェック
	switch(true){
		case (util_function_RemoveSpace($regist_id) === ""):
			$regist_ret = ID_IS_ENPTY;
			break;
		case (util_function_RemoveSpace($regist_pw) === ""):
			$regist_ret = PW_IS_ENPTY;
			break;
		default:
			break;
	}

	//SQL実行
	if ($regist_ret === SUCCESS) {
		$sql = "insert into users(id, password, regist_time) ".
					 "values ('$regist_id', '$regist_pw' , now())";
		mysqli_query($db, $sql);
		switch(mysqli_sqlstate($db)) {
			case 0:
				$regist_ret = SUCCESS;
				break;
			case 23000: //一意性制約
				$regist_ret = PRIMARY_ERROR;
				break;
			default:
				$regist_ret = OTHER_ERROR;
				break;
		}
	}
}

//トークンの生成
$token = md5(uniqid(rand(), true));	//乱数 + 一意のID　の文字列をハッシュにする
$_SESSION['token'] = $token;

?>

<!DOCUTYPE html>
<html lang="ja">
<head>
  <meta charset="utf-8">
	<title>ログイン画面</title>
	<style>
	</style>
	<link rel="stylesheet" href="default.css">
</head>
<body>
	<h1>ユーザー登録</h1>
	<hr>
	<div>
		<form action="" method="post">
			<table>
				<tr>
					<th>
						ログインＩＤ　
					</th>
					<th>
						<input id="regist_id" type="text"     name="regist_id"size="15" maxlength="10">
					</th>
				</tr>
				<tr>
					<th>
						パスワード　
					</th>
					<th>
						<input id="regist_pw" type="password" name="regist_pw"size="15" maxlength="20">
					</th>
				</tr>
			</table>
			<input type="hidden" name="regist_act" value=1>
			<input type="hidden" name="token" value=<?php print($token) ?>>
			<input type="submit" value="新規登録">
		</form>
	</div>
	<br>
	<br>
	<?php
	switch ($regist_ret){
		case 0;
			break;
		case ID_IS_ENPTY:
			print('IDが入力されていません');
			break;
		case PW_IS_ENPTY;
			print('パスワードが入力されていません');
			break;
		case SUCCESS:
			print('新規登録しました');
			break;
		case PRIMARY_ERROR;
			print('そのユーザーは既に登録されています');
			break;
		case DUPLICATE_POST;
			break;
		default:
			print('その他エラー');
			break;
	}
	?>
	<br>
	<a href="login.php">ログイン画面に戻る</a>
</body>
