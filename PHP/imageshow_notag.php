<html>
<head>
<title>画像一覧表示</title>
<link rel="stylesheet" type="text/css" href="/HTTP/CSS/imageshow.css">
<!-- jQuery -->
<script type="text/javascript" src="/HTTP/jquery-3.5.0.js"></script>
<script type="text/javascript" src="/HTTP/javascript/totop.js"></script>
</head>

<body>
<div class ="header">
<?php
define("ROOT",$_SERVER['DOCUMENT_ROOT']);
setlocale(LC_ALL, 'ja_JP.UTF-8');

if(isset($_GET['path'])){
    $path = $_GET['path'];
}else{
    $path = "."; //設定されていないときはルートディレクトリのパス
}
chdir(ROOT); // ディレクトリの場所の初期化
$name = basename(realpath($path));
echo"<h1>「{$name}」内の画像一覧</h1><br>\n";

echo "<a href = \"/{$path}\" >現在表示しているディレクトリへ移動(/{$path})</a><br>\n";
echo "<a href = \"./covershow.php?path={$path}\"> 現在のディレクトリ内のディレクトリ代表画像一覧</a><br>\n";
echo "<a href = \"./allshow.php?path={$path}\"> 子ディレクトリ内含め全表示</a><br>\n";
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
    header("Location: ./covershow.php?img=1&rawno=25&dirimg=0&path=".$path);
    exit;
}

echo "<h2>合計：".count($list)."枚（画像以外のファイルなども含む）</h2><br>\n";

chdir($path); // ディレクトリ移動

foreach($list as $key => $img){
    $link = substr(realpath($img),strlen(realpath(ROOT))+1);
    //if(is_dir(mb_convert_encoding($img, 'sjis', 'utf-8'))==TRUE){

    if(is_dir($img)==TRUE){
        //ディレクトリの場合はpathを変更した自身のリンクを表示
        echo "<a href = \"./".basename(__FILE__)."?path=".$path."/".$img."\"> &lt; DIR &gt;：{$img} </a>";
        echo "<br>\n";
    }elseif(exif_imagetype($img) == FALSE){
        //画像でないときはリンクを表示
        echo "<a href = \"/{$link}\"> {$img} </a>";
        echo "<br>\n";
    }
    else{
        echo "<img src=\"/{$link}\" >";
        echo "<br>{$key}<br>\n";
    }
}
?>
</div>
<div class="footer">
<?php
echo"<p>ディレクトリ名「{$name}」</p><br>\n";

echo "<a href = \"/{$path}\" >現在表示しているディレクトリへ移動(/{$path})</a><br>\n";
echo "<a href = \"./covershow.php?path={$path}\"> 現在のディレクトリ内のディレクトリ代表画像一覧</a><br>\n";
echo "<a href = \"./allshow.php?path={$path}\"> 子ディレクトリ内含め全表示</a><br>\n";
if(realpath($path)==ROOT){
    //自身で設定したROOTより上に行くリンクも作成しない
}else{
    echo "<a href = \"./imageshow.php?path=".dirname($path)."\"> 親ディレクトリへ（画像表示）</a><br>\n";
    echo "<a href = \"./covershow.php?path=".dirname($path)."\"> 親ディレクトリへ（代表画像表示）</a><br>\n";
}
?>
</div>
<div id="totop"><a href="#"></a></div>
</body>
</html>