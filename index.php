<?php
	if (!empty($_SERVER['HTTPS']) && ('on' == $_SERVER['HTTPS'])) {
		$uri = 'https://';
	} else {
		$uri = 'http://';
	}
	$uri .= $_SERVER['HTTP_HOST'];
	print('<a href="login.php">一行掲示板へ！</a><br>');
	print('<a href="stock/index.php">株価であそぼ！</a>');
	exit;
?>
Something is wrong with the XAMPP installation :-(
