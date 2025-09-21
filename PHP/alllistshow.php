<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>
画像一覧リスト（サブディレクトリ含む）
</title>
<?php

require_once 'common-path.php';
require_once 'db-func.php';
require_once 'file-func.php';
require_once 'search-class.php';
echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"".webPathEncode(pathCombine(BASE_PATH,"/CSS/covershow.css"))."\">\n";
echo "<!-- jQuery -->\n";
echo "<script type=\"text/javascript\" src=\"".webPathEncode(JQUERY_FILE_PATH)."\"></script>\n";
echo "<script type=\"text/javascript\" src=\"".webPathEncode(pathCombine(BASE_PATH,"/javascript/totop.js"))."\"></script>\n";
echo "<script type=\"text/javascript\" src=\"".webPathEncode(pathCombine(BASE_PATH,"/javascript/tagcont.js"))."\"></script>\n";
echo "</head>\n";
?>
<body>
<div class ="header">
<?php

if(isset($_GET['path'])){
    $path = $_GET['path'];
}else{
    $path = "."; //設定されていないときはサイトルートのパス
}
chdir(WEB_ROOT_DIR); //ディレクトリの場所の初期化
$name = basename(realpath($path));

echo"<h1>「{$name}」内の画像一覧リスト(サブディレクトリ含む)</h1>\n";

echo "<a href = \"/{$path}\" >現在表示しているディレクトリへ移動(/{$path})</a><br>\n";
echo "<a href = \"./imageshow.php?redirect=0&path=".rawurlencode($path)."\"> 子ディレクトリの画像を含めない</a><br>\n";
echo "<a href = \"./allshow.php?path=".rawurlencode($path)."\"> 画像一覧表示</a><br>\n";
if(realpath($path)==WEB_ROOT_DIR){
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
            echo "<a href = \"./db_search.php?search=".rawurlencode("\"".$cell[1][$i]."\"")."\"> DBから[".htmlspecialchars($cell[1][$i])."]を検索</a><br>\n";
        }
    }
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
//pathは上で設定済み

$list = list_files($path);
//隠しファイルの削除
$list = preg_grep('/^\..*/',$list,PREG_GREP_INVERT);
chdir($path); // ディレクトリ移動
//ディレクトリのみ抜粋
$dirlist = array();
foreach($list as $listpath){
    if(is_picture($listpath)){
        $dirlist[] = $listpath;
    }elseif(is_audio($listpath) || is_video($listpath)){
        //同時にメディアも追加
        $dirlist[] = $listpath;
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
    echo "<h2>合計：".count($dirlist)."コンテンツ</h2>\n";
}

echo "<h3>「".$name."」のタグ情報</h3>";
//タグ表示
chdir(WEB_ROOT_DIR);
echo "<div class=\"tags\">\n<div class=\"tagshow\">\n";
$tags = dir_tag_list(realpath($path));
foreach($tags as $tag){
    echo "<a href=\"./taggedlist.php?tag[]=".rawurlencode($tag)."\" class=\"tag\"> ".htmlspecialchars($tag)." </a>　";
}
$search_obj = new SearchClass(SearchClass::KEEP_TARGET_MODE);
$search_obj->set_target_str(realpath($path));
$querys = $search_obj->pickup_match_query(get_search_query_list());
foreach($querys as $query){
    echo "<a href=\"./db_search.php?search=".rawurlencode($query["query"])."\" class=\"searchquery\"> ".htmlspecialchars($query["name"])." </a>　";
}
echo "</div class=\"tagshow\">\n";
?>
<p id="info_text1" hidden>説明文</p>
<select id="rm_tag1" hidden>
<?php
foreach($tags as $tag){
    echo "<option value=\"".htmlspecialchars($tag)."\"> ".htmlspecialchars($tag)." </option>\n";
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
echo "<br>";
chdir($path);

echo "<p><b>".$page_no."ページ目&emsp;&ensp;１ページ表示件数: ".$page_long."&emsp;並び順: ";
if($is_shuffle == 1){
    echo "シャッフル";
}else{
    echo "通常（５０音順）";
}
echo "</b></p>\n";

//表示件数切り替え
$change_row = ceil($page_long / 2); //減少は半分の切り上げ
echo "<p><a href=\"./".basename(__FILE__)."?rawno=".$change_row."&page=".$page_no."&shuffle=".$is_shuffle."&path=".rawurlencode($path)."\">"."１ページ".$change_row."件表示に切り替え</a> &ensp; ";
$change_row = $page_long * 2;
echo "<a href=\"./".basename(__FILE__)."?rawno=".$change_row."&page=".$page_no."&shuffle=".$is_shuffle."&path=".rawurlencode($path)."\">"."１ページ".$change_row."件表示に切り替え</a>\n";
echo"<br>\n";

//並び替え選択
if($is_shuffle == 0){
    echo "<a href=\"./".basename(__FILE__)."?rawno=".$page_long."&page=".$page_no."&shuffle="."1"."&path=".rawurlencode($path)."\">"."シャッフルする</a><br>\n";
}else{
    echo "<a href=\"./".basename(__FILE__)."?rawno=".$page_long."&page=".$page_no."&shuffle="."0"."&path=".rawurlencode($path)."\">"."通常の並びへ戻す</a><br>\n";
}


//ページ移動
echo "<div class=\"pageIndex\">\n";
if($page_no < $max_page){
    $next_page = $page_no + 1;
    echo "<a href=\"./".basename(__FILE__)."?rawno=".$page_long."&page=".$next_page."&shuffle=".$is_shuffle."&path=".rawurlencode($path)."\" class = \"next_btn\"> NEXT(".$next_page."ページ) &gt; </a>  <br>\n";
}
for($i = 1; $i <= $max_page; $i++){
    if($i == $page_no){ //現在のページはリンクを張らない
        echo "<b>{$page_no}</b>  ";
    }else{
        echo "<a href=\"./".basename(__FILE__)."?rawno=".$page_long."&page=".$i."&shuffle=".$is_shuffle."&path=".rawurlencode($path)."\">".$i."</a>  ";
    }
}
echo "</div>\n";
echo "<br>\n";

//一覧リスト生成

//リスト切り出し
$disp_list = array_slice($dirlist, ($page_no -1)*$page_long, $page_long, TRUE);

echo "<table>\n";
foreach($disp_list as $key => $folder){
    if(strcmp($folder,"\n--@//nothing") == 0){
        echo "<tr><td>Empty.</td></tr>";
        break;
    }

    echo "<tr><td>";
    $link = getRelativePath(realpath($folder),WEB_ROOT_DIR);

    if(is_picture($folder)){
        //画像の場合は表示して次へ
        $key ++;
        $imglink = getRelativePath(realpath($folder),WEB_ROOT_DIR);
        echo "<img src=\"/".rawurlencode($imglink)."\" >";
        echo "<br>".$key.": ".htmlspecialchars($folder)."<br>";
        $key --;
    }elseif(is_audio($folder)||is_video($folder)){
        //メディアの場合はメディア再生ページへのリンクを張る
        $key ++;
        $imglink = getRelativePath(realpath($folder),WEB_ROOT_DIR);
        echo "<a href=\"./mediaplay.php?path=".rawurlencode($imglink)."\" >";
        echo "".$key.": ".htmlspecialchars($folder)."</a>(メディア再生ページへ)<br>";
        print_tag(realpath($folder));
        $key --;
    }
}
echo "</table><br>\n";

//ページ移動
echo "<div class=\"pageIndex\">\n";
if($page_no < $max_page){
    $next_page = $page_no + 1;
    echo "<a href=\"./".basename(__FILE__)."?rawno=".$page_long."&page=".$next_page."&shuffle=".$is_shuffle."&path=".rawurlencode($path)."\" class = \"next_btn\"> NEXT(".$next_page."ページ) &gt; </a>  <br>\n";
}
for($i = 1; $i <= $max_page; $i++){
    if($i == $page_no){ //現在のページはリンクを張らない
        echo "<b>{$page_no}</b>  ";
    }else{
        echo "<a href=\"./".basename(__FILE__)."?rawno=".$page_long."&page=".$i."&shuffle=".$is_shuffle."&path=".rawurlencode($path)."\">".$i."</a>  ";
    }
}
echo "</div>\n";

//サブディレクトリ含めて全取得する関数
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

//タグ表示（これより前には改行が入っている前提）
function print_tag(String $path){
    $tags = dir_tag_list($path);
    if(count($tags) == 0){
        return;
    }
    echo "<div class = \"tags\">";
    foreach($tags as $tag){
        echo "<a href=\"./"."taggedlist.php"."?tag[]=".rawurlencode($tag)."\" class=\"tag\"> ".htmlspecialchars($tag)."</a>　";
    }
    $search_obj = new SearchClass(SearchClass::KEEP_TARGET_MODE);
    $search_obj->set_target_str($path);
    $querys = $search_obj->pickup_match_query(get_search_query_list());
    foreach($querys as $query){
        echo "<a href=\"./db_search.php?search=".rawurlencode($query["query"])."\" class=\"searchquery\"> ".htmlspecialchars($query["name"])." </a>　";
    }
    echo "</div>";
}
?>
</div>
<div class="footer">
<?php
chdir(WEB_ROOT_DIR);
$name = basename(realpath($path));

echo"<p>表示ディレクトリ：".$name."</p>\n";
//タグ表示
echo "<div class=\"tags\">\n<div class=\"tagshow\">\n";
$tags = dir_tag_list(realpath($path));
foreach($tags as $tag){
    echo "<a href=\"./taggedlist.php?tag[]=".rawurlencode($tag)."\" class=\"tag\"> ".htmlspecialchars($tag)." </a>　";
}
$search_obj = new SearchClass(SearchClass::KEEP_TARGET_MODE);
$search_obj->set_target_str(realpath($path));
$querys = $search_obj->pickup_match_query(get_search_query_list());
foreach($querys as $query){
    echo "<a href=\"./db_search.php?search=".rawurlencode($query["query"])."\" class=\"searchquery\"> ".htmlspecialchars($query["name"])." </a>　";
}
echo "</div class=\"tagshow\">\n";
?>
<p id="info_text2" hidden>説明文</p>
<select id="rm_tag2" hidden>
<?php
foreach($tags as $tag){
    echo "<option value=\"".htmlspecialchars($tag)."\"> ".htmlspecialchars($tag)." </option>\n";
}
?>
</select>
<select id="add_tag_list2" hidden>
<?php
$addlist = array_diff(all_tag_list(),$tags);
foreach($addlist as $tag){
    echo "<option value=\"".htmlspecialchars($tag)."\"> ".htmlspecialchars($tag)." </option>\n";
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
echo "<a href = \"./imageshow.php?redirect=0&path=".rawurlencode($path)."\"> 子ディレクトリの画像を含めない</a><br>\n";
echo "<a href = \"./allshow.php?path=".rawurlencode($path)."\"> 画像一覧表示</a><br>\n";
if(realpath($path)==WEB_ROOT_DIR){
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
            echo "<a href = \"./db_search.php?search=".rawurlencode("\"".$cell[1][$i]."\"")."\"> DBから[".htmlspecialchars($cell[1][$i])."]を検索</a><br>\n";
        }
    }
}
?>
</div>
<div id="totop"><a href="#"></a></div>
</body>
</html>