<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>
DB検索結果（代表画像表示）
</title>
<link rel="stylesheet" type="text/css" href="../CSS/db_search.css">
<!-- jQuery -->
<script type="text/javascript" src="../jquery-3.5.0.js"></script>
<script type="text/javascript" src="../javascript/totop.js"></script>
<script type="text/javascript" src="../javascript/querycont.js"></script>
</head>

<body>
<div class ="header">
<?php
include 'root_dir.php';
include 'db-func.php';
include 'file-func.php';
include 'search-class.php';

if(isset($_GET['search'])){
    $search = $_GET['search'];
}else{
    echo "<b>検索ワードが設定されていません</b>\n";
    exit();
}
chdir(ROOT); //ディレクトリの場所の初期化

echo"<h1>「".htmlspecialchars($search)."」の検索結果</h1>\n";

echo "<a href = \"./db_all.php\" >データベース登録の全表示へ戻る</a><br>\n";

echo "<br>\n";
echo "<form action=\"./db_search.php\" method=\"get\" class=\"search_form\">\n";
echo "<input type=\"text\" name=\"search\" value=\"".htmlspecialchars($search)."\">\n";
echo "<button type=\"submit\">検索</button>\n";
echo "</form>\n";
?>
</div>
<?php
//DB検索クエリ操作フォーム
?>
<div class="search_query_form">
<p id="info_text">検索クエリ記録</p>
<select id="db_query_list">
<?php
$query_list = get_search_query_list();
foreach($query_list as $query){
    echo "<option label=\"".htmlspecialchars($query["name"])."\" value=\"".htmlspecialchars($query["query"])."\"> ".htmlspecialchars($query["name"])." </option>\n";
}
?>
<option label="自分で入力(新規追加)">自分で入力(新規追加)</option>
</select>
<input id="add_query_text" type="text" name="add_txt_tag" hidden><br>
<input id="read_query_btn" type="button" value="検索クエリ読み込み" />
<input id="add_query_btn" type="button" value="現在の検索条件で検索クエリ保存" />
<input id="rm_query_btn" type="button" value="保存済み検索クエリ削除" />
<?php
echo "<div id=\"now_query_text\" hidden>".htmlspecialchars($search)."</div>\n";
?>
</div class="search_query_form">

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
if(isset($_GET['dirimg'])){
    $dir_img = $_GET['dirimg'];
}else{
    $dir_img = 1; //初期値はオン(1)
}

$dirlist = all_dir_list();
//隠しファイルの削除
//$list = preg_grep('/^\..*/',$list,PREG_GREP_INVERT);
chdir(ROOT); // ディレクトリ移動

//抽出
$search_query = mb_convert_encoding($search,"UTF-8");
$search_obj = new SearchClass(SearchClass::KEEP_QUERY_MODE);
$search_obj->set_query_str($search_query);
//検索条件に合致するものを抽出
$dirlist = $search_obj->filter_list_by_query($dirlist);

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

echo "<h2>合計：".count($dirlist)."コンテンツ</h2>\n";

echo "<p><b>".$page_no."ページ目&emsp;&ensp;１ページ表示件数: ".$page_long."&emsp;並び順: ";
if($is_shuffle == 1){
    echo "シャッフル";
}else{
    echo "通常（５０音順）";
}
echo "</b></p>\n";

//表示件数切り替え
$change_row = ceil($page_long / 2); //減少は半分の切り上げ
echo "<p><a href=\"./".basename(__FILE__)."?search=".rawurlencode($search)."&rawno=".$change_row."&page=".$page_no."&shuffle=".$is_shuffle."&dirimg=".$dir_img."\">"."１ページ".$change_row."件表示に切り替え</a> &ensp; ";
$change_row = $page_long * 2;
echo "<a href=\"./".basename(__FILE__)."?search=".rawurlencode($search)."&rawno=".$change_row."&page=".$page_no."&shuffle=".$is_shuffle."&dirimg=".$dir_img."\">"."１ページ".$change_row."件表示に切り替え</a>\n";
echo"<br>\n";

//並び替え選択
if($is_shuffle == 0){
    echo "<a href=\"./".basename(__FILE__)."?search=".rawurlencode($search)."&rawno=".$page_long."&page=".$page_no."&shuffle="."1"."&dirimg=".$dir_img."\">"."シャッフルする</a><br>\n";
}else{
    echo "<a href=\"./".basename(__FILE__)."?search=".rawurlencode($search)."&rawno=".$page_long."&page=".$page_no."&shuffle="."0"."&dirimg=".$dir_img."\">"."通常の並びへ戻す</a><br>\n";
}

//ディレクトリ画像オンオフ
if($dir_img == 0){
    echo "<a href=\"./".basename(__FILE__)."?search=".rawurlencode($search)."&rawno=".$page_long."&page=".$page_no."&shuffle=".$is_shuffle."&dirimg="."1"."\">"."ディレクトリ代表画像の表示</a><br>\n";
}else{
    echo "<a href=\"./".basename(__FILE__)."?search=".rawurlencode($search)."&rawno=".$page_long."&page=".$page_no."&shuffle=".$is_shuffle."&dirimg="."0"."\">"."ディレクトリ代表画像の非表示</a><br>\n";
}

//ページ移動
echo "<div class=\"pageIndex\">\n";
if($page_no < $max_page){
    $next_page = $page_no + 1;
    echo "<a href=\"./".basename(__FILE__)."?search=".rawurlencode($search)."&rawno=".$page_long."&page=".$next_page."&shuffle=".$is_shuffle."&dirimg=".$dir_img."\" class = \"next_btn\"> NEXT(".$next_page."ページ) &gt; </a>  <br>\n";
}
for($i = 1; $i <= $max_page; $i++){
    if($i == $page_no){ //現在のページはリンクを張らない
        echo "<b>{$page_no}</b>  ";
    }else{
        echo "<a href=\"./".basename(__FILE__)."?search=".rawurlencode($search)."&rawno=".$page_long."&page=".$i."&shuffle=".$is_shuffle."&dirimg=".$dir_img."\">".$i."</a>  ";
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
    $link = substr(realpath($folder),strlen(ROOT));
    $disp_link = htmlspecialchars($link);
    echo "<tr><td>";

    if(is_dir($folder)){
        //フォルダの時
        if($dir_img == 0){
            $key ++;
            echo "<a href = \"./imageshow.php?path=".rawurlencode($link)."\">".$key.": {$disp_link}</a> (画像一覧表示へ移動)<br>";
            print_tag($folder);
            $key --;
        }else{
            if(($img = gettopimage($folder,3)) == NULL){
            //画像が存在しないときはディレクトリへのリンクを表示
            $key ++;
            echo "<a href = \"/{$link}\">".$key.": {$disp_link}</a>（直近３階層ディレクトリ内画像なし）";
            echo "<br>";
            print_tag($folder);
            $key --;
            }
            else{
                $key ++;
                $imglink = substr(realpath($folder."/".$img),strlen(ROOT));
                //画像が存在する場合はimageshowに渡す
                echo "<a href = \"./imageshow.php?path=".rawurlencode($link)."\"><img src=\"/{$imglink}\" ></a>";
                echo "<br><a href = \"./imageshow.php?path=".rawurlencode($link)."\">".$key.": {$disp_link}</a><br>";
                print_tag($folder);
                $key --;
            }
        }
    }elseif(is_picture($folder)){
        //画像の場合は表示して次へ
        $key ++;
        $imglink = substr(realpath($folder),strlen(ROOT));
        echo "<img src=\"/{$imglink}\" >";
        echo "<br>".$key.": {$disp_link}<br>";
        print_tag($folder);
        $key --;
    }elseif(is_audio($folder) || is_video($folder)){
        //メディアの場合はメディア再生ページへのリンクを張る
        $key ++;
        $imglink = substr(realpath($folder),strlen(ROOT));
        echo "<a href=\"./mediaplay.php?path=".rawurlencode($imglink)."\" >";
        echo "".$key.": {$folder}</a> (メディア再生ページへ)<br>";
        print_tag(realpath($folder));
        $key --;
    }else{
        //画像以外のファイル
        $key ++;
        echo "<a href = \"/{$link}\">".$key.": {$disp_link}</a>（ファイル）";
        echo "<br>";
        print_tag($folder);
        $key --;
    }
    echo "</td></tr>\n";
}
echo "</table><br>\n";

//ページ移動
echo "<div class=\"pageIndex\">";
if($page_no < $max_page){
    $next_page = $page_no + 1;
    echo "<a href=\"./".basename(__FILE__)."?search=".rawurlencode($search)."&rawno=".$page_long."&page=".$next_page."&shuffle=".$is_shuffle."&dirimg=".$dir_img."\" class = \"next_btn\"> NEXT(".$next_page."ページ) &gt; </a>  <br>\n";
}
for($i = 1; $i <= $max_page; $i++){
    if($i == $page_no){ //現在のページはリンクを張らない
        echo "<b>{$page_no}</b>  ";
    }else{
        echo "<a href=\"./".basename(__FILE__)."?search=".rawurlencode($search)."&rawno=".$page_long."&page=".$i."&shuffle=".$is_shuffle."&dirimg=".$dir_img."\">".$i."</a>  ";
    }
}
echo "</div>";


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
        if(is_picture($foldername."/".$file) != FALSE){
            return $file;
        }
    }
    //見つからなかった場合
    return NULL;
}

//タグ表示（これより前には改行が入っている前提）
function print_tag(String $path){
    $tags = dir_tag_list($path);
    $search_obj = new SearchClass(SearchClass::KEEP_TARGET_MODE);
    $search_obj->set_target_str($path);
    $querys = $search_obj->pickup_match_query(get_search_query_list());
    if(count($tags) == 0 && count($querys) == 0){
        return;
    }
    echo "<div class = \"tags\">\n";
    foreach($tags as $tag){
        echo "<a href=\"./"."taggedlist.php"."?tag[]=".rawurlencode($tag)."\" class=\"tag\"> ".htmlspecialchars($tag)."</a>　";
    }
    foreach($querys as $query){
        echo "<a href=\"./db_search.php?search=".rawurlencode($query["query"])."\" class=\"searchquery\"> ".htmlspecialchars($query["name"])." </a>　";
    }
    echo "</div>\n";
}
?>
</div>
<div class="footer">
<?php
chdir(ROOT);
echo"<p>「".htmlspecialchars($search)."」の検索結果<p><br>\n";
echo "<a href = \"./db_all.php\" >データベース登録の全表示へ戻る</a><br>\n";
?>
</div>
<div id="totop"><a href="#"></a></div>
</body>
</html>