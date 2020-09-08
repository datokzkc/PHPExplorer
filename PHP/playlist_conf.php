<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>
プレイリスト確認・編集画面
</title>
<link rel="stylesheet" type="text/css" href="/HTTP/CSS/taglist.css">
<!-- jQuery -->
<script type="text/javascript" src="/HTTP/jquery-3.5.0.js"></script>
<script type="text/javascript" src="/HTTP/javascript/totop.js"></script>
</head>

<body>
<div class ="header">
<?php
include 'root_dir.php';
include 'db-func.php';
include 'file-func.php';

chdir(ROOT); //ディレクトリの場所の初期化

echo"<h1>現在のプレイリスト</h1>\n";

?>
</div>
<div class ="covershow">
<?php
/*
$path = getcwd();
*/
set_error_handler(function($severity, $message) {
    throw new ErrorException($message);
});

try{
    $fp = fopen("/HTTP/data/playlist/nowplay.txt","rb");
    $track_no = (int)substr(fgets($fp), 1);
    $music_list = fgetcsv($fp);
    fclose($fp);
    if($music_list[0]== null){
        throw new ErrorException('曲なし');
    }

}catch (Exception $e){
    //ファイルオープンエラー
    $track_no = -1; //nomal
    $music_list = array();
}finally{
    restore_error_handler();
}

//タグ表示
echo "<div class=\"include_list\">\n";
?>
<p id="include_info_text" hidden></p>
<select id="include_filename" hidden>
<?php
chdir(ROOT);
$filelist = scandir("./HTTP/data/playlist");
//隠しファイルの削除
$filelist = preg_grep('/^\..*/',$filelist,PREG_GREP_INVERT);
natsort($filelist);
$filelist = array_values($filelist);
foreach($filelist as $file){
    if($file != "nowplay.txt"){
        echo "<option value=\"".$file."\"> ".$file." </option>\n";
    }
}
?>
</select><br>
<input id="include_run_btn" type="button" value="ファイルからプレイリストを読み込む" />
<input id="include_enter_btn" type="button" value="決定" hidden>
<input id="include_cancel_btn" class="cancel" type="button" value="キャンセル" hidden>
</div>
<?php

chdir(ROOT); // ディレクトリ移動

echo "<h2>合計：".count($music_list)."曲</h2>\n";

echo "<p><b>リピート: ";
if($track_no != -1){
    echo "ON</b>&emsp;";
    echo "<input id=\"repeat_btn\" type=\"button\" value=\"OFFにする\" /></p>\n";
}else{
    echo "OFF</b>&emsp;";
    echo "<input id=\"repeat_btn\" type=\"button\" value=\"ONにする\" /></p>\n";
}

// //並び替え選択
// if($is_shuffle == 0){
//     echo "<a href=\"./".basename(__FILE__)."?shuffle="."1"."&mode=".$mode."\">"."シャッフルする</a><br>\n";
// }else{
//     echo "<a href=\"./".basename(__FILE__)."?shuffle="."0"."&mode=".$mode."\">"."通常の並びへ戻す</a><br>\n";
// }


//一覧リスト生成
echo "<table>\n";
if(count($music_list) == 0){
    echo "<tr><td>Empty.</td></tr>\n";
}else{
    foreach($music_list as $key => $media){
        echo "<tr><td>";
        if($key == $track_no){
            $track = $key+1;
            echo $track."曲目（Now Playing）</td>";
        }else{
            $track = $key+1;
            echo $track."曲目</td>";
        }
        echo "<td><a href=\"./mediaplay.php?path=".rawurlencode($media)."\">".$media."</a></td>";
        echo "<td><button type=\"button\" id=\"rm_btn_".$key."\">削除</button></td>";
        echo "</tr>\n";
    }
}
echo "</table><br>\n";


?>
</div>
<div class="save_list">
<p id="save_info_text"></p>
<input id="save_name" type="text" name="save_name" hidden><span id="save_ext" hidden>.txt</span><br>
<input id="save_run_btn" type="button" value="現在のプレイリストを名前を付けて保存" />
<input id="save_enter_btn" type="button" value="決定" hidden>
<input id="save_cancel_btn" class="cancel" type="button" value="キャンセル" hidden>
</div>
<div class="add_all_tag">
<p id="add_tag_info_text"></p>
<select id="add_tag_list" hidden>
<?php
$addlist = all_tag_list();
foreach($addlist as $tag){
    echo "<option value=\"".$tag."\"> ".$tag." </option>\n";
}
?>
<option value="自分で入力(新規追加)">自分で入力(新規追加)</option>
</select>
<input id="add_tag_text" type="text" name="add_txt_tag" hidden><br>
<input id="add_tag_enter_btn" type="button" value="決定" hidden>
<input id="add_tag_cancel_btn" class="cancel" type="button" value="キャンセル" hidden>
<input id="add_tag_btn" type="button" value="現在のプレイリスト内の曲全てにタグを登録" /><br>
<p><a href="#" onClick="window.close(); return false;">現在のタブを閉じる</a></p>
<div class="footer">
<?php
chdir(ROOT);
echo"<p>プレイリスト確認・編集画面</p><br>\n";
?>
</div>
<div id="totop"><a href="#"></a></div>
<script type="text/javascript">
$(document).ready(function (){

    var $save_btn = $('#save_run_btn');
    var $save_name = $('#save_name');
    var $save_ext = $('#save_ext');
    var $save_enter = $('#save_enter_btn');
    var $save_info = $('#save_info_text');
    var $include_btn = $('#include_run_btn');
    var $include_name = $('#include_filename');
    var $include_enter = $('#include_enter_btn');
    var $include_info = $('#include_info_text');
    var $add_btn = $('#add_tag_btn');
    var $add_list = $('#add_tag_list');
    var $add_text = $('#add_tag_text');
    var $add_enter = $('#add_tag_enter_btn');
    var $add_info = $('#add_tag_info_text');
    var $cancel = $('.cancel');

    $save_btn.click(function(){
        $(".save_list").show();
        $save_info.text("保存するファイル名を入力してください\n");
        $save_info.show();
        $save_name.val("");
        $save_name.show();
        $save_ext.show();
        $save_enter.show();
        $cancel.show();
        $save_btn.hide();
        $include_btn.hide();
        $include_name.hide();
        $include_enter.hide();
        $include_info.hide();
        $add_btn.hide();
        $add_list.hide();
        $add_text.hide();
        $add_enter.hide();
        $add_info.hide();
    });

    $include_btn.click(function(){
        $(".include_list").show();
        $include_info.text("取り込むファイルを選択してください\n");
        $include_info.show();
        $include_name.show();
        $include_enter.show();
        $cancel.show();
        $include_btn.hide();
        $save_btn.hide();
        $save_name.hide();
        $save_ext.hide();
        $save_enter.hide();
        $save_info.hide();
        $add_btn.hide();
        $add_list.hide();
        $add_text.hide();
        $add_enter.hide();
        $add_info.hide();
    });

    $add_btn.click(function(){
        $('.add_all_tag').show();
        $add_info.text("追加するタグを選択してください\n");
        $add_info.show();
        $add_list.show();
        $add_enter.show();
        $cancel.show();
        $add_btn.hide();
        if ($add_list.val() == "自分で入力(新規追加)") {
            $add_text.prop("disabled", false);
            $add_text.show();
        } else {
            $add_text.prop("disabled", true);
            $add_text.hide();
        }
        $save_btn.hide();
        $save_name.hide();
        $save_ext.hide();
        $save_enter.hide();
        $save_info.hide();
        $include_btn.hide();
        $include_name.hide();
        $include_enter.hide();
        $include_info.hide();
    });

    $add_list.change(function () {
        if ($add_list.val() == "自分で入力(新規追加)") {
            $add_text.prop("disabled", false);
            $add_text.show();
        }
        else {
            $add_text.prop("disabled", true);
            $add_text.hide();
        }
    });

    $cancel.click(function(){
        $save_btn.show();
        $include_btn.show();
        $save_name.hide();
        $save_ext.hide();
        $save_enter.hide();
        $save_info.hide();
        $include_name.hide();
        $include_enter.hide();
        $include_info.hide();
        $cancel.hide();
        $add_btn.show();
        $add_list.hide();
        $add_text.hide();
        $add_enter.hide();
        $add_info.hide();
    })

    $save_enter.click(function(){
        var filename = $save_name.val()+".txt";
        if (filename == "nowplay.txt"){
                alert("その名前では作成できません");
                return;
        }
        if (filename == ".txt"){
                alert("何か入力してください");
                return;
        }
        $.ajax({
            url: './ajax.php',
            type: 'POST',
            data: {
                'mode': "list_save",
                'filename': filename
            }
        })
            // Ajaxリクエストが成功した時発動
            .done((data) => {
                console.log(data);
                alert(data);
                location.reload();
            })
            // Ajaxリクエストが失敗した時発動
            .fail((data) => {
                alert("Ajaxエラーが発生しました。");
                console.log(data);
            })
            // Ajaxリクエストが成功・失敗どちらでも発動
            .always((data) => {

            });
    })

    $include_enter.click(function(){
        var filename = $include_name.val();
        $.ajax({
            url: './ajax.php',
            type: 'POST',
            data: {
                'mode': "list_include",
                'filename': filename
            }
        })
            // Ajaxリクエストが成功した時発動
            .done((data) => {
                console.log(data);
                alert(data);
                location.reload();
            })
            // Ajaxリクエストが失敗した時発動
            .fail((data) => {
                alert("Ajaxエラーが発生しました。");
                console.log(data);
            })
            // Ajaxリクエストが成功・失敗どちらでも発動
            .always((data) => {

            });
    })

    $("#repeat_btn").click(function(){
        var filename = $include_name.val();
        $.ajax({
            url: './ajax.php',
            type: 'POST',
            data: {
                'mode': "list_repeat",
            }
        })
            // Ajaxリクエストが成功した時発動
            .done((data) => {
                console.log(data);
                alert(data);
                location.reload();
            })
            // Ajaxリクエストが失敗した時発動
            .fail((data) => {
                alert("Ajaxエラーが発生しました。");
                console.log(data);
            })
            // Ajaxリクエストが成功・失敗どちらでも発動
            .always((data) => {

            });
    })

    $add_enter.click(function(){
        var tag = $add_list.val();
        if (tag == "自分で入力(新規追加)") {
            tag = $add_text.val();
            if (tag == "自分で入力(新規追加)"){
                alert("そのタグは追加できません");
                return;
            }
        }

        $.ajax({
            url: './ajax.php',
            type: 'POST',
            data: {
                'mode': "list_add_db",
                'tag' : tag
            }
        })
            // Ajaxリクエストが成功した時発動
            .done((data) => {
                console.log(data);
                alert(data);
                location.reload();
            })
            // Ajaxリクエストが失敗した時発動
            .fail((data) => {
                alert("Ajaxエラーが発生しました。");
                console.log(data);
            })
            // Ajaxリクエストが成功・失敗どちらでも発動
            .always((data) => {

            });
    })

<?php
if(count($music_list) == 0){
    //nothing do
}else{
    foreach($music_list as $key => $media){
        echo "$('#rm_btn_".$key."').click(function (){\n";
        echo "$.ajax({\n";
        echo "url: './ajax.php',type:'POST',data:{'mode':'list_rm_trc','track':'".$key."'}\n";
        echo "})\n";
        echo "//ajax成功時\n";
        echo ".done((data) => {\n";
        echo "console.log(data);\nalert(data);\nlocation.reload();\n";
        echo "})\n";
        echo "//ajax失敗時\n";
        print ".fail((data) => {\n";
        echo "alert('Ajax通信エラー');\n";
        echo "});\n});\n";
    }
}
?>
});
</script>

</body>
</html>