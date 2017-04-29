<?php

/* 定数 */
define("PAGE_ONCE", 5); //1ページに表示されるページ数

/* 共通ライブラリ */
require('util_db.php');

/* 処理 */

/* 初期処理 */
session_start();
$db = util_db_Dbconnect();
$login_id = $_SESSION['login_id'];

/* 二重更新チェック */
$session_token = isset($_SESSION['token']) ? $_SESSION['token'] : '';
$post_token = isset($_POST['token']) ? $_POST['token'] : '';

if ($session_token === $post_token){
	if ($_REQUEST['tweet_post'] === "1") {
		/* 投稿時の処理 */
		$tweet = htmlspecialchars($_REQUEST['tweet'], ENT_QUOTES);
		$sql = "insert into tweets(id, tweet) ".
		       "values ('$login_id', '$tweet')";
		mysqli_query($db, $sql) or die(mysqli_error($db));
	}

	if ($_REQUEST['tweet_del'] === "1") {
		/* ツイート削除時の処理 */
		if ($_REQUEST['del_seq_no'] > 0) {
			$del_seq_no = $_REQUEST['del_seq_no'];
		} else {
			$del_seq_no = 0;
		}
		$sql = "delete from tweets where seq_no = $del_seq_no";
		mysqli_query($db, $sql) or die(mysqli_error($db));
	}
}

if ($_REQUEST['logout'] === "1") {
	/* ログアウト時の処理 */
	unset($_SESSION['login_id']);
	header("Location: login.php");
}

/* 表示ページ計算処理 */

//maxページの計算
$sql = "select count(*) as page_count from tweets";
$recordSet = mysqli_query($db, $sql);
$data = mysqli_fetch_assoc($recordSet);
$max_page = ceil($data['page_count']/ PAGE_ONCE); //maxページ数の計算(切り上げ)

//現在のページを取得
$page = $_REQUEST['page'];
if ($page == ''){  //画面展開時は$pageが空白
	$page = 1;
} elseif ($page >= $max_page){
	$page = $max_page;
}
$start = ($page - 1) * PAGE_ONCE;
$sql = "select * from tweets order by seq_no desc limit $start ,".PAGE_ONCE;
$recordSet = mysqli_query($db, $sql);

/* トークンの生成 */
$token = md5(uniqid(rand(), true));	//乱数 + 一意のID　の文字列をハッシュにする
$_SESSION['token'] = $token;


?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="utf-8">
	<title>メイン画面</title>
	<style>
	</style>
	<link rel="stylesheet" href="default.css">
</head>
<body>
	<h1>メイン画面</h1>
		<p>ようこそ <?php print($login_id); ?> さん </p>
	<hr>
	<!-- 投稿処理 --!>
	<h2>投稿フォーム</h2>
	<form action="" method="post">
		<input id="tweet" type="text" name="tweet" size="50" maxlength="100" value="" />
		<input type="hidden" name="tweet_post" value="1">
		<?php
		print('<input type="hidden" name="token" value="' .$_SESSION['token']. '">');
		?>
		<br>
		<input type="submit" value="投稿する"><br>
	</form>
	<hr>
	<!-- ツイート表示処理 --!>
	<h2>書き込み</h2>
	<table>
		<?php
		while ($data = mysqli_fetch_assoc($recordSet)){
		?>
			<tr>
				<td>
					No.<?php print($data['seq_no']); ?>
					<b>user:</b><?php print($data['id']); ?>
					<?php
					if ($login_id === $data[id]) {
						print('<form action="" method="post">');
						print('<input type="hidden" name="tweet_del" value="1">');
						print('<input type="hidden" name="del_seq_no" value="' . $data['seq_no'] . '">');
						print('<input type="hidden" name="token" value="' .$_SESSION['token']. '">');
						print('<input type="submit" value="削除">');
						print('</form>');
					}
					?>
				</td>
			</tr>
			<tr>
				<td>
					<?php print($data['tweet']); ?>
					<hr>
				</td>
			</tr>
		<?php
		}
		?>
	</table>

	<?php
	if ($page < $max_page){
		$next_page = $page + 1;
		print('<a href="main.php?page='. $next_page .'"><<前</a>　');
	}
	if ($page > 1){
		$before_page = $page - 1;
		print('<a href="main.php?page='. $before_page .'">次>></a>　');
	}
	print('<a href="main.php">最新</a>');
	?>
	<br>
	<form action="" method="post">
		<input type="hidden" name="logout" value="1">
		<input type="submit" value="ログアウト">
	</form>
</body>
