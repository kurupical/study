
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' ){
	include 'util_db.php';
	$db = util_db_Dbconnect();

	$login_id = htmlspecialchars($_REQUEST['login_id'], ENT_QUOTES);
	$login_pw = htmlspecialchars($_REQUEST['login_pw'], ENT_QUOTES);

	$login_sql = "select count(*) as login_flg from users where id = '". $login_id ."' and password = '". $login_pw ."'";

	$recordSet = mysqli_query($db, $login_sql);
	$data = mysqli_fetch_assoc($recordSet); //レコード無しならfalseが返る

	$login_fail_flg = false;
	if ($data['login_flg'] > 0) {
	  session_start();
	  $_SESSION['login_id'] = $login_id;
		header("Location: main.php");
		exit;   //入れとかないと後ろの処理が動く
	} else {
		$login_fail_flg = true;
	}
}
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
		<h1>ログイン</h1>
		<hr>
		<div>
			<form action="" method="post">
				<table>
					<tr>
						<th>
							ログインＩＤ　
						</th>
						<th>
							<input id="login_id" type="text" name="login_id" size="20" maxlength="10" value="" />
						</th>
					</tr>
					<tr>
						<th>
							パスワード　
						</th>
						<th>
							<input id="login_pw" type="password" name="login_pw" size="20" maxlength="20" value="" />
						</th>
					</tr>
				</table>
				<input type="submit" value="ログイン"><br>
				<?php
					if ($login_fail_flg) {
						echo "IDまたはパスワードに誤りがあります。";
					}
				?>
				<br>
				<br>
				<a href="regist.php">ユーザー登録</a>する
			</form>
		</div>
	</body>
