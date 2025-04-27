<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>
メディア再生ページ(HLS)
</title>
<link rel="stylesheet" type="text/css" href="../CSS/mediaplay.css">
<!-- jQuery -->
<script type="text/javascript" src="../jquery-3.5.0.js"></script>
<script type="text/javascript" src="../javascript/tagcont.js"></script>
</head>
<body>
<div class ="header">
<?php

include 'root_dir.php';
setlocale(LC_ALL, 'ja_JP.UTF-8');
include 'db-func.php';
include 'file-func.php';

//変換したHLSを格納する場所。このディレクトリは必ず存在するように事前に作成しておく。
//最後のスラッシュは記載しない。ROOTから見た相対パス(?)で記載すること。
define("HLS_SAVE_PATH",".folder/Video/HLS");

if(isset($_GET['path'])){
    $path = $_GET['path'];
}else{
    //ファイル指定は必須
    echo "ファイルが指定されていません\n";
    exit();
}
if(isset($_GET['playmode'])){
    $mode = $_GET['playmode'];
}else{
    $mode = "nomal"; //設定されていない場合はノーマルに
}
if(isset($_GET['forceencode'])){
    $forceEncode = true;
}else{
    $forceEncode = false;
}
chdir(ROOT); //ディレクトリの場所の初期化
$name = basename(realpath($path));
echo"<h1>「{$name}」の再生画面(HLS)</h1><br>\n";
$link = substr(realpath($path),strlen(ROOT));
echo "<a href = \"/{$link}\" >直接表示(/{$link})</a><br><br>\n";
echo "<a href = \"./mediaplay.php?path=".rawurlencode(($link))."\"> 通常のメディア再生ページへ</a><br><br>\n";

echo "<a href = \"/".dirname($link)."\" >親ディレクトリを表示(/".dirname($link).")</a><br>\n";
echo "<a href = \"./imageshow.php?path=".rawurlencode(dirname($link))."\"> 親ディレクトリへ（画像表示）</a><br>\n";
echo "<a href = \"./allshow.php?path=".rawurlencode(dirname($link))."\"> 親ディレクトリへ（サブディレクトリ含め全部表示）</a><br>\n";
echo "<a href = \"./covershow.php?path=".rawurlencode(dirname($link))."\"> 親ディレクトリへ（代表画像表示、メディア表示なし）</a><br>\n";

?>
</div>
<div class="mediaplayer">
<?php

//タグ表示
echo "<div class=\"tags\">\n<div class=\"tagshow\">\n";
$tags = dir_tag_list(realpath($path));
foreach($tags as $tag){
    echo "<a href=\"./taggedlist.php?tag[]=".rawurlencode($tag)."\"> ".$tag." </a>　";
}
echo "</div class=\"tagshow\">\n";
?>
<p id="info_text1" hidden>説明文</p>
<select id="rm_tag1" hidden>
<?php
foreach($tags as $tag){
    echo "<option value=\"".$tag."\"> ".$tag." </option>\n";
}
?>
</select>
<select id="add_tag_list1" hidden>
<?php
$addlist = array_diff(all_tag_list(),$tags);
foreach($addlist as $tag){
    echo "<option value=\"".$tag."\"> ".$tag." </option>\n";
}
?>
<option value="自分で入力(新規追加)">自分で入力(新規追加)</option>
</select>
<input id="add_tag_text1" type="text" name="add_txt_tag" hidden><br>
<input id="add_tag_btn1" type="button" value="タグ追加" />
<input id="rm_tag_btn1" type="button" value="タグ削除" />
<input id="rm_all_btn1" type="button" value="タグ全削除（DBから消す）" />
<input id="enter_btn1" type="button" value="決定" hidden>
<input id="cancel_btn1" type="button" value="キャンセル" hidden>
<div id="path1" hidden><?php
echo realpath($path);
?></div id ="path1">
</div class="tags">
<br>
<?php
//ここから変換処理
$dir_id = get_dir_id(realpath($path));
if ($dir_id < 0){
    echo "<Error>Don't have Dir ID.<br>\n";
    exit();
}

chdir(ROOT);
$hls_file = HLS_SAVE_PATH."\\".$dir_id."\\".$dir_id.".m3u8" ;
//$hls_file = ".folder/Video/tmp/hogehoge/output.m3u8";

if($forceEncode == true){
    //HLS変換格納フォルダを削除して、再読み込み
    remove_directory(dirname($hls_file));
    echo "<script>$(function() {setTimeout(function(){window.location.href = './hls-play.php?path=".rawurlencode($link)."';}, 1000);});</script>\n";
    echo "<p>１秒後に再読み込みします</p>\n";
    echo "<a href = \"./hls-play.php?path=".rawurlencode($link)."\"> 自動遷移しない場合はここをクリック</a>\n";
    exit();
}

// 再帰的にディレクトリを削除する関数
function remove_directory($dir) {
    $files = array_diff(scandir($dir), array('.','..'));
    foreach ($files as $file) {
        // ファイルかディレクトリによって処理を分ける
        if (is_dir("$dir\\$file")) {
            // ディレクトリなら再度同じ関数を呼び出す
            remove_directory("$dir\\$file");
        } else {
            // ファイルなら削除
            unlink("$dir\\$file");
        }
    }
    // 指定したディレクトリを削除
    return rmdir($dir);
}

if(file_exists($hls_file) == false){

    //保存用フォルダができていれば、すでに処理中と判断。
    //フォルダができていない場合のみ変換処理開始
    if(file_exists(dirname($hls_file)) == false){
        if (file_exists(dirname($hls_file)) == false){
            mkdir(dirname($hls_file));
        }
        $file_full_path = realpath($path);
        $hls_full_path = realpath(dirname($hls_file));

        $cmd = "ffmpeg -i \"".$file_full_path."\" -c copy -f hls -hls_list_size 0 ".$hls_full_path."\\".basename($hls_file);
        //Windows用に文字コードを変換
        $cmd = mb_convert_encoding($cmd, "sjis-win");
        //非同期実行(Windowsのみ対応)
        $WshShell = new COM("WScript.Shell");
        $WshShell->Run($cmd,0,false);
        echo "<p>変換処理を開始しました。15秒後自動リロードします</p>\n";
    }
    else {
        echo "<p>変換処理実施中です。15秒後自動リロードします</p>\n";
        echo "<br><a href = \"./hls-play.php?path=".rawurlencode($link)."&forceencode=1\"> 強制再変換実施の場合はここをクリック</a><br>\n";
    }
    echo "<script>$(function(){setTimeout(() => {location.reload();}, 15000);});</script>\n";
}
else {

    $hls_file_url = str_replace("\\","/",$hls_file);

    //$hls_file_urlをurlエンコードすると、映像ファイルが読まれない（スラッシュまでもエンコードされるのが原因？）
    //なので、$hls_file_urlには日本語とかが絶対入らないようにする。
    echo "<video src=\"/".$hls_file_url."\" controls><p>このビデオはこのブラウザでは再生できません</p></video><br>\n";
    echo "<br><a href = \"./hls-play.php?path=".rawurlencode($link)."&forceencode=1\"> HLS再変換実施の場合はここをクリック</a><br>\n";

}

chdir(dirname(__FILE__));
?>

<!--
    再生モード及び
    次の曲、プレイリスト編集画面へのリンク

    -->
<div class="modefinfo">
<p id="modeinfotext" hidden></p>
<input type="checkbox" value="autoplay" id="is_auto">自動再生(ページ推移は行わない)　&emsp;
<input type="button" value="次の曲へ" id="next_btn">&emsp;
<?php
echo "<a href=\"./mediaplay.php?playmode=".$mode."&path=".rawurlencode($path)."\" id=\"to_nowsong\">現在再生中の曲のメディアプレイ画面へ</a><br>\n";
?>
&nbsp;<input type="radio" name="mode" value="all_shuffle" checked>登録音楽全てをシャッフルプレイ<br>
&nbsp;<input type="radio" name="mode" value="list_nomal">プレイリストを順に再生<br>
&nbsp;<input type="radio" name="mode" value="list_shuffle">プレイリストをランダムに再生<br>
<input type="button" id="list_add" value="プレイリストに現在の曲を追加">&emsp;
<a href="./playlist_conf.php" target="_blank">プレイリスト確認・編集画面へ</a><br>
</div>

<p><a href="#" onClick="history.back(); return false;">前のページにもどる</a></p>
<div class="footer">
<?php
echo"<p>ファイル名「{$name}」</p>\n";

chdir(ROOT);
//タグ表示
echo "<div class=\"tags\">\n<div class=\"tagshow\">\n";
$tags = dir_tag_list(realpath($path));
foreach($tags as $tag){
    echo "<a href=\"./taggedlist.php?tag[]=".rawurlencode($tag)."\"> ".$tag." </a>　";
}
echo "</div class=\"tagshow\">\n";
?>
<p id="info_text2" hidden>説明文</p>
<select id="rm_tag2" hidden>
<?php
foreach($tags as $tag){
    echo "<option value=\"".$tag."\"> ".$tag." </option>\n";
}
?>
</select>
<select id="add_tag_list2" hidden>
<?php
$addlist = array_diff(all_tag_list(),$tags);
foreach($addlist as $tag){
    echo "<option value=\"".$tag."\"> ".$tag." </option>\n";
}
?>
<option value="自分で入力(新規追加)">自分で入力(新規追加)</option>
</select>
<input id="add_tag_text2" type="text" name="add_txt_tag" hidden><br>
<input id="add_tag_btn2" type="button" value="タグ追加" />
<input id="rm_tag_btn2" type="button" value="タグ削除" />
<input id="rm_all_btn2" type="button" value="タグ全削除（DBから消す）" />
<input id="enter_btn2" type="button" value="決定" hidden>
<input id="cancel_btn2" type="button" value="キャンセル" hidden>
<div id="path2" hidden><?php
echo realpath($path);?>
</div id ="path2">
</div class="tags">

<?php
echo "<a href = \"/{$link}\" >直接表示(/{$link})</a><br><br>\n";
echo "<a href = \"./mediaplay.php?path=".rawurlencode(($link))."\"> 通常のメディア再生ページへ</a><br><br>\n";

echo "<a href = \"/".dirname($path)."\" >親ディレクトリを表示(/".dirname($path).")</a><br>\n";
echo "<a href = \"./imageshow.php?path=".rawurlencode(dirname($path))."\"> 親ディレクトリへ（画像表示）</a><br>\n";
echo "<a href = \"./allshow.php?path=".rawurlencode(dirname($path))."\"> 親ディレクトリへ（サブディレクトリ含め全部表示）</a><br>\n";
echo "<a href = \"./covershow.php?path=".rawurlencode(dirname($path))."\"> 親ディレクトリへ（代表画像表示、メディア表示なし）</a><br>\n";

?>
</div>

</body>
</html>