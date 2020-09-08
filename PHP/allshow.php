<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>
画像一覧(サブディレクトリ含む)
</title>
<link rel="stylesheet" type="text/css" href="/HTTP/CSS/allshow.css">
<!-- jQuery -->
<script type="text/javascript" src="/HTTP/jquery-3.5.0.js"></script>
<script type="text/javascript" src="/HTTP/javascript/totop.js"></script>
<script type="text/javascript" src="/HTTP/javascript/tagcont.js"></script>
</head>

<body>
<div class ="header">
<?php
include 'root_dir.php';
include 'db-func.php';
include 'file-func.php';

if(isset($_GET['path'])){
    $path = $_GET['path'];
}else{
    $path = "."; //設定されていないときはルートディレクトリのパス
}
chdir(ROOT); //ディレクトリの場所の初期化
$name = basename(realpath($path));
echo"<h1>「{$name}」内の画像一覧(サブディレクトリ含む)</h1><br>\n";

echo "<a href = \"/{$path}\" >現在表示しているディレクトリへ移動(/{$path})</a><br>\n";
echo "<a href = \"./covershow.php?path=".rawurlencode($path)."\"> 現在のディレクトリ内のディレクトリ代表画像一覧</a><br>\n";
echo "<a href = \"./imageshow.php?path=".rawurlencode($path)."\"> 子ディレクトリの画像を含めない</a><br>\n";
echo "<a href = \"./slideshow.php?mode=all&path=".rawurlencode($path)."\"> スライドショー形式で表示</a><br>\n";
if(realpath($path)==ROOT){
    //自身で設定したROOTより上に行くリンクも作成しない
}else{
    echo "<a href = \"./imageshow.php?path=".rawurlencode(dirname($path))."\"> 親ディレクトリへ（画像表示）</a><br>\n";
    echo "<a href = \"./covershow.php?path=".rawurlencode(dirname($path))."\"> 親ディレクトリへ（代表画像表示）</a><br>\n";
}
?>
</div>
<div class ="imageshow">
<?php

$list = list_files($path);
//隠しファイルの削除
$list = preg_grep('/^\..*/',$list,PREG_GREP_INVERT);
$list = preg_grep('/^.*\\._.*/',$list,PREG_GREP_INVERT);
natsort($list);
$list = array_values($list);

echo "<h2>合計：".count($list)."枚（画像以外のファイルなども含む）</h2><br>\n";

//タグ表示

chdir(ROOT);
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
<?php

chdir($path); // ディレクトリ移動

foreach($list as $key => $img){
    $link = substr(realpath($img),strlen(ROOT));
    if(is_dir($img)==TRUE){
        //ディレクトリの場合はpathを変更した自身のリンクを表示
        //<注意>このプログラムのままではディレクトリはすべてスキップされるため関係ない
        echo "<a href = \"./".basename(__FILE__)."?path=".rawurlencode($path)."/".$img."\"> &lt; DIR &gt;：{$img} </a>";
        echo "<br>\n";
    }else if(is_picture($img) == TRUE){
        //画像の時は画像を表示
        echo "<img src=\"/{$link}\" >";
        echo "<br>{$key}：{$img}<br>\n";
    }else if(is_audio($img) || is_video($img)){
        echo "<a href = \"./mediaplay.php?path=".rawurlencode($link)."\"> {$img} </a>：メディア再生ページに移動します";
        echo "<br>\n";
    }
    else{
        echo "<a href = \"/{$link}\"> {$img} </a>";
        echo "<br>\n";
    }
}

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
?>
</div>
<div class="footer">
<?php
echo"<p>ディレクトリ名「{$name}」</p>\n";
//タグ表示
echo "<div class=\"tags\">\n<div class=\"tagshow\">\n";
chdir(ROOT);
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
echo "<a href = \"/{$path}\" >現在表示しているディレクトリへ移動(/{$path})</a><br>\n";
echo "<a href = \"./covershow.php?path=./".rawurlencode($path)."\"> 現在のディレクトリ内のディレクトリ代表画像一覧</a><br>\n";
echo "<a href = \"./imageshow.php?path=./".rawurlencode($path)."\"> 子ディレクトリの画像を含めない</a><br>\n";
echo "<a href = \"./slideshow.php?mode=all&path=".rawurlencode($path)."\"> スライドショー形式で表示</a><br>\n";
if(realpath($path)==ROOT){
    //自身で設定したROOTより上に行くリンクも作成しない
}else{
    echo "<a href = \"./imageshow.php?path=./".rawurlencode(dirname($path))."\"> 親ディレクトリへ（画像表示）</a><br>\n";
    echo "<a href = \"./covershow.php?path=./".rawurlencode(dirname($path))."\"> 親ディレクトリへ（代表画像表示）</a><br>\n";
}
?>
</div>
<div id="totop"><a href="#"></a></div>
</body>
</html>