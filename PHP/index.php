<?php
if(isset($_POST['password'])){
    if($_POST['password'] == "1116"){
        header("Location: ../../imageshow.php?path=./");
        exit;
    }
    else{
        echo "パスワードが違います";
    }
}
?>
<!DOCTYPE html>
<html lang = "ja">
<head>
<meta charset = "UFT-8">
<title>ログインフォーム</title>
</head>
<body>
<h1>パスワード入力</h1>
<form action = "index.php" method = "post">
<input type = "text" name ="password"><br/>
<input type = "submit" value ="送信">
</form>
</body>
</html>