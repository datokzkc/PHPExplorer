<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>
メディア再生ページ(HLS)
</title>
<link rel="stylesheet" type="text/css" href="/HTTP/CSS/mediaplay.css">
<!-- jQuery -->
<script type="text/javascript" src="/HTTP/jquery-3.5.0.js"></script>
<script type="text/javascript" src="/HTTP/javascript/tagcont.js"></script>
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
chdir(ROOT); //ディレクトリの場所の初期化
$name = basename(realpath($path));
echo"<h1>「{$name}」の再生画面</h1><br>\n";
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
    echo "<a href=\"./taggedlist.php?tag=".$tag."\"> ".$tag." </a>　";
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
//ここから変換処理　（何らかの変換処理がいるかも）
$dir_id = get_dir_id(realpath($path));
if ($dir_id < 0){
    echo "<Error>Don't have Dir ID.<br>\n";
    exit();
}

chdir(ROOT);
$hls_file = HLS_SAVE_PATH."\\".$dir_id."\\".$dir_id.".m3u8" ;
//$hls_file = ".folder/Video/tmp/hogehoge/output.m3u8";

if(file_exists($hls_file) == false){

    //とりあえずバッチファイル作って先実行で様子見。
    //いろいろとできてないです、、
    $fp = fopen("hls.bat", "a");
    if(file_exists(dirname($hls_file)) == false){
        //mkdir(dirname($hls_file));
        @fwrite($fp,"cd ".ROOT."\n");
        $hls_file = str_replace("/","\\",$hls_file);
        @fwrite($fp,"mkdir ".dirname($hls_file)."\n");
    }
    $file_full_path = realpath($path);
    $hls_full_path = realpath(dirname($hls_file));  //windows表記のパスに変えたかった、、
    //変換には時間がかかるのでバックグラウンドで実行（&）を付ける
    //変換が完了したとかは特に確認してないから、改善したほうがいい。↓参考
    //https://qiita.com/kazukichi/items/c0516edb3898b469198b
    //現在、m3u8ファイルがあるかで判断してるけど、変換処理の最初のほうでできちゃうからぶっちゃけダメ

    //$cmd = "ffmpeg -i \"".$file_full_path."\" -c copy -f hls -hls_list_size 0 ".$hls_full_path."\\".$dir_id.".m3u8";
    $cmd = "ffmpeg -i \"".$file_full_path."\" -c copy -f hls -hls_list_size 0 ".$hls_file;
    //exec($cmd,$output_array,$result_code);
    //$WshShell = new COM("WScript.Shell");
    //$oExec = $WshShell->Run($cmd,0,false);
    @fwrite($fp,$cmd."\n");
    fclose($fp); 

    //echo "<br>変換処理を開始しました。しばらくしてからリロードしてください<br>\n";
    echo "<br>ごめん、準備できていないの、あきらめてください<br>\n";
}
else {

    $hls_file_url = str_replace("\\","/",$hls_file);

    //$hls_file_urlをurlエンコードすると、映像ファイルが読まれない（スラッシュまでもエンコードされるのが原因？）
    //なので、$hls_file_urlには日本語とかが絶対入らないようにする。
    echo "<video src=\"/".$hls_file_url."\" controls><p>このビデオはこのブラウザでは再生できません</p></video><br>\n";

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
    echo "<a href=\"./taggedlist.php?tag=".$tag."\"> ".$tag." </a>　";
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