<html>
<head>
<title>
画像一覧(サブディレクトリ含む)
</title>
<link rel="stylesheet" type="text/css" href="/HTTP/CSS/allshow.css">
<!-- jQuery -->
<script type="text/javascript" src="/HTTP/jquery-3.5.0.js"></script>
<script type="text/javascript" src="/HTTP/javascript/totop.js"></script>
</head>

<body>
<div class ="header">
<?php
define("ROOT",$_SERVER['DOCUMENT_ROOT']);

if(isset($_GET['path'])){
    $path = $_GET['path'];
}else{
    $path = "."; //設定されていないときはルートディレクトリのパス
}
chdir(ROOT); //ディレクトリの場所の初期化
$name = basename(realpath($path));
echo"<h1>「{$name}」内の画像一覧(サブディレクトリ含む)</h1><br>\n";

echo "<a href = \"/{$path}\" >現在表示しているディレクトリへ移動(/{$path})</a><br>\n";
echo "<a href = \"./covershow.php?path={$path}\"> 現在のディレクトリ内のディレクトリ代表画像一覧</a><br>\n";
echo "<a href = \"./imageshow.php?path={$path}\"> 子ディレクトリの画像を含めない</a><br>\n";
if(realpath($path)==ROOT){
    //自身で設定したROOTより上に行くリンクも作成しない
}else{
    echo "<a href = \"./imageshow.php?path=".dirname($path)."\"> 親ディレクトリへ（画像表示）</a><br>\n";
    echo "<a href = \"./covershow.php?path=".dirname($path)."\"> 親ディレクトリへ（代表画像表示）</a><br>\n";
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

chdir($path); // ディレクトリ移動

foreach($list as $key => $img){
    $link = substr(realpath($img),strlen(realpath(ROOT))+1);
    if(is_dir($img)==TRUE){
        //ディレクトリの場合はpathを変更した自身のリンクを表示
        //<注意>このプログラムのままではディレクトリはすべてスキップされるため関係ない
        echo "<a href = \"./".basename(__FILE__)."?path=".$path."/".$img."\"> &lt; DIR &gt;：{$img} </a>";
        echo "<br>\n";
    }else if(exif_imagetype($img) == FALSE){
        //画像でないときはリンクを表示
        echo "<a href = \"/{$link}\"> {$img} </a>";
        echo "<br>\n";
    }
    else{
        echo "<img src=\"/{$link}\" >";
        echo "<br>{$key}：{$img}<br>\n";
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
echo"<p>ディレクトリ名「{$name}」</p><br>\n";

echo "<a href = \"/{$path}\" >現在表示しているディレクトリへ移動(/{$path})</a><br>\n";
echo "<a href = \"./covershow.php?path=./{$path}\"> 現在のディレクトリ内のディレクトリ代表画像一覧</a><br>\n";
echo "<a href = \"./imageshow.php?path=./{$path}\"> 子ディレクトリの画像を含めない</a><br>\n";
if(realpath($path)==ROOT){
    //自身で設定したROOTより上に行くリンクも作成しない
}else{
    echo "<a href = \"./imageshow.php?path=./".dirname($path)."\"> 親ディレクトリへ（画像表示）</a><br>\n";
    echo "<a href = \"./covershow.php?path=./".dirname($path)."\"> 親ディレクトリへ（代表画像表示）</a><br>\n";
}
?>
</div>
<div id="totop"><a href="#"></a></div>
</body>
</html>