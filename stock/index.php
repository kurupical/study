
<!DOCUTYPE html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>株操作メニュー</title>
  <style>
  </style>
  <link rel="stylesheet" href="default.css">
</head>
<body>
  <h1>メニュー</h1>
  <hr>
  <h2>財務諸表取得</h2>
  <a href="xbrl_read.php">XBRLを読み込む</a><br>
  <br>
  <h2>株価取得</h2>
  <form action="getstock.php" method="post">
    <!-- 日付を範囲指定する -->
    <?php
    print("取得したい株価の指定を指定してください <br>");
    print('<input id="date_start" type="date" name="date_start" value="'.
            date('Y-m-d').
            '">');
    print("～");
    print('<input id="date_end" type="date" name="date_end" value="'.
            date('Y-m-d'). //処理日
            '">');
    ?>
    <input type="submit" value="株価取得"><br>
    <!--
    日付の前後判定
    -->
  </form>

</body>
