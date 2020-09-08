<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
</head>
<body>
<?php
//このファイルが存在している階層以下のメディアファイルを全て追加する
chdir(__DIR__);
mb_internal_encoding("UTF-8");
$list = scandir(__DIR__);
//隠しファイルの削除
$list = preg_grep('/^\..*/',$list,PREG_GREP_INVERT);
natsort($list);
$list = array_values($list);
foreach($list as $path){
    $path = realpath($path);
    if(is_dir($path) == TRUE){
        continue;  //ディレクトリは無視
    }
    if(is_audio($path) || is_video($path)){
        if(grep_match('/^\..*/',basename($path)) == 0){
            //隠しファイルでないことを簡単に確認
            if(add_dir_tag($path,"全て")==true){
                echo $path."を追加しました<br>\n";
            }else{
                echo $path."の追加に失敗しました<br>\n";
            }
        }
    }
}
echo "done.";
//サブディレクトリ含めて全取得
function list_files($dir){
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(
            $dir,
            FilesystemIterator::SKIP_DOTS
            |FilesystemIterator::KEY_AS_PATHNAME
            |FilesystemIterator::CURRENT_AS_FILEINFO
        ), RecursiveIteratorIterator::LEAVES_ONLY
    );
 
    $list = array();
    foreach($iterator as $pathname => $info){
        $list[] = substr($pathname,strlen($dir)+1);
    }
    return $list;
}

function add_dir_tag(String $path,String $tag){
    mb_internal_encoding("UTF-8");

    //データベース接続
    $db = new mysqli("localhost","php","php","php_dir_tag1");
    if($db->connect_error){
        echo "データベース接続エラー<br>\n";
        echo $db->connect_error;
        exit();
    }else{
        $db->set_charset("utf8mb4");
    }

    //文字列変換
    $path = mb_convert_encoding($path,"UTF-8");
    $replace = [
        // '置換前の文字' => '置換後の文字',
        '\\' => '\\\\',
        "'" => "\\'",
        '"' => '\\"',
    ];
    $path = str_replace(array_keys($replace), array_values($replace), $path);

    //tag文字列変換
    $tag = mb_convert_encoding($tag,"UTF-8");
    $tag = str_replace(array_keys($replace), array_values($replace), $tag);

    //ディレクトリのIDを検索
    $sql = "SELECT dir_id FROM dirs_all WHERE path = '".$path."'";
    if ($result = $db->query($sql)) {
        if($result->num_rows == 0){
            //結果セットの破棄
            $result->close();
            $result = null;
            //ディレクトリがDBに登録されていない場合は登録
            $sql = "INSERT INTO dirs_all (path) VALUES ('".$path."')";
            $res = $db->query($sql);
            if($res == false){
                echo "エラー：新規ディレクトリのデータベース追加に失敗しました<br>\n";
                exit();
            }
            else{
                //追加の成功
                $sql = "SELECT dir_id FROM dirs_all WHERE path = '".$path."'";
                if ($result = $db->query($sql)) {
                    //追加した後にもう一度クエリ実行
                }else{
                    echo "新規ディレクトリ追加後のデータベース検索エラー<br>\n";
                    exit();
                }
            }
        }
        while ($row = $result->fetch_assoc()) {
            $dir_id = $row["dir_id"];
        }
        // 結果セットを閉じる
        $result->close();
        $result = null;
    }else{
        echo "データベース検索エラー　ディレクトリ<br>\n";
        exit();
    }

    //tagのIDを検索
    $sql = "SELECT tag_id FROM tags_all WHERE tag = '".$tag."'";
    if ($result = $db->query($sql)) {
        if($result->num_rows == 0){
            //結果セットの破棄
            $result->close();
            $result = null;
            //tagがDBに登録されていない場合は登録
            $sql = "INSERT INTO tags_all (tag) VALUES ('".$tag."')";
            $res = $db->query($sql);
            if($res == false){
                echo "エラー：新規タグのデータベース追加に失敗しました<br>\n";
                exit();
            }
            else{
                //追加の成功
                $sql = "SELECT tag_id FROM tags_all WHERE tag = '".$tag."'";
                if ($result = $db->query($sql)) {
                    //追加した後にもう一度クエリ実行
                }else{
                    echo "新規タグ追加後のデータベース検索エラー<br>\n";
                    exit();
                }
            }
        }
        while ($row = $result->fetch_assoc()) {
            $tag_id = $row["tag_id"];
        }
        // 結果セットを閉じる
        $result->close();
        $result = null;
    }else{
        echo "データベース検索エラー　タグ<br>\n";
        exit();
    }

    //追加前にその関係性が存在しているかを確認
    $sql = "SELECT id FROM dir_tag WHERE dir_id = '".$dir_id."' AND tag_id = '".$tag_id."'";
    if ($result = $db->query($sql)) {
        if($result->num_rows > 0){
            $result->close();
            $result = null;
            $db->close();
            return false;
        }
    }
    $result->close();

    $sql = "INSERT INTO dir_tag (dir_id,tag_id) VALUES ('".$dir_id."','".$tag_id."')";
    $res = $db->query($sql);
    if($res == false){
        echo "エラー：新規ディレクトリタグ関係データベース追加に失敗しました<br>\n";
        exit();
    }else{
        $db->close();
        return true;
    }
}
?>
<a href="javascript:history.back()">[戻る]</a>
</body>
</heml>