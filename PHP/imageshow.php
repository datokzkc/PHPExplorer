<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>画像一覧表示</title>
<link rel="stylesheet" type="text/css" href="/HTTP/CSS/imageshow.css">
<!-- jQuery -->
<script type="text/javascript" src="/HTTP/jquery-3.5.0.js"></script>
<script type="text/javascript" src="/HTTP/javascript/totop.js"></script>
<script type="text/javascript" src="/HTTP/javascript/tagcont.js"></script>
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
    $path = "."; //設定されていないときはルートディレクトリのパス
}
chdir(ROOT); // ディレクトリの場所の初期化
$name = basename(realpath($path));
echo"<h1>「{$name}」内の画像一覧</h1><br>\n";

echo "<a href = \"/{$path}\" >現在表示しているディレクトリへ移動(/{$path})</a><br>\n";
echo "<a href = \"./covershow.php?path=".rawurlencode($path)."\"> 現在のディレクトリ内のディレクトリ代表画像一覧</a><br>\n";
echo "<a href = \"./allshow.php?path=".rawurlencode($path)."\"> 子ディレクトリ内含め全表示</a><br>\n";
echo "<a href = \"./slideshow.php?mode=dir&path=".rawurlencode($path)."\"> スライドショー形式で表示</a><br>\n";
if(realpath($path)==ROOT){
    //自身で設定したROOTより上に行くリンクも作成しない
}else{
    echo "<a href = \"./imageshow.php?path=".rawurlencode(dirname($path))."\"> 親ディレクトリへ（画像表示）</a><br>\n";
    echo "<a href = \"./covershow.php?path=".rawurlencode(dirname($path))."\"> 親ディレクトリへ（代表画像表示）</a><br>\n";
}

// パスの中に[]で書かれた場所があればその内容での検索するリンクを追加
$ptn = "/\[([^\]]*)\]/u";
$ptn_match_res = preg_match_all($ptn, $path, $cell, PREG_PATTERN_ORDER);
if ($ptn_match_res != false && $ptn_match_res != 0){
    for($i = 0 ; $i < 5 ; $i = $i +1){
        if(isset($cell[1][$i])){
            echo "<a href = \"./db_search.php?search=".rawurlencode($cell[1][$i])."\"> DBから[".$cell[1][$i]."]を検索</a><br>\n";
        }
    }
}
?>
</div>
<div class ="imageshow">
<?php
$list = scandir($path);
//隠しファイルの削除
$list = preg_grep('/^\..*/',$list,PREG_GREP_INVERT);
natsort($list);
$list = array_values($list);

if(isset($_GET['redirect'])){
    $redir = $_GET['redirect'];
}else{
    $redir = 1; //設定されていないときはリダイレクトオン(1)
}
//内容が50枚以上の場合はcovershowへリダイレクト
if($redir==1 && count($list) > 50){
    header("Location: ./covershow.php?img=1&rawno=25&dirimg=0&path=".rawurlencode($path));
    exit;
}

echo "<h2>合計：".count($list)."枚（画像以外のファイルなども含む）</h2><br>\n";

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

<?php
chdir($path); // ディレクトリ移動

foreach($list as $key => $img){
    $link = substr(realpath($img),strlen(ROOT));
    //if(is_dir(mb_convert_encoding($img, 'sjis', 'utf-8'))==TRUE){

    if(is_dir($img)==TRUE){
        //ディレクトリの場合はpathを変更した自身のリンクを表示
        echo "<a href = \"./".basename(__FILE__)."?path=".rawurlencode($path)."/".$img."\"> &lt; DIR &gt;：{$img} </a>";
        echo "<br>\n";
    }elseif(is_picture($img) == TRUE){
        echo "<img src=\"/{$link}\" >";
        echo "<br>{$key}<br>\n";
    }elseif(is_audio($img) || is_video($img)){
        //メディアはメディア再生ページへのリンクを表示
        echo "<a href = \"./mediaplay.php?path=".rawurlencode($link)."\"> {$img} </a>(メディア再生ページへ)";
        echo "<br>\n";
    }
    else{
        //画像でないときはリンクを表示
        echo "<a href = \"/{$link}\"> {$img} </a>";
        echo "<br>\n";
    }
}
?>
</div>
<div class="footer">
<?php
echo"<p>ディレクトリ名「{$name}」</p>\n";

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
echo "<a href = \"/{$path}\" >現在表示しているディレクトリへ移動(/{$path})</a><br>\n";
echo "<a href = \"./covershow.php?path=".rawurlencode($path)."\"> 現在のディレクトリ内のディレクトリ代表画像一覧</a><br>\n";
echo "<a href = \"./allshow.php?path=".rawurlencode($path)."\"> 子ディレクトリ内含め全表示</a><br>\n";
echo "<a href = \"./slideshow.php?mode=dir&path=".rawurlencode($path)."\"> スライドショー形式で表示</a><br>\n";
if(realpath($path)==ROOT){
    //自身で設定したROOTより上に行くリンクも作成しない
}else{
    echo "<a href = \"./imageshow.php?path=".rawurlencode(dirname($path))."\"> 親ディレクトリへ（画像表示）</a><br>\n";
    echo "<a href = \"./covershow.php?path=".rawurlencode(dirname($path))."\"> 親ディレクトリへ（代表画像表示）</a><br>\n";
}

// パスの中に[]で書かれた場所があればその内容での検索するリンクを追加
$ptn = "/\[([^\]]*)\]/u";
$ptn_match_res = preg_match_all($ptn, $path, $cell, PREG_PATTERN_ORDER);
if ($ptn_match_res != false && $ptn_match_res != 0){
    for($i = 0 ; $i < 5 ; $i = $i +1){
        if(isset($cell[1][$i])){
            echo "<a href = \"./db_search.php?search=".rawurlencode($cell[1][$i])."\"> DBから[".$cell[1][$i]."]を検索</a><br>\n";
        }
    }
}
?>
</div>
<div id="totop"><a href="#"></a></div>
</body>
</html>