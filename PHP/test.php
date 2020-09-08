<html>
<head>
</head>
<body>
<?php
//このファイルが存在しているディレクトリを全て追加する
chdir(__DIR__);
mb_internal_encoding("UTF-8");
$srctag = "全て";

    $db = new mysqli("localhost","php","php","php_dir_tag");
    if($db->connect_error){
    echo "データベース接続エラー\n";
    echo $db->connect_error;
    exit();
    }else{
        $db->set_charset("utf8mb4");
    }

    //insert前に存在していないことを確認
    $sql = "SELECT tag_id FROM tags_all WHERE tag = '".$srctag."'";
    if ($result = $db->query($sql)) {
        if($result->num_rows == 0){
            echo "エラー：".$srctag."はタグとして登録されていません<br>";
            $db->close();
            exit();
        }
        while ($row = $result->fetch_assoc()) {
            $tag_id = $row["tag_id"];
        }
        // 結果セットを閉じる
        $result->close();
    }else{
        echo "データベース検索エラー<br>\n";
        exit();
    }

    //insert前に存在していないことを確認
    $sql = "SELECT dirs_all.path FROM dir_tag JOIN dirs_all USING (dir_id) JOIN tags_all USING (tag_id) WHERE tags_all.tag_id=".$tag_id;
    if ($result = $db->query($sql)) {
        if($result->num_rows == 0){
            echo "エラー：".$srctag."タグに登録されているものはありません<br>";
            $db->close();
            exit();
        }
        while ($row = $result->fetch_assoc()) {
            $list[] = $row["path"];
        }
        // 結果セットを閉じる
        $result->close();
    }else{
        echo "データベース検索エラー<br>\n";
        exit();
    }

    $db->close();
    echo "エラーなし";

?>
<a href="javascript:history.back()">[戻る]</a>
</body>
</heml>