<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>
タグ一覧
</title>
<link rel="stylesheet" type="text/css" href="../CSS/taglist.css">
<!-- jQuery -->
<script type="text/javascript" src="../jquery-3.5.0.js"></script>
<script type="text/javascript" src="../javascript/totop.js"></script>
</head>

<body>
<div class ="header">
<?php
include 'root_dir.php';
include 'db-func.php';
include 'file-func.php';

chdir(ROOT); //ディレクトリの場所の初期化

echo"<h1>タグ一覧</h1>\n";

echo "<a href = \"./db_all.php\" >データベースに登録されているディレクトリ一覧</a><br>\n";

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
if(isset($_GET['mode'])){
    $mode = $_GET['mode'];
}else{
    $mode = 0; //初期値は詳細非表示(0)
}


$taglist = all_tag_list();
//隠しファイルの削除
//$list = preg_grep('/^\..*/',$list,PREG_GREP_INVERT);
chdir(ROOT); // ディレクトリ移動

if($taglist == false){
    //何もないとき
    $taglist = array();
    $taglist[] = "\n--@//nothing";
}
if($is_shuffle == 1){
    shuffle($taglist);
}else{
    //並べ替えはしない
}
$taglist = array_values($taglist);

echo "<h2>合計：".count($taglist)."種類</h2>\n";

echo "<p><b>並び順: ";
if($is_shuffle == 1){
    echo "シャッフル";
}else{
    echo "通常（５０音順）";
}
echo "　表示モード: ";
if($mode == 0){
    echo "タグ名のみ";
}else if($mode ==1){
    echo "タグ名とコンテンツ数";
}else if($mode ==2){
    echo "タグ名とコンテンツ数とランダムコンテンツ画像";
}
echo "</b></p>\n";

//並び替え選択
if($is_shuffle == 0){
    echo "<a href=\"./".basename(__FILE__)."?shuffle="."1"."&mode=".$mode."\">"."シャッフルする</a><br>\n";
}else{
    echo "<a href=\"./".basename(__FILE__)."?shuffle="."0"."&mode=".$mode."\">"."通常の並びへ戻す</a><br>\n";
}

//ディレクトリ画像オンオフ
if($mode != 0){
    echo "<a href=\"./".basename(__FILE__)."?shuffle=".$is_shuffle."&mode="."0"."\">"."タグ名のみ表示</a><br>\n";
}if($mode >= 0 && $mode != 1){
    echo "<a href=\"./".basename(__FILE__)."?shuffle=".$is_shuffle."&mode="."1"."\">"."タグ名と各タグのコンテンツ数を表示</a><br>\n";
}if($mode == 1){
    echo "<a href=\"./".basename(__FILE__)."?shuffle=".$is_shuffle."&mode="."2"."\">"."各タグのランダムコンテンツも表示</a><br>\n";
}


//一覧リスト生成
echo "<table>\n";
$tag_no = 0;
foreach($taglist as $tag){
    if(strcmp($tag,"\n--@//nothing") == 0){
        echo "<tr><td>Empty.</td></tr>\n";
        break;
    }
    echo "<tr><td>";
    if($mode == 2){
        if(($tagdirs = tagged_dir_list([$tag],[])) != false){
            $listlong = count($tagdirs);
            $top = mt_rand(0,$listlong-1);
            if(is_dir($tagdirs[$top]) == FALSE){
                if(is_audio($tagdirs[$top]) || is_video($tagldirs[$top])){
                    //メディアの場合はリンクで表示
                    $medialink = substr(realpath($tagdirs[$top]), strlen(ROOT)); 
                    echo "<a href = \"./mediaplay.php?path=".rawurlencode($medialink)."\">Media:".basename($tagdirs[$top])."</a><br>";
                }
            }
            elseif(($img = gettopimage($tagdirs[$top],3)) == NULL){
                //画像が存在しないときは画像表示はあきらめる
            }
            else{
                $imglink = substr(realpath($tagdirs[$top]."/".$img),strlen(ROOT));
                //画像が存在する場合はimageshowに渡す
                $dirlink = substr(realpath($tagdirs[$top]), strlen(ROOT)); 
                echo "<a href = \"./imageshow.php?path=".rawurlencode($dirlink)."\"><img src=\"/{$imglink}\" ></a><br>";
            }
        }else{
            $listlong = 0;
        }
    }
    
    echo "<a href=\"./taggedlist.php?tag[]=".rawurlencode($tag)."\"> ".$tag." </a>";

    if($mode == 1){
        if(($tagdirs = tagged_dir_list([$tag],[])) != false){
            $listlong = count($tagdirs);
        }else{
            $listlong = 0;
        }
    }
    if($mode > 0){
        echo "　コンテンツ数：".$listlong;
    }
    echo "　　<button type=\"button\" id=\"btn_".$tag_no."\">タグの削除</button>";
    echo "</td></tr>\n";
    $tag_no ++;
}
echo "</table><br>\n";


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
?>
</div>
<p id="info_text"></p>
<input id="add_tag_text" type="text" name="add_txt_tag" hidden><br>
<input id="add_tag_btn" type="button" value="新規タグ作成" />
<input id="enter_btn" type="button" value="決定" hidden>
<input id="cancel_btn" type="button" value="キャンセル" hidden>
<div class="footer">
<?php
chdir(ROOT);
echo"<p>タグ一覧</p><br>\n";
echo "<a href = \"./db_all.php\" >データベースに登録されているディレクトリ一覧</a><br>\n";
?>
</div>
<div id="totop"><a href="#"></a></div>
<script type="text/javascript">
$(document).ready(function (){

    var $add = $('#add_tag_btn');
    var $text = $('#add_tag_text');
    var $enter = $('#enter_btn');
    var $cancel = $('#cancel_btn');
    var $info = $('#info_text');

    $add.click(function(){
        $info.text("追加するタグを入力してください\n");
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
        var tag = $text.val();
        if (tag == "自分で入力(新規追加)"){
                alert("そのタグは追加できません");
                return;
        }
        if (tag == ""){
                alert("何か入力してください");
                return;
        }
        $.ajax({
            url: './ajax.php',
            type: 'POST',
            data: {
                'mode': "make_tag",
                'tag': tag
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
$tag_no = 0;
foreach($taglist as $tag){
    echo "$('#btn_".$tag_no."').click(function (){\n";
    echo "if(confirm('タグ「".$tag."」は削除されます。\\n本当によろしいですか？')){\n";
    echo "$.ajax({\n";
    echo "url: './ajax.php',type:'POST',data:{'mode':'tag_remove','tag':'".$tag."'}\n";
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
    $tag_no++;
}
?>
});
</script>

</body>
</html>