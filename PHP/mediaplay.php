<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>
メディア再生ページ
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
chdir(ROOT); //ディレクトリの場所の初期化
$name = basename(realpath($path));
echo"<h1>「{$name}」の再生画面</h1><br>\n";
$link = substr(realpath($path),strlen(ROOT));
echo "<a href = \"/{$link}\" >直接表示(/{$link})</a><br><br>\n";

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
$link = substr(realpath($path),strlen(ROOT));
if(is_video($path)){
    echo "<video src=\"/".rawurlencode($link)."\" controls><p>このビデオはこのブラウザでは再生できません</p></video><br>\n";
    echo "<br><a href = \"./hls-play.php?path=".rawurlencode($link)."\"> HLS(ストリーミング)再生</a><br>\n";
}
if(is_audio($path)){
    echo "<audio src=\"/".rawurlencode($link)."\" controls id=\"audio_player\"><p>この音楽はこのブラウザでは再生できません</p></audio><br>\n";
}
?>
<script src="../javascript/player/aurora.js"></script>
<?php
//.m4aはALACとする
$is_load = false;
$js_play = false;
if(preg_match("/.*\.m4a$/i",$path) == 1 ||  preg_match("/.*\.alac$/i",$path) == 1){
    echo "ALACメディアプレイヤー<br>\n";
    echo "<script src=\"/HTTP/javascript/player/alac.js\" id=\"encoder\"></script>";
    $is_load = true;
    $js_play = true;
}
if(preg_match("/.*\.flac$/i",$path) == 1){
    echo "FLACメディアプレイヤー\n";
    echo "<script src=\"/HTTP/javascript/player/flac.js\" id=\"encoder\"></script>";
    $is_load = true;
    $js_play = true;
}

if(preg_match("/.*\.mp4$/i",$path) == 1 ||  preg_match("/.*\.aac$/i",$path) == 1){
    echo "AACタグ情報\n";
    echo "<script src=\"/HTTP/javascript/player/aac.js\" id=\"encoder\"></script>";
    $is_load = true;
}
if(preg_match("/.*\.mp3$/i",$path) == 1){
    echo "MP3タグ情報\n";
    echo "<script src=\"/HTTP/javascript/player/mp3.js\" id=\"encoder\"></script>";    
    $is_load = true;
}
else{
    echo "<script id=\"encoder\"></script>"; 
}

?>
<table id="musicinfo" hidden>
<tr><td rowspan="4" class="longcel"><img src="../img/player/fallback_album_art.png" id="album_cover"></td><td class="label">曲名</td><td id="music_title">Can't Read</td></tr>
<tr><td class="label">アーティスト</td><td id="music_artist">Can't Read</td></tr>
<tr><td class="label">アルバム</td><td id="album_title">Can't Read</td></tr>
<tr><td class="label">アルバムアーティスト</td><td id="album_artist">Can't Read</td></tr>
</table>

<div id="volume_div" hidden>
音量：　　<input type="range" id="volume" min="0" max="100" step="1" value="100">
</div><div id="nowtime_div" hidden>
再生位置：<input type="range" id="nowtime" min="0" max="1000" step="1" value="0" disabled>
<p id="time"></p>
<input type="button" id="playpause" value="再生" hidden>
<input type="button" id="stop" value="停止" hidden>
</div>
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

echo "<a href = \"/".dirname($path)."\" >親ディレクトリを表示(/".dirname($path).")</a><br>\n";
echo "<a href = \"./imageshow.php?path=".rawurlencode(dirname($path))."\"> 親ディレクトリへ（画像表示）</a><br>\n";
echo "<a href = \"./allshow.php?path=".rawurlencode(dirname($path))."\"> 親ディレクトリへ（サブディレクトリ含め全部表示）</a><br>\n";
echo "<a href = \"./covershow.php?path=".rawurlencode(dirname($path))."\"> 親ディレクトリへ（代表画像表示、メディア表示なし）</a><br>\n";

?>
</div>

<script>
<?php

//文字列変換
$link = mb_convert_encoding($link,"UTF-8");
$replace = [
    // '置換前の文字' => '置換後の文字',
    '\\' => '\\\\',
    "'" => "\\'",
    '"' => '\\"',
];
$link_url = str_replace(array_keys($replace), array_values($replace), $link);
echo "var media_link = \"/".$link_url."\";\n";
echo "var play_mode = \"".$mode."\";\n";
echo "var player_mode = \"nomal\";\n";
if($is_load == true){
    echo "player_mode = \"loadable\";\n";
    if($js_play == true){
        echo "player_mode = \"playable\";\n";
    }
}
    readfile("HTTP/javascript/player.js");
?>
</script>
</body>
</html>