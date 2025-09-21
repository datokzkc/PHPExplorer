<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>
画像表示(スライドショー型)
</title>
<?php
setlocale(LC_ALL, 'ja_JP.UTF-8');
require_once 'common-path.php';
require_once 'db-func.php';
require_once 'file-func.php';
require_once 'search-class.php';
echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"".webPathEncode(pathCombine(BASE_PATH,"/CSS/slideshow.css"))."\">\n";
echo "<!-- jQuery -->\n";
echo "<script type=\"text/javascript\" src=\"".webPathEncode(JQUERY_FILE_PATH)."\"></script>\n";
echo "<script type=\"text/javascript\" src=\"".webPathEncode(pathCombine(BASE_PATH,"/javascript/totop.js"))."\"></script>\n";
echo "<script type=\"text/javascript\" src=\"".webPathEncode(pathCombine(BASE_PATH,"/javascript/tagcont.js"))."\"></script>\n";
?>
</head>

<body>

<?php
if(isset($_GET['path'])){
    $path = $_GET['path'];
}else{
    $path = "."; //設定されていないときはルートディレクトリのパス
}
if(isset($_GET['mode'])){
    $mode = $_GET['mode'];
}else{
    $mode = "dir"; //設定されていない場合はディレクトリ直下のみ
}
if(isset($_GET['page'])){
    $page = $_GET['page'];
}else{
    $page = 1; //設定されていない場合は１ページから
}
chdir(WEB_ROOT_DIR); //ディレクトリの場所の初期化
?>
<div class ="imageshow">
<?php

$list = null;
if($mode == "all"){
    $list = list_files($path);
}else if($mode == "dir"){
    $list = scandir($path);
}else{
    echo "エラーが発生しました。(mode未設定)¥n";
    exit();
}
if($list == null){
    echo "ファイルを得ることができませんでした。";
    exit();
}
//隠しファイルの削除
$list = preg_grep('/^\..*/u',$list,PREG_GREP_INVERT);
$list = preg_grep('/^.*\\._.*/u',$list,PREG_GREP_INVERT);
chdir($path);
$filelist = array();
foreach($list as $file){
    if(is_dir($file) == false){
        if(is_picture($file) == true){
            $filelist[] = $file;
        }
    }
}
if(count($filelist) <= 0){
    echo "画像データがありませんでした。";
    exit();
}
if($page > count($filelist) || $page <= 0){
    echo "引数pageの設定がおかしいです。";
    exit();
}
natsort($filelist);
$list = array_values($filelist);

//タグ表示(非表示)、消したらめんどくさいのでとりあえず

chdir(WEB_ROOT_DIR);
echo "<div class=\"tags\" hidden>\n<div class=\"tagshow\">\n";
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
    echo "<option value=\"".htmlspecialchars($tag)."\"> ".htmlspecialchars($tag)." </option>\n";
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

$link = getRelativePath(realpath($list[($page - 1)]),WEB_ROOT_DIR);
echo "<div class=\"imagebox\">\n";
echo "<a href=\"./slideshow.php?mode=".$mode."&page=".($page + 1)."&path=".rawurlencode($path)."\"><img src=\"/".rawurlencode($link)."\" ></a>\n";
echo "</div>\n";

echo "<div class= \"center\"><span id=\"nowpage\">".$page."</span>/".count($list)."</div><br>\n";

echo "<div class=\"contloller\">\n";
echo "<div class=\"left\"><a id=\"prev\" href=\"./slideshow.php?mode=".$mode."&page=".($page - 1)."&path=".rawurlencode($path)."\">&lt;前の画像へ</a></div>\n";
echo "<div class=\"right\"><a id=\"next\" href=\"./slideshow.php?mode=".$mode."&page=".($page + 1)."&path=".rawurlencode($path)."\">次の画像へ&gt;</a></div>\n";
echo "</div>\n";

echo "<br>\n";
echo "<p><a href=\"#\" onClick=\"history.back(); return false;\">前のページにもどる</a></p>\n";

chdir(WEB_ROOT_DIR);

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
        $list[] = mb_substr($pathname,mb_strlen($dir)+1);
    }
    return $list;
}
?>
</div>
<div class="footer">
<?php
chdir(WEB_ROOT_DIR);
$name = basename(realpath($path));
echo"<p>ディレクトリ名「{$name}」</p>\n";
//タグ表示
echo "<div class=\"tags\">\n<div class=\"tagshow\">\n";
chdir(WEB_ROOT_DIR);
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
echo "<p><a href=\"#\" onClick=\"history.back(); return false;\">前のページにもどる</a></p>\n";
?>
</div>
<div id="totop"><a href="#"></a></div>
<script type="text/javascript">
$(document).ready(function (){
    var $next_a = $('.imagebox a');
    var $now_img = $('.imagebox img');
    var $prev_img_btn = $('#prev');
    var $next_img_btn = $('#next');
    var $page_disp = $('#nowpage');
<?php
echo "var nowpage = ".$page.";\n";
echo "const maxpage = ".count($list).";\n";
echo "const nowmode = " . json_encode($mode) . ";\n";

echo "const this_path = " . json_encode(rawurlencode($path)) . ";\n";

// 画像リスト配列をJSONで出力
chdir($path);
$img_urls = [];
foreach($list as $img){
    $link = getRelativePath(realpath($img),WEB_ROOT_DIR);
    $img_urls[] = rawurlencode($link);
}
echo "const img_list = " . json_encode($img_urls) . ";\n";
?>

    // --- プリロードキャッシュ ---
    var preloadCache = {};

    function preloadImages(centerIndex) {
        var preloadDistance = 2;
        for (var i = centerIndex - preloadDistance; i <= centerIndex + preloadDistance; i++) {
            if (i < 1 || i > maxpage) continue;
            var idx = i - 1;
            var src = "/" + img_list[idx];
            if (!preloadCache[src]) {
                var img = new Image();
                img.src = src;
                preloadCache[src] = true;
            }
        }
    }


    function next_img(){
        if(nowpage >= maxpage){
            alert("これが最終ページです。");
        }
        else{
            if(nowpage <= 1){
                $prev_img_btn.show();
            }
            nowpage++;
            $now_img.attr('src',"/"+img_list[(nowpage-1)]);
            $next_a.attr('href',"./slideshow.php?mode="+nowmode+"&page="+(nowpage+1)+"&path="+this_path);
            $next_img_btn.attr('href',"./slideshow.php?mode="+nowmode+"&page="+(nowpage+1)+"&path="+this_path);
            $prev_img_btn.attr('href',"./slideshow.php?mode="+nowmode+"&page="+(nowpage-1)+"&path="+this_path);
            $page_disp.text(nowpage);
            if(nowpage >=maxpage){
                $next_img_btn.hide();
                $next_a.removeAttr('href');
            }
            preloadImages(nowpage);
        }
    }

    function prev_img(){
        if(nowpage <= 1){
            alert("これが最初のページです。");
        }
        else{
            if(nowpage >= maxpage){
                $next_img_btn.show();
            }
            nowpage--;
            $now_img.attr('src',"/"+img_list[(nowpage-1)]);
            $next_a.attr('href',"./slideshow.php?mode="+nowmode+"&page="+(nowpage+1)+"&path="+this_path);
            $next_img_btn.attr('href',"./slideshow.php?mode="+nowmode+"&page="+(nowpage+1)+"&path="+this_path);
            $prev_img_btn.attr('href',"./slideshow.php?mode="+nowmode+"&page="+(nowpage-1)+"&path="+this_path);
            $page_disp.text(nowpage);
            if(nowpage <= 1){
                $prev_img_btn.hide();
            }
            preloadImages(nowpage);
        }
    }


    //画面の初期状態によって表示の調整
    if(nowpage <= 1){
        $prev_img_btn.hide();
    }
    if(nowpage >=maxpage){
        $next_img_btn.hide();
        $next_a.removeAttr('href');
    }

    $next_a.click(function(){
        next_img();
        return false;
    });

    $prev_img_btn.click(function(){
        prev_img();
        return false;
    });

    $next_img_btn.click(function(){
        next_img();
        return false;
    });

    $(window).keydown(function(e){
	    switch(e.keyCode){
            case 39: //右
                next_img();
                return false;
                break;
            case 37: //左
                prev_img();
                return false;
                break;
            case 32: //スペース
                next_img();
                return false;
                break;
            case 8: //BS
                prev_img();
                return false;
                break;
            case 13: //Enter
                next_img();
                return false;
                break;
        }
    });

    //初期表示時に周辺の画像をプリロード
    preloadImages(nowpage);

});
</script>
</body>
</html>