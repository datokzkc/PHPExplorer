<html>
<head>
<title>
ディレクトリ一覧（代表画像表示）
</title>
<link rel="stylesheet" type="text/css" href="/HTTP/CSS/covershow.css">
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
    $path = "."; //設定されていないときはサイトルートのパス
}
chdir(ROOT); //ディレクトリの場所の初期化
$name = basename(realpath($path));

echo"<h1>「{$name}」内の代表画像一覧</h1>\n";

echo "<a href = \"/{$path}\" >現在表示しているディレクトリへ移動(/{$path})</a><br>\n";
echo "<a href = \"./imageshow.php?redirect=0&path={$path}\"> 現在のディレクトリ内の画像一覧</a><br>\n";
echo "<a href = \"./allshow.php?path={$path}\"> 子ディレクトリ内含め全表示</a><br>\n";
if(realpath($path)==ROOT){
    //自身で設定したROOTより上に行くリンクも作成しない
}else{
    echo "<a href = \"./imageshow.php?path=".dirname($path)."\"> 親ディレクトリへ（画像表示）</a><br>\n";
    echo "<a href = \"./covershow.php?path=".dirname($path)."\"> 親ディレクトリへ（代表画像表示）</a><br>\n";
}
?>
</div>
<div class ="covershow">
<?php
/*
$path = getcwd();
*/

//引数取得
if(isset($_GET['rawno'])){
    $page_long = $_GET['rawno'];
}else{
        $page_long = 10; //初期値はmax１０
}
if(isset($_GET['page'])){
    $page_no = $_GET['page'];
}else{
    $page_no = 1; //ページが設定されていないときは１
}
if(isset($_GET['shuffle'])){
    $is_shuffle = $_GET['shuffle'];
}else{
    $is_shuffle = 0; //初期値はシャッフルオフ(0)
}
if(isset($_GET['img'])){
    $on_img = $_GET['img'];
}else{
    $on_img = 0; //初期値はオフ(0)
}
if(isset($_GET['dirimg'])){
    $dir_img = $_GET['dirimg'];
}else{
    $dir_img = 1; //初期値はオン(1)
}
//pathは上で設定済み

$list = scandir($path);
//隠しファイルの削除
$list = preg_grep('/^\..*/',$list,PREG_GREP_INVERT);
chdir($path); // ディレクトリ移動
//ディレクトリのみ抜粋
$dirlist = array();
foreach($list as $listpath){
    if(is_dir($listpath) == TRUE){
        $dirlist[] = $listpath;
    }else if($on_img == 1){
        if(!getimagesize($listpath)){
            //nothing do
        }else{
            //オプションで画像も追加
            $dirlist[] = $listpath;
        }
    }
}
if(count($dirlist)== 0){
    $dirlist[] = "\n--@//nothing";
}
if($is_shuffle == 1){
    shuffle($dirlist);
}else{
    natsort($dirlist);
}
$dirlist = array_values($dirlist);
$max_page = ceil(count($dirlist) / $page_long); //切り上げでページ数指定

if($page_no > $max_page){
    $page_no = $max_page;
}

if($dirlist[0] != "\n--@//nothing"){
    if($on_img == 0){
        echo "<h2>合計：".count($dirlist)."ディレクトリ</h2>\n";
    }else{
        echo "<h2>合計：".count($dirlist)."コンテンツ</h2>\n";
    }
}

echo "<p><b>".$page_no."ページ目&emsp;&ensp;１ページ表示件数: ".$page_long."&emsp;並び順: ";
if($is_shuffle == 1){
    echo "シャッフル";
}else{
    echo "通常（５０音順）";
}
echo "</b></p>\n";

//表示件数切り替え
$change_row = ceil($page_long / 2); //減少は半分の切り上げ
echo "<p><a href=\"./".basename(__FILE__)."?rawno=".$change_row."&page=".$page_no."&shuffle=".$is_shuffle."&img=".$on_img."&dirimg=".$dir_img."&path=".$path."\">"."１ページ".$change_row."件表示に切り替え</a> &ensp; ";
$change_row = $page_long * 2;
echo "<a href=\"./".basename(__FILE__)."?rawno=".$change_row."&page=".$page_no."&shuffle=".$is_shuffle."&img=".$on_img."&dirimg=".$dir_img."&path=".$path."\">"."１ページ".$change_row."件表示に切り替え</a>\n";
echo"<br>\n";

//並び替え選択
if($is_shuffle == 0){
    echo "<a href=\"./".basename(__FILE__)."?rawno=".$page_long."&page=".$page_no."&shuffle="."1"."&img=".$on_img."&dirimg=".$dir_img."&path=".$path."\">"."シャッフルする</a><br>\n";
}else{
    echo "<a href=\"./".basename(__FILE__)."?rawno=".$page_long."&page=".$page_no."&shuffle="."0"."&img=".$on_img."&dirimg=".$dir_img."&path=".$path."\">"."通常の並びへ戻す</a><br>\n";
}

//ディレクトリ画像オンオフ
if($dir_img == 0){
    echo "<a href=\"./".basename(__FILE__)."?rawno=".$page_long."&page=".$page_no."&shuffle=".$is_shuffle."&img=".$on_img."&dirimg="."1"."&path=".$path."\">"."ディレクトリ代表画像の表示</a><br>\n";
}else{
    echo "<a href=\"./".basename(__FILE__)."?rawno=".$page_long."&page=".$page_no."&shuffle=".$is_shuffle."&img=".$on_img."&dirimg="."0"."&path=".$path."\">"."ディレクトリ代表画像の非表示</a><br>\n";
}

//画像オンオフ
if($on_img == 0){
    echo "<a href=\"./".basename(__FILE__)."?rawno=".$page_long."&page=".$page_no."&shuffle=".$is_shuffle."&img="."1"."&dirimg=".$dir_img."&path=".$path."\">"."ディレクトリ直下の画像も含める</a></p>\n";
}else{
    echo "<a href=\"./".basename(__FILE__)."?rawno=".$page_long."&page=".$page_no."&shuffle=".$is_shuffle."&img="."0"."&dirimg=".$dir_img."&path=".$path."\">"."ディレクトリのみの表示に変更</a></p>\n";
}

//ページ移動
echo "<div class=\"pageIndex\">\n";
if($page_no < $max_page){
    $next_page = $page_no + 1;
    echo "<a href=\"./".basename(__FILE__)."?rawno=".$page_long."&page=".$next_page."&shuffle=".$is_shuffle."&img=".$on_img."&dirimg=".$dir_img."&path=".$path."\"> NEXT(".$next_page."ページ) &gt; </a>  <br>\n";
}
for($i = 1; $i <= $max_page; $i++){
    if($i == $page_no){ //現在のページはリンクを張らない
        echo "<b>{$page_no}</b>  ";
    }else{
        echo "<a href=\"./".basename(__FILE__)."?rawno=".$page_long."&page=".$i."&shuffle=".$is_shuffle."&img=".$on_img."&dirimg=".$dir_img."&path=".$path."\">".$i."</a>  ";
    }
}
echo "</div>\n";
echo "<br>\n";

//一覧リスト生成

//リスト切り出し
$disp_list = array_slice($dirlist, ($page_no -1)*$page_long, $page_long, TRUE);

foreach($disp_list as $key => $folder){
    if(strcmp($folder,"\n--@//nothing") == 0){
        echo "Empty.<br>\n";
        break;
    }
    $link = substr(realpath($folder),strlen(ROOT)+1);

    if($on_img == 1){
        if(is_dir($folder)){
            //nothing do
        }elseif(!getimagesize($folder)){
            //nothing do
        }else{
            //画像の場合は表示して次へ
            $key ++;
            $imglink = substr(realpath($folder),strlen(ROOT)+1);
            echo "<img src=\"/{$imglink}\" >";
            echo "<br> ".$key.": {$folder}<br>\n";
            $key --;
            continue;
        }
    }

    if($dir_img == 0){
        $key ++;
        echo "<a href = \"./imageshow.php?path={$link}\"> ".$key.": {$folder}</a>(画像一覧表示へ移動)<br>\n";
        $key --;
    }else{
        if(($img = gettopimage($folder,3)) == NULL){
        //画像が存在しないときはディレクトリへのリンクを表示
        $key ++;
        echo "<a href = \"/{$link}\"> ".$key.": {$folder} </a>（直近３階層ディレクトリ内画像なし）";
        echo "<br>\n";
        $key --;
        }
        else{
            $key ++;
            $imglink = substr(realpath($folder."/".$img),strlen(ROOT)+1);
            //画像が存在する場合はimageshowに渡す
            echo "<a href = \"./imageshow.php?path={$link}\"> <img src=\"/{$imglink}\" > </a>";
            echo "<br><a href = \"./imageshow.php?path={$link}\"> ".$key.": {$folder}</a><br>\n";
            $key --;
        }
    }
}
echo "<br>\n";

//ページ移動
echo "<div class=\"pageIndex\">\n";
if($page_no < $max_page){
    $next_page = $page_no + 1;
    echo "<a href=\"./".basename(__FILE__)."?rawno=".$page_long."&page=".$next_page."&shuffle=".$is_shuffle."&img=".$on_img."&dirimg=".$dir_img."&path=".$path."\"> NEXT(".$next_page."ページ) &gt; </a>  <br>\n";
}
for($i = 1; $i <= $max_page; $i++){
    if($i == $page_no){ //現在のページはリンクを張らない
        echo "<b>{$page_no}</b>  ";
    }else{
        echo "<a href=\"./".basename(__FILE__)."?rawno=".$page_long."&page=".$i."&shuffle=".$is_shuffle."&img=".$on_img."&dirimg=".$dir_img."&path=".$path."\">".$i."</a>  ";
    }
}
echo "</div>\n";


//foldername内のサブディレクトリnum階層までの画像ファイルを１つ検索
//各階層は上10個のみ調べる（隠しファイルのぞく）
function gettopimage(string $foldername,Int $num){
    if(($list = scandir($foldername)) == FALSE){
        return NULL;
    }
    $stop = 10;
    foreach($list as $file){
        if(preg_match('/^\..*/',$file)==TRUE){
            continue; //ドットから始まるものはスキップ
        }
        $stop--;
        if($stop < 0){
            return NULL; //上位１０階層なければ画像なしとする
        }
        if(is_dir($foldername."/".$file)==TRUE){
            if($num == 1){ //最終階層はフォルダは無視
                continue;
            }else if(($ans = gettopimage($foldername."/".$file,$num-1)) != NULL){
                return $file."/".$ans;
            }else{
                continue;
            }
        }
        if(exif_imagetype($foldername."/".$file) != FALSE){
            return $file;
        }
    }
    //見つからなかった場合
    return NULL;
}
?>
</div>
<div class="footer">
<?php
chdir(ROOT);
$name = basename(realpath($path));

echo"<p>表示ディレクトリ：".$name."</p><br>\n";

echo "<a href = \"/{$path}\" >現在表示しているディレクトリへ移動(/{$path})</a><br>\n";
echo "<a href = \"./imageshow.php?redirect=0&path={$path}\"> 現在のディレクトリ内の画像一覧</a><br>\n";
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