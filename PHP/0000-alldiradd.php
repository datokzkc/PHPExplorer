<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
</head>
<body>
<?php
//このファイルが存在しているディレクトリを全て追加する
chdir(__DIR__);
mb_internal_encoding("UTF-8");
$list = scandir(__DIR__);
foreach($list as $path){
    $path = realpath($path);
    if(is_dir($path) == FALSE){
        continue;  //ディレクトリ以外は無視
    }
    $path = mb_convert_encoding($path,"UTF-8");
    $replace = [
        // '置換前の文字' => '置換後の文字',
        '\\' => '\\\\',
        "'" => "\\'",
        '"' => '\\"',
    ];
    $path = str_replace(array_keys($replace), array_values($replace), $path);
    $sql = "INSERT INTO dirs_all (path) VALUES ('".$path."')";

    $db = new mysqli("localhost","php","php","php_dir_tag");
    if($db->connect_error){
    echo "データベース接続エラー\n";
    echo $db->connect_error;
    exit();
    }else{
        $db->set_charset("utf8mb4");
    }

    //insert前に存在していないことを確認
    $sql = "SELECT path FROM dirs_all WHERE path = '".$path."'";
    if ($result = $db->query($sql)) {
        if($result->num_rows > 0){
            echo "エラー：既に追加されています<br>";
            $db->close();
            continue;
        }
        // 結果セットを閉じる
        $result->close();
    }else{
        echo "データベース検索エラー<br>\n";
        exit();
    }

    //$sql = "INSERT INTO tags_all (tag) VALUES ('".$add_tag."')";
    $res = $db->query($sql);
    if($res == false){
        echo "データベース追加に失敗しました<br>\n";
    }
    else{
        echo "データベースにディレクトリ　".$path."　を正常に追加できました<br>\n";
    }

    $db->close();
}

?>
<a href="javascript:history.back()">[戻る]</a>
</body>
</heml>