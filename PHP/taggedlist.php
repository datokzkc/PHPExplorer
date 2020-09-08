<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>
タグ内一覧（代表画像表示）
</title>
<link rel="stylesheet" type="text/css" href="/HTTP/CSS/covershow.css">
<!-- jQuery -->
<script type="text/javascript" src="/HTTP/jquery-3.5.0.js"></script>
<script type="text/javascript" src="/HTTP/javascript/totop.js"></script>
</head>

<body>
<div class ="header">
<?php
if(isset($_GET['tag'])){
    $nowtag = $_GET['tag'];
}else{
        $nowtag = "全て"; //初期値は"全て"とする
}
include 'root_dir.php';
include 'db-func.php';
include 'file-func.php';

mb_internal_encoding("UTF-8");

chdir(ROOT); //ディレクトリの場所の初期化

echo"<h1>タグ「".$nowtag."」のついたもの一覧</h1>\n";

echo "<a href = \"./taglist.php\" >タグ一覧を表示</a><br>\n"

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
if(isset($_GET['dirimg'])){
    $dir_img = $_GET['dirimg'];
}else{
    $dir_img = 1; //初期値はオン(1)
}
//pathは上で設定済み

$dirlist = tagged_dir_list($nowtag);
if($dirlist == false){
    //空の時
    $dirlist = array();
}
//隠しファイルの削除
//$list = preg_grep('/^\..*/',$list,PREG_GREP_INVERT);
chdir(ROOT); // ディレクトリ移動

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
echo "<p><a href=\"./".basename(__FILE__)."?rawno=".$change_row."&page=".$page_no."&shuffle=".$is_shuffle."&dirimg=".$dir_img."&tag=".$nowtag."\">"."１ページ".$change_row."件表示に切り替え</a> &ensp; ";
$change_row = $page_long * 2;
echo "<a href=\"./".basename(__FILE__)."?rawno=".$change_row."&page=".$page_no."&shuffle=".$is_shuffle."&dirimg=".$dir_img."&tag=".$nowtag."\">"."１ページ".$change_row."件表示に切り替え</a>\n";
echo"<br>\n";

//並び替え選択
if($is_shuffle == 0){
    echo "<a href=\"./".basename(__FILE__)."?rawno=".$page_long."&page=".$page_no."&shuffle="."1"."&dirimg=".$dir_img."&tag=".$nowtag."\">"."シャッフルする</a><br>\n";
}else{
    echo "<a href=\"./".basename(__FILE__)."?rawno=".$page_long."&page=".$page_no."&shuffle="."0"."&dirimg=".$dir_img."&tag=".$nowtag."\">"."通常の並びへ戻す</a><br>\n";
}

//ディレクトリ画像オンオフ
if($dir_img == 0){
    echo "<a href=\"./".basename(__FILE__)."?rawno=".$page_long."&page=".$page_no."&shuffle=".$is_shuffle."&dirimg="."1"."&tag=".$nowtag."\">"."ディレクトリ代表画像の表示</a><br>\n";
}else{
    echo "<a href=\"./".basename(__FILE__)."?rawno=".$page_long."&page=".$page_no."&shuffle=".$is_shuffle."&dirimg="."0"."&tag=".$nowtag."\">"."ディレクトリ代表画像の非表示</a><br>\n";
}

//プレイリストに全追加ボタン
echo "<button type=\"button\" id=\"tag_to_list_btn\">タグ「".$nowtag."」がついた音楽でプレイリストを作成</button><br>\n";

//ページ移動
echo "<div class=\"pageIndex\">\n";
if($page_no < $max_page){
    $next_page = $page_no + 1;
    echo "<a href=\"./".basename(__FILE__)."?rawno=".$page_long."&page=".$next_page."&shuffle=".$is_shuffle."&dirimg=".$dir_img."&tag=".$nowtag."\" class = \"next_btn\"> NEXT(".$next_page."ページ) &gt; </a>  <br>\n";
}
for($i = 1; $i <= $max_page; $i++){
    if($i == $page_no){ //現在のページはリンクを張らない
        echo "<b>{$page_no}</b>  ";
    }else{
        echo "<a href=\"./".basename(__FILE__)."?rawno=".$page_long."&page=".$i."&shuffle=".$is_shuffle."&dirimg=".$dir_img."&tag=".$nowtag."\">".$i."</a>  ";
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
        echo "<tr><td>Empty.</td></tr>\n";
        break;
    }

    echo "<tr><td>";
    $link = substr(realpath($folder),strlen(ROOT));
    $disp_link = htmlspecialchars($link);

    if(is_dir($folder)){
        //フォルダの時
        if($dir_img == 0){
            $key ++;
            echo "<a href = \"./imageshow.php?path=".rawurlencode($link)."\">".$key.": {$disp_link}</a>(画像一覧表示へ移動)";
            $key --;
        }else{
            if(($img = gettopimage($folder,3)) == NULL){
            //画像が存在しないときはディレクトリへのリンクを表示
            $key ++;
            echo "<a href = \"/{$link}\"> ".$key.": {$disp_link}</a> (直近３階層ディレクトリ内画像なし)";
            $key --;
            }
            else{
                $key ++;
                $imglink = substr(realpath($folder."/".$img),strlen(ROOT));
                //画像が存在する場合はimageshowに渡す
                echo "<a href = \"./imageshow.php?path=".rawurlencode($link)."\"><img src=\"/{$imglink}\" ></a>";
                echo "<br><a href = \"./imageshow.php?path=".rawurlencode($link)."\">".$key.": {$disp_link}</a>";
                $key --;
            }
        }
    }elseif(is_picture($folder)){
        //画像の場合は表示して次へ
        $key ++;
        $imglink = substr(realpath($folder),strlen(ROOT));
        echo "<img src=\"/{$imglink}\" >";
        echo "<br>".$key.": {$disp_link}";
        $key --;
    }elseif(is_audio($folder) || is_video($folder)){
        //メディアの場合は再生ページへのリンクを張る
        $key ++;
        echo "<a href = \"./mediaplay.php?path=".rawurlencode($link)."\">".$key.": {$disp_link}</a>（メディア再生ページへ）";
        $key --;
    }else{
        //画像以外のファイル
        $key ++;
        echo "<a href = \"/{$link}\">".$key.": {$disp_link}</a>（ファイル）";
        $key --;
    }
    echo "<br>";
    print_tag($folder,$nowtag);
    echo "</td></tr>";
}
echo "</table><br>\n";

//ページ移動
echo "<div class=\"pageIndex\">\n";
if($page_no < $max_page){
    $next_page = $page_no + 1;
    echo "<a href=\"./".basename(__FILE__)."?rawno=".$page_long."&page=".$next_page."&shuffle=".$is_shuffle."&dirimg=".$dir_img."&tag=".$nowtag."\" class = \"next_btn\"> NEXT(".$next_page."ページ) &gt; </a>  <br>\n";
}
for($i = 1; $i <= $max_page; $i++){
    if($i == $page_no){ //現在のページはリンクを張らない
        echo "<b>{$page_no}</b>  ";
    }else{
        echo "<a href=\"./".basename(__FILE__)."?rawno=".$page_long."&page=".$i."&shuffle=".$is_shuffle."&dirimg=".$dir_img."&tag=".$nowtag."\">".$i."</a>  ";
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
        if(is_picture($foldername."/".$file) != FALSE){
            return $file;
        }
    }
    //見つからなかった場合
    return NULL;
}

//タグ表示（これより前には改行が入っている前提）
function print_tag(String $path,String $nowtag){
    $tags = dir_tag_list($path);
    if(count($tags) == 0){
        return;
    }
    echo "<div class = \"tags\">\n";
    foreach($tags as $tag){
        if(strcmp($tag,$nowtag)==0){
            echo "<b>".$tag."</b>　";
        }
        else{
            echo "<a href=\"./"."taggedlist.php"."?tag=".$tag."\"> ".$tag."</a>　";
        }
    }
    echo "</div>\n";
}
?>
</div>
<div class="footer">
<?php
chdir(ROOT);
echo"<p>タグ「".$nowtag."」<p><br>\n";

echo "<a href = \"./taglist.php\" >タグ一覧を表示</a><br>\n"
?>
</div>
<div id="totop"><a href="#"></a></div>
<script type="text/javascript">
$(document).ready(function (){
    <?php
    echo "var nowtag = \"".$nowtag."\";\n";
    ?>
    $('#tag_to_list_btn').click(function(){
        $.ajax({
            url: './ajax.php',
            type: 'POST',
            data: {
                'mode': "tag_to_list",
                'tag': nowtag
            }
        })
            // Ajaxリクエストが成功した時発動
            .done((data) => {
                console.log(data);
                alert(data);
                location.reload();
            })
            // Ajaxリクエストが失敗した時発動
            .fail((data) => {
                alert("Ajaxエラーが発生しました。");
                console.log(data);
            })
            // Ajaxリクエストが成功・失敗どちらでも発動
            .always((data) => {

            });
    });
});
</script>
</body>
</html>