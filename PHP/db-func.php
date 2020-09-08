<?php

define("WRITE_DBIP","192.168.1.123");
define("READ_DBIP","localhost");
define("PI_ROOT","/misc/FAT2/");
define("THIS_ROOT","D:\\");
mb_internal_encoding("UTF-8");

//データベース接続
// $db = new mysqli(WRITE_DBIP,"php","php","php_dir_tag");
// if($db->connect_error){
// echo "データベース接続エラー<br>\n";
// echo $db->connect_error;
// exit();
// }else{
//     $db->set_charset("utf8mb4");
// }


// $sql = "UPDATE dirs_all SET path=REPLACE(path,\"/misc/FAT2/\",\"D:\\\\\")";
// $res = $db->query($sql);
// if($res == false){
//     echo "エラー：ルートの置換に失敗しました。<br>\n";
//     exit();
// }
// $sql = "UPDATE dirs_all SET path=REPLACE(path,\"/\",\"\\\\\")";
// $res = $db->query($sql);
// if($res == false){
//     echo "エラー：スラッシュ置換に失敗しました。<br>\n";
//     exit();
// }
// $db->close();

/*
関数一覧
tagged_dir_list ($tag) 
    引数で指定したタグがついているディレクトリパスのリストを返す
    ※引数を指定しない場合は「全て」タグのついているものを返す
    ディレクトリが存在しないときはfalseを返す。
    存在しないタグを引数に取るとfalseとecho
all_dir_list()
    dirs_allに登録されているパスをすべてリストとして返す
    まったく存在していないときはfalseとecho
dir_tag_list($path)
    指定したディレクトリに設定されているタグ一覧を返す
    ディレクトリが登録されていないorタグが付いていない場合は空配列を返却
all_tag_list()
    tags_allに登録されているタグ一覧をすべてリストとして返す。
    １つも存在しない場合はfalseとecho

make_tag($tag)
    引数で指定したtagをtags_allに追加
    すでに追加されている時はechoとfalse
    成功したらtrue
add_dir_tag($path,$tag)
    ディレクトリとタグを関連付け。
    それぞれ登録されていない場合は新規登録もする。
    追加済みの場合はfalse
    成功したらtrue

rm_dir_tag($path,$tag) 
    ディレクトリとタグの関連性を削除
    登録していない場合はfalseとecho
    成功したらtrue
rm_tag($tag)
    タグを削除。それを使っている関係も同時に削除。
    登録していない場合はfalseとecho
    成功したらtrue
rm_db_dir($path)
    ディレクトリ情報をデータベースから削除
    登録していない場合はfalseとehco
    成功したらtrue

*/
function tagged_dir_list(String $srctag = "全て"){
    //引数設定されていない場合は"全て"タグで検索
    mb_internal_encoding("UTF-8");

    //データベース接続
    $db = new mysqli(READ_DBIP,"php","php","php_dir_tag");
    if($db->connect_error){
    echo "データベース接続エラー<br>\n";
    echo $db->connect_error;
    exit();
    }else{
        $db->set_charset("utf8mb4");
    }

    //文字列変換
    $srctag = mb_convert_encoding($srctag,"UTF-8");
    $replace = [
        // '置換前の文字' => '置換後の文字',
        '\\' => '\\\\',
        "'" => "\\'",
        '"' => '\\"',
    ];
    $srctag = str_replace(array_keys($replace), array_values($replace), $srctag);

    //tagのIDを検索
    $sql = "SELECT tag_id FROM tags_all WHERE tag = '".$srctag."'";
    if ($result = $db->query($sql)) {
        if($result->num_rows == 0){
            echo "エラー：".$srctag."はタグとして登録されていません<br>\n";
            $db->close();
            return false;
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

    //tag_idが一致するパスをすべて取得
    $sql = "SELECT dirs_all.path FROM dir_tag JOIN dirs_all USING (dir_id) JOIN tags_all USING (tag_id) WHERE tags_all.tag_id=".$tag_id;
    if ($result = $db->query($sql)) {
        if($result->num_rows == 0){
            //echo "エラー：".$srctag."タグに登録されているものはありません<br>";
            $db->close();
            return false;
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

    //ディレクトリパス変換
    $list = str_replace(PI_ROOT,THIS_ROOT,$list);
    $list = str_replace("/","\\",$list);

    return $list;
}

function all_dir_list(){
    mb_internal_encoding("UTF-8");

    //データベース接続
    $db = new mysqli(READ_DBIP,"php","php","php_dir_tag");
    if($db->connect_error){
    echo "データベース接続エラー<br>\n";
    echo $db->connect_error;
    exit();
    }else{
        $db->set_charset("utf8mb4");
    }

    //dirs_allから全てのpathを取得
    $sql = "SELECT path FROM dirs_all";
    if ($result = $db->query($sql)) {
        if($result->num_rows == 0){
            echo "エラー：登録ディレクトリが１つも存在しません<br>\n";
            $db->close();
            return false;
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

    //ディレクトリパス変換
    $list = str_replace(PI_ROOT,THIS_ROOT,$list);
    $list = str_replace("/","\\",$list);

    return $list;
}

function dir_tag_list(String $dir_path){
    //ディレクトリに登録されているpathをリストで返却
    mb_internal_encoding("UTF-8");

    //データベース接続
    $db = new mysqli(READ_DBIP,"php","php","php_dir_tag");
    if($db->connect_error){
        echo "データベース接続エラー<br>\n";
        echo $db->connect_error;
        exit();
    }else{
        $db->set_charset("utf8mb4");
    }

    //ディレクトリパス変換
    $dir_path = str_replace(THIS_ROOT,PI_ROOT,$dir_path);
    $dir_path = str_replace("\\","/",$dir_path);

    //文字列変換
    $dir_path = mb_convert_encoding($dir_path,"UTF-8");
    $replace = [
        // '置換前の文字' => '置換後の文字',
        '\\' => '\\\\',
        "'" => "\\'",
        '"' => '\\"',
    ];
    $dir_path = str_replace(array_keys($replace), array_values($replace), $dir_path);

    //ディレクトリのIDを検索
    $sql = "SELECT dir_id FROM dirs_all WHERE path = '".$dir_path."'";
    if ($result = $db->query($sql)) {
        if($result->num_rows == 0){
            //ディレクトリがDBに登録されていない場合は空配列を返す
            $db->close();
            return array();
        }
        while ($row = $result->fetch_assoc()) {
            $dir_id = $row["dir_id"];
        }
        // 結果セットを閉じる
        $result->close();
    }else{
        echo "データベース検索エラー<br>\n";
        exit();
    }

    //dir_idが一致するタグをすべて取得
    $sql = "SELECT tags_all.tag FROM dir_tag JOIN dirs_all USING (dir_id) JOIN tags_all USING (tag_id) WHERE dirs_all.dir_id=".$dir_id." ORDER BY tags_all.tag_id";
    if ($result = $db->query($sql)) {
        if($result->num_rows == 0){
            //タグが登録されていない場合は空配列を返す
            $db->close();
            return array();
        }
        while ($row = $result->fetch_assoc()) {
            $list[] = $row["tag"];
        }
        // 結果セットを閉じる
        $result->close();
    }else{
        echo "データベース検索エラー<br>\n";
        exit();
    }

    $db->close();

    return $list;
}

function all_tag_list(){
    mb_internal_encoding("UTF-8");

    //データベース接続
    $db = new mysqli(READ_DBIP,"php","php","php_dir_tag");
    if($db->connect_error){
        echo "データベース接続エラー<br>\n";
        echo $db->connect_error;
        exit();
    }else{
        $db->set_charset("utf8mb4");
    }

    //tags_allから全てのタグを取得
    $sql = "SELECT tag FROM tags_all ORDER BY tag_id";
    if ($result = $db->query($sql)) {
        if($result->num_rows == 0){
            echo "エラー：タグが１つも存在しません<br>\n";
            $db->close();
            return false;
        }
        while ($row = $result->fetch_assoc()) {
            $list[] = $row["tag"];
        }
        // 結果セットを閉じる
        $result->close();
    }else{
        echo "データベース検索エラー<br>\n";
        exit();
    }

    return $list;
}

function make_tag(String $tag){
    mb_internal_encoding("UTF-8");

    //データベース接続
    $db = new mysqli(WRITE_DBIP,"php","php","php_dir_tag");
    if($db->connect_error){
        echo "データベース接続エラー<br>\n";
        echo $db->connect_error;
        exit();
    }else{
        $db->set_charset("utf8mb4");
    }

    //文字列変換
    $tag = mb_convert_encoding($tag,"UTF-8");
    $replace = [
        // '置換前の文字' => '置換後の文字',
        '\\' => '\\\\',
        "'" => "\\'",
        '"' => '\\"',
    ];
    $tag = str_replace(array_keys($replace), array_values($replace), $tag);

    //insert前に存在していないことを確認
    $sql = "SELECT tag FROM tags_all WHERE tag = '".$tag."'";
    if ($result = $db->query($sql)) {
        if($result->num_rows > 0){
            echo "エラー：既にタグ".$tag."は追加されています<br>\n";
            $db->close();
            return false;
        }
        // 結果セットを閉じる
        $result->close();
    }else{
        echo "データベース検索エラー\n";
        exit();
    }

    //タグの追加
    $sql = "INSERT INTO tags_all (tag) VALUES ('".$tag."')";
    $res = $db->query($sql);
    if($res == false){
        echo "データベース追加に失敗しました<br>\n";
        $db->close();
        return false;
    }
    else{
        $db->close();
        return true;
    }

    $db->close();
}

function add_dir_tag(String $path,String $tag){
    mb_internal_encoding("UTF-8");

    //データベース接続
    $db = new mysqli(WRITE_DBIP,"php","php","php_dir_tag");
    if($db->connect_error){
        echo "データベース接続エラー<br>\n";
        echo $db->connect_error;
        exit();
    }else{
        $db->set_charset("utf8mb4");
    }

    //ディレクトリパス変換
    $path = str_replace(THIS_ROOT,PI_ROOT,$path);
    $path = str_replace("\\","/",$path);

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

function rm_dir_tag(String $path,String $tag){
    mb_internal_encoding("UTF-8");

    //データベース接続
    $db = new mysqli(WRITE_DBIP,"php","php","php_dir_tag");
    if($db->connect_error){
        echo "データベース接続エラー<br>\n";
        echo $db->connect_error;
        exit();
    }else{
        $db->set_charset("utf8mb4");
    }

    //ディレクトリパス変換
    $path = str_replace(THIS_ROOT,PI_ROOT,$path);
    $path = str_replace("\\","/",$path);

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
            $db->close();
            echo "指定されたディレクトリはデータベースに存在していません。<br>\n";
            return false;
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
            $db->close();
            echo "指定されたタグはデータベースに存在していません。<br>\n";
            return false;
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

    $sql = "DELETE FROM dir_tag WHERE tag_id = '".$tag_id."' AND dir_id = '".$dir_id."'";
    $res = $db->query($sql);
    if($res == false){
        echo "エラー：新規ディレクトリタグ関係データベースからのデータ削除に失敗しました<br>\n";
        exit();
    }else{
        $db->close();
        return true;
    }
}

function rm_tag(String $tag){
    mb_internal_encoding("UTF-8");

    //データベース接続
    $db = new mysqli(WRITE_DBIP,"php","php","php_dir_tag");
    if($db->connect_error){
        echo "データベース接続エラー\n";
        echo $db->connect_error;
        exit();
    }else{
        $db->set_charset("utf8mb4");
    }

    //文字列変換
    $tag = mb_convert_encoding($tag,"UTF-8");
    $replace = [
        // '置換前の文字' => '置換後の文字',
        '\\' => '\\\\',
        "'" => "\\'",
        '"' => '\\"',
    ];
    $tag = str_replace(array_keys($replace), array_values($replace), $tag);

    //tagのIDを検索
    $sql = "SELECT tag_id FROM tags_all WHERE tag = '".$tag."'";
    if ($result = $db->query($sql)) {
        if($result->num_rows == 0){
            //結果セットの破棄
            $result->close();
            $result = null;
            $db->close();
            echo "指定されたタグはデータベースに存在していません。<br>\n";
            return false;
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

    //先に関係データベースからタグが用いられているものをすべて削除(念のため)
    $sql = "DELETE FROM dir_tag WHERE tag_id = '".$tag_id."'";
    $res = $db->query($sql);
    if($res == false){
        echo "エラー：ディレクトリタグ関係データベースからのデータ削除に失敗しました<br>\n";
        exit();
    }

    //タグの抹消
    $sql = "DELETE FROM tags_all WHERE tag_id = '".$tag_id."'";
    $res = $db->query($sql);
    if($res == false){
        echo "エラー：タグデータベースからのデータ削除に失敗しました<br>\n";
        exit();
    }
    $db->close();
    return true;
}

function rm_db_dir(String $path){
    mb_internal_encoding("UTF-8");

    //データベース接続
    $db = new mysqli(WRITE_DBIP,"php","php","php_dir_tag");
    if($db->connect_error){
        echo "データベース接続エラー<br>\n";
        echo $db->connect_error;
        exit();
    }else{
        $db->set_charset("utf8mb4");
    }

    //ディレクトリパス変換
    $path = str_replace(THIS_ROOT,PI_ROOT,$path);
    $path = str_replace("\\","/",$path);

    //文字列変換
    $path = mb_convert_encoding($path,"UTF-8");
    $replace = [
        // '置換前の文字' => '置換後の文字',
        '\\' => '\\\\',
        "'" => "\\'",
        '"' => '\\"',
    ];
    $path = str_replace(array_keys($replace), array_values($replace), $path);

    //tagのIDを検索
    $sql = "SELECT dir_id FROM dirs_all WHERE path = '".$path."'";
    if ($result = $db->query($sql)) {
        if($result->num_rows == 0){
            //結果セットの破棄
            $result->close();
            $result = null;
            $db->close();
            echo "指定されたディレクトリはデータベースに存在していません。<br>\n";
            return false;
        }
        while ($row = $result->fetch_assoc()) {
            $dir_id = $row["dir_id"];
        }
        // 結果セットを閉じる
        $result->close();
        $result = null;
    }else{
        echo "データベース検索エラー　タグ<br>\n";
        exit();
    }

    //先に関係データベースからディレクトリが用いられているものをすべて削除(念のため)
    $sql = "DELETE FROM dir_tag WHERE dir_id = '".$dir_id."'";
    $res = $db->query($sql);
    if($res == false){
        echo "エラー：ディレクトリタグ関係データベースからのデータ削除に失敗しました<br>\n";
        exit();
    }

    //ディレクトリデータの抹消
    $sql = "DELETE FROM dirs_all WHERE dir_id = '".$dir_id."'";
    $res = $db->query($sql);
    if($res == false){
        echo "エラー：データベースからのディレクトリデータ削除に失敗しました<br>\n";
        exit();
    }
    $db->close();
    return true;
}



?>