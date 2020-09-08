<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>
ブックマーク一覧
</title>
<link rel="stylesheet" type="text/css" href="/HTTP/CSS/taglist.css">
<!-- jQuery -->
<script type="text/javascript" src="/HTTP/jquery-3.5.0.js"></script>
<script type="text/javascript" src="/HTTP/javascript/totop.js"></script>
</head>

<body>
<div class ="header">
<?php
include 'root_dir.php';

chdir(ROOT); //ディレクトリの場所の初期化

echo"<h1>ブックマーク一覧</h1>\n";

echo "<p><a href=\"#\" onClick=\"history.back(); return false;\">前のページにもどる</a></p>\n";

?>
</div>
<div class ="covershow">
<?php
/*
$path = getcwd();
*/

//引数取得
if(isset($_GET['shuffle'])){
    $is_shuffle = $_GET['shuffle'];
}else{
    $is_shuffle = 0; //初期値はシャッフルオフ(0)
}

set_error_handler(function($severity, $message) {
    throw new ErrorException($message);
});
try{
    // fopenでファイルを開く（'r'は読み込みモードで開く）
    $fp = fopen("./HTTP/data/bookmark.txt", 'rb');

    while (!feof($fp)) {
        $bmlist[] = fgets($fp);
    }
 
    // fcloseでファイルを閉じる
    fclose($fp);
}catch (Exception $e){
    //何もないとき
    $bmlist = array();
    $bmlist[] = "\n--@//nothing";
}finally{
    restore_error_handler();
}

if($is_shuffle == 1){
    shuffle($bmlist);
}else{
    //並べ替えはしない
}
$bmlist = array_values($bmlist);

echo "<h2>合計：".count($bmlist)."サイト</h2>\n";

echo "<p><b>並び順: ";
if($is_shuffle == 1){
    echo "シャッフル";
}else{
    echo "通常";
}
echo "</b></p>\n";

//並び替え選択
if($is_shuffle == 0){
    echo "<a href=\"./".basename(__FILE__)."?shuffle="."1"."\">"."シャッフルする</a><br>\n";
}else{
    echo "<a href=\"./".basename(__FILE__)."?shuffle="."0"."\">"."通常の並びへ戻す</a><br>\n";
}


//一覧リスト生成
echo "<table>\n";
$site_no = 0;
foreach($bmlist as $bmsite){
    if(strcmp($bmsite,"\n--@//nothing") == 0){
        echo "<tr><td>Empty.</td></tr>\n";
        break;
    }
    //改行削除
    $bmsite = str_replace(array("\r", "\n"), '', $bmsite);
    echo "<tr><td>";  
    $sitetitle = getPageTitle($bmsite);
    echo "<a href=\"".$bmsite."\"><img src=\"https://www.google.com/s2/favicons?domain_url=".$bmsite."\">".$sitetitle." </a></td>";
    echo "<td><button type=\"button\" id=\"btn_".$site_no."\">削除</button></td>";
    echo "</tr>\n";
    $site_no ++;
}
echo "</table><br>\n";

function getPageTitle($url){
    static $regex = '@<title>([^<]++)</title>@i';
    static $order = 'ASCII,JIS,UTF-8,CP51932,SJIS-win';
    static $ch;
    if(!$ch){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    $html = mb_convert_encoding(curl_exec($ch), 'UTF-8', $order);
    return preg_match($regex, $html, $m) ? $m[1] : '';
}
?>
</div>
<p id="info_text"></p>
<input id="add_bm_text" type="text" name="add_txt_bm" hidden><br>
<input id="add_bm_btn" type="button" value="新規追加" />
<input id="enter_btn" type="button" value="決定" hidden>
<input id="cancel_btn" type="button" value="キャンセル" hidden>
<div class="footer">
<?php
chdir(ROOT);
echo"<p>ブックマーク一覧</p><br>\n";
echo "<a href = \"./db_all.php\" >データベースに登録されているディレクトリ一覧</a><br>\n";
?>
</div>
<div id="totop"><a href="#"></a></div>
<script type="text/javascript">
$(document).ready(function (){

    var $add = $('#add_bm_btn');
    var $text = $('#add_bm_text');
    var $enter = $('#enter_btn');
    var $cancel = $('#cancel_btn');
    var $info = $('#info_text');

    $add.click(function(){
        $info.text("追加するサイトを入力してください\n");
        $info.show();
        $text.show();
        $text.val("");
        $enter.show();
        $cancel.show();
        $add.hide();
    });

    $cancel.click(function(){
        $info.hide();
        $add.show();
        $text.hide();
        $enter.hide();
        $cancel.hide();
    })

    $enter.click(function(){
        var site = $text.val();
        if (site == ""){
                alert("何か入力してください");
                return;
        }
        $.ajax({
            url: './ajax.php',
            type: 'POST',
            data: {
                'mode': "bm_add_site",
                'site': site
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
    })

<?php
$bm_no = 0;
foreach($bmlist as $bmsite){
    //改行削除
    $bmsite = str_replace(array("\r", "\n"), '', $bmsite);
    echo "$('#btn_".$bm_no."').click(function (){\n";
    echo "if(confirm('削除されます。\\n本当によろしいですか？')){\n";
    echo "$.ajax({\n";
    echo "url: './ajax.php',type:'POST',data:{'mode':'bm_rm_site','site':'".$bmsite."'}\n";
    echo "})\n";
    echo "//ajax成功時\n";
    echo ".done((data) => {\n";
    echo "console.log(data);\nalert(data);\nlocation.reload();\n";
    echo "})\n";
    echo "//ajax失敗時\n";
    print ".fail((data) => {\n";
    echo "alert('Ajax通信エラー');\n";
    echo "});\n";
    echo "}else{\n";
    echo "//nothing do.\n";
    echo "}\n});";
    $bm_no++;
}
?>
});
</script>

</body>
</html>