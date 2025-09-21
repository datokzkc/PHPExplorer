<?php
mb_internal_encoding("UTF-8");
setlocale(LC_ALL, 'ja_JP.UTF-8');
require_once 'db-func.php';
require_once 'common-path.php';
require_once 'file-func.php';

if(isset($_POST['mode'])){
    $mode = $_POST['mode'];
}else{
    echo 'FAIL TO AJAX REQUEST';
    exit();
}
chdir(WEB_ROOT_DIR);
switch($mode){
    case "add":
        if(isset($_POST['path'])){
            $path = $_POST['path'];
        }else{
            echo 'FAIL TO AJAX REQUEST';
            exit();
        }
        if(isset($_POST['tag'])){
            $tag = $_POST['tag'];
        }else{
            echo 'FAIL TO AJAX REQUEST';
            exit();
        }
        if(add_dir_tag($path,$tag) == true){
            $list = dir_tag_list($path);
            if(in_array("全て",$list)== false){
                //「全て」が入っていない場合はこの時に追加
                if(add_dir_tag($path,"全て") == false){
                    echo "Attention:'全て'is not tugged\n";
                }
            }
        }else{
            echo "FAIL";
            exit();
        }
        echo "SUCCESS";
        break;
    case "remove":
        if(isset($_POST['path'])){
            $path = $_POST['path'];
        }else{
            echo 'FAIL TO AJAX REQUEST';
            exit();
        }
        if(isset($_POST['tag'])){
            $tag = $_POST['tag'];
        }else{
            echo 'FAIL TO AJAX REQUEST';
            exit();
        }
        if(rm_dir_tag($path,$tag) == true){
            echo "SUCCESS";
        }else{
            echo "FAIL";
            exit();
        }
        break;
    case "remove_all":
        if(isset($_POST['path'])){
            $path = $_POST['path'];
        }else{
            echo 'FAIL TO AJAX REQUEST';
            exit();
        }
        if(rm_db_dir($path) == true){
            echo "SUCCESS";
        }else{
            echo "FAIL";
            exit();
        }
        break;
    case "tag_remove":
        if(isset($_POST['tag'])){
            $tag = $_POST['tag'];
        }else{
            echo 'FAIL TO AJAX REQUEST';
            exit();
        }
        if(rm_tag($tag) == true){
            echo "SUCCESS";
        }else{
            echo "FAIL";
            exit();
        }
        break;
    case "make_tag":
        if(isset($_POST['tag'])){
            $tag = $_POST['tag'];
        }else{
            echo 'FAIL TO AJAX REQUEST';
            exit();
        }
        if(make_tag($tag) == true){
            echo "SUCCESS";
        }else{
            echo "FAIL";
            exit();
        }
        break;

    case "get_next":
        if(isset($_POST['playmode'])){
            $play_mode = $_POST['playmode'];
        }else{
            echo 'ERR';
            exit();
        }

        if($play_mode == "all_shuffle"){
            $music_list = all_dir_list();
            $next_trc = mt_rand(0,count($music_list)-1);
            $next_path = getRelativePath(realpath($music_list[$next_trc]), WEB_ROOT_DIR);
            echo "/".$next_path;
            exit();
        }

        set_error_handler(function($severity, $message) {
            throw new ErrorException($message);
        });
        try{
            $fp = fopen(pathCombine(DATA_DIR,"/playlist/nowplay.txt") ,"rb");
            $track_no = (int)substr(fgets($fp), 1);
            $music_list = fgetcsv($fp);
            fclose($fp);
            if($track_no == -1){//nomal_list
                $track_no = 0;
                $is_nomal = true;
            }else{
                $is_nomal = false; //repeat list
                if(($track_no + 1) >= count($music_list)){
                    $track_no = 0;
                }else {
                    $track_no ++;
                }
            }
            if($play_mode == "list_shuffle"){
                $track_no = mt_rand(0,count($music_list)-1);
            }
            if($music_list[0] == null){
                throw new ErrorException("リストなし");
            }

            echo $music_list[$track_no];

            //リストを作り直す
            $fp = fopen(pathCombine(DATA_DIR,"/playlist/nowplay.txt"),"wb");

            if($is_nomal == true){
                array_splice($music_list,$track_no,1);
                $write_str = mb_convert_encoding("#"."-1\n", "UTF-8");
                if(fwrite($fp,$write_str) == false){
                    echo "ERR ";
                    exit();
                    //throw new Exception('書き込みエラー');
                }
            }
            else{
                $write_str = mb_convert_encoding("#".$track_no."\n", "UTF-8");
                if(fwrite($fp,$write_str) == false){
                    echo "ERR ";
                    exit();
                    //throw new Exception('書き込みエラー');
                }
            }
            fputcsv($fp,$music_list);
            fclose($fp);

        }catch (Exception $e){
            echo "This is Final.";
        }finally{
            restore_error_handler();
            exit();
        }
        break;
    case "list_add":
        if(isset($_POST['path'])){
            $path = $_POST['path'];
        }else{
            echo 'ERR';
            exit();
        }

        set_error_handler(function($severity, $message) {
            throw new ErrorException($message);
        });

        try{
            $fp = fopen(pathCombine(DATA_DIR,"/playlist/nowplay.txt"),"rb");
            $track_no = (int)substr(fgets($fp), 1);
            $music_list = fgetcsv($fp);
            fclose($fp);
            if($music_list[0]== null){
                throw new ErrorException('曲なし');
            }

        }catch (Exception $e){
            //ファイルオープンエラー
            $track_no = -1; //nomal
            $music_list = array();
        }finally{
            restore_error_handler();
        }
        $music_list[] = $path;

        $fp = fopen(pathCombine(DATA_DIR,"/playlist/nowplay.txt"),"wb");

        $write_str = mb_convert_encoding("#".$track_no."\n", "UTF-8");
        if(fwrite($fp,$write_str) == false){
            echo "ERR ";
            exit();
            //throw new Exception('書き込みエラー');
        }
        fputcsv($fp,$music_list);
        fclose($fp);

        echo "SUCCESS";
        break;
    case "list_include":
        if(isset($_POST['filename'])){
            $filename = $_POST['filename'];
        }else{
            echo 'ERR';
            exit();
        }
    
        set_error_handler(function($severity, $message) {
            throw new ErrorException($message);
        });

        try{
            $fp = fopen(pathCombine(DATA_DIR,"/playlist/".$filename),"rb");
            if(preg_match("/.*\.txt$/i",$filename) == 1){
                $track_no = (int)substr(fgets($fp), 1);
                $music_list = fgetcsv($fp);
                fclose($fp);
                if($music_list[0]== null){
                    throw new ErrorException('曲なし');
                }
            }
            else if(preg_match("/.*\.m3u$/i",$filename) == 1 || preg_match("/.*\.m3u8$/i",$filename) == 1){
                $track_no = -1;
                $music_list = array();
                while(!feof($fp)){
                    //全て絶対パスで表記されていること
                    $line = fgets($fp);
                    $line = rtrim($line,"\r\n\t\0\x0B");
                    $bom = hex2bin('EFBBBF');
                    $line = preg_replace("/^$bom/", '', $line);
                    if(preg_match("/^#.*/",$line) == 0 && $line != ""){
                        $line = getRelativePath($line, WEB_ROOT_DIR);
                        $line = "/".$line;
                        $music_list[] = $line;
                    }
                }
                fclose($fp);
                if(count($music_list) == 0){
                    throw new ErrorException('曲なし');
                }
            }else{
                fclose($fp);
                throw new ErrorException('ERR');
            }
        }catch (Exception $e){
            //ファイルオープンエラー
            echo "ERR\n";
            echo $e->getMessage();
            return;
        }finally{
            restore_error_handler();
        }

        $fp = fopen(pathCombine(DATA_DIR,"/playlist/nowplay.txt"),"wb");

        $write_str = mb_convert_encoding("#".$track_no."\n", "UTF-8");
        if(fwrite($fp,$write_str) == false){
            echo "ERR ";
            exit();
            //throw new Exception('書き込みエラー');
        }
        fputcsv($fp,$music_list);
        fclose($fp);
 
        echo "SUCCESS";
        break;
    case "list_save":
        if(isset($_POST['filename'])){
            $filename = $_POST['filename'];
        }else{
            echo 'ERR';
            exit();
        }
        set_error_handler(function($severity, $message) {
            throw new ErrorException($message);
        });

        try{
            $fp = fopen(pathCombine(DATA_DIR,"/playlist/nowplay.txt"),"rb");
            $track_no = (int)substr(fgets($fp), 1);
            $music_list = fgetcsv($fp);
            fclose($fp);
            if($music_list[0]== null){
                throw new ErrorException('曲なし');
            }

        }catch (Exception $e){
            //ファイルオープンエラー
            $track_no = -1; //nomal
            $music_list = array();
        }finally{
            restore_error_handler();
        }

        $fp = fopen(pathCombine(DATA_DIR,"/playlist/".$filename),"wb");

        $write_str = mb_convert_encoding("#".$track_no."\n", "UTF-8");
        if(fwrite($fp,$write_str) == false){
            echo "ERR ";
            exit();
            //throw new Exception('書き込みエラー');
        }
        fputcsv($fp,$music_list);
        fclose($fp);

        echo "SUCCESS";
        break;

    case "list_rm_trc":
        if(isset($_POST['track'])){
            $rm_track = $_POST['track'];
        }else{
            echo 'ERR';
            exit();
        }
        set_error_handler(function($severity, $message) {
            throw new ErrorException($message);
        });

        try{
            $fp = fopen(pathCombine(DATA_DIR,"/playlist/nowplay.txt"),"rb");
            $track_no = (int)substr(fgets($fp), 1);
            $music_list = fgetcsv($fp);
            fclose($fp);
            if($music_list[0]== null){
                throw new ErrorException('曲なし');
            }

        }catch (Exception $e){
            //ファイルオープンエラー
            echo "ERR";
            return;
        }finally{
            restore_error_handler();
        }

        if($track_no != -1 && $track_no >= $rm_track){
            $track_no --;
        }
        array_splice($music_list,$rm_track,1);

        $fp = fopen(pathCombine(DATA_DIR,"/playlist/nowplay.txt"),"wb");

        $write_str = mb_convert_encoding("#".$track_no."\n", "UTF-8");
        if(fwrite($fp,$write_str) == false){
            echo "ERR ";
            exit();
            //throw new Exception('書き込みエラー');
        }
        fputcsv($fp,$music_list);
        fclose($fp);

        echo "SUCCESS";
        break;

    case "list_repeat":
        set_error_handler(function($severity, $message) {
            throw new ErrorException($message);
        });

        try{
            $fp = fopen(pathCombine(DATA_DIR,"/playlist/nowplay.txt"),"rb");
            $track_no = (int)substr(fgets($fp), 1);
            $music_list = fgetcsv($fp);
            fclose($fp);
            if($music_list[0]== null){
                throw new ErrorException('曲なし');
            }

        }catch (Exception $e){
            //ファイルオープンエラー
            $track_no = -1; //nomal
            $music_list = array();
        }finally{
            restore_error_handler();
        }

        if($track_no == -1){
            $track_no = count($music_list)-1;
        }else{
            $track_no = -1;
        }

        $fp = fopen(pathCombine(DATA_DIR,"/playlist/nowplay.txt"),"wb");

        $write_str = mb_convert_encoding("#".$track_no."\n", "UTF-8");
        if(fwrite($fp,$write_str) == false){
            echo "ERR ";
            exit();
            //throw new Exception('書き込みエラー');
        }
        fputcsv($fp,$music_list);
        fclose($fp);

        echo "SUCCESS";
        break;

    case "list_add_db":
        if(isset($_POST['tag'])){
            $tag = $_POST['tag'];
        }else{
            echo 'ERR';
            exit();
        }
        set_time_limit(60000);
        set_error_handler(function($severity, $message) {
            throw new ErrorException($message);
        });

        try{
            $fp = fopen(pathCombine(DATA_DIR,"/playlist/nowplay.txt"),"rb");
            $track_no = (int)substr(fgets($fp), 1);
            $music_list = fgetcsv($fp);
            fclose($fp);
            if($music_list[0]== null){
                throw new ErrorException('曲なし');
            }
        }catch (Exception $e){
            //ファイルオープンエラー
            echo "ERR";
            return;
        }finally{
            restore_error_handler();
        }
        $false_no = 0;
        foreach($music_list as $path){
            $path = substr($path, 1);
            $path = pathCombine(WEB_ROOT_DIR, $path);
            if(add_dir_tag($path,$tag) == true){
                $list = dir_tag_list($path);
                if(in_array("全て",$list)== false){
                    //「全て」が入っていない場合はこの時に追加
                    if(add_dir_tag($path,"全て") == false){
                        echo "Attention:'全て'is not tugged\n";
                    }
                }
            }else{
                $false_no ++;
                echo $path." tag:".$tag."\n";
            }
        }
        echo "SUCCESS\n";
        if($false_no != 0){
            echo "FAIR: ".$false_no;
        }
        set_time_limit(30);
        break;

    case "tag_to_list":
            if(isset($_POST['tag'])){
                $tag = $_POST['tag'];
            }else{
                $tag = array();
            }
            if(isset($_POST['notag'])){
                $notag = $_POST['notag'];
            }else{
                $notag = array();
            }
        
            if(tagged_dir_list ($tag,$notag) === false){
                $music_list = array();
            }else{
                $dir_list = tagged_dir_list($tag,$notag);
                $music_list = array();
                foreach($dir_list as $line){
                    $line = getRelativePath($line, WEB_ROOT_DIR);
                    $line = "/".$line;
                    if(is_audio($line)){
                        $music_list[] = $line;
                    }
                }
            }
            $track_no = -1;

            $fp = fopen(pathCombine(DATA_DIR,"/playlist/nowplay.txt"),"wb");

            $write_str = mb_convert_encoding("#".$track_no."\n", "UTF-8");
            if(fwrite($fp,$write_str) == false){
                echo "ERR ";
                exit();
                //throw new Exception('書き込みエラー');
            }
            fputcsv($fp,$music_list);
            fclose($fp);
     
            echo "SUCCESS";
            break;

    case "bm_add_site":
        if(isset($_POST['site'])){
            $site = $_POST['site'];
        }else{
            echo 'ERR';
            exit();
        }
        set_error_handler(function($severity, $message) {
            throw new ErrorException($message);
        });

        try{
            $fp = fopen(pathCombine(DATA_DIR,"/bookmark.txt"),"rb");
            $bmlist = array();
            while (!feof($fp)) {
                //改行削除
                $bmsite = str_replace(array("\r", "\n"), '', fgets($fp));
                $bmlist[] = $bmsite;
            }
            // fcloseでファイルを閉じる
            fclose($fp);
        }catch (Exception $e){
            //ファイルオープンエラー
            $bmlist = array();
        }finally{
            restore_error_handler();
        }

        if(array_search($site,$bmlist) != FALSE){
            echo "ERR すでに追加されています".$site;
            exit();
        }
        $bmlist[] = $site;

        $fp = fopen(pathCombine(DATA_DIR,"/bookmark.txt"),"wb");

        foreach($bmlist as $bm){
            if($bm === end($bmlist)){
                if(fwrite($fp,$bm) == false){
                    echo "ERR ";
                    exit();
                    //throw new Exception('書き込みエラー');
                }
            }else{
                if(fwrite($fp,$bm."\n") == false){
                    echo "ERR ";
                    exit();
                    //throw new Exception('書き込みエラー');
                }
            }
        }
        fclose($fp);

        echo "SUCCESS";
        break;
    case "bm_rm_site":
        if(isset($_POST['site'])){
            $site = $_POST['site'];
        }else{
            echo 'ERR';
            exit();
        }
        set_error_handler(function($severity, $message) {
            throw new ErrorException($message);
        });

        try{
            $fp = fopen(pathCombine(DATA_DIR,"/bookmark.txt"),"rb");
            $bmlist = array();
            while (!feof($fp)) {
                //改行削除
                $bmsite = str_replace(array("\r", "\n"), '', fgets($fp));
                $bmlist[] = $bmsite;
            }
            // fcloseでファイルを閉じる
            fclose($fp);
        }catch (Exception $e){
            //ファイルオープンエラー
            $bmlist = array();
        }finally{
            restore_error_handler();
        }

        if(array_search($site,$bmlist) == FALSE){
            echo "ERR can't search".$site;
            exit();
        }
        unset($bmlist[array_search($site,$bmlist)]);
        array_merge($bmlist);

        $fp = fopen(pathCombine(DATA_DIR,"/bookmark.txt"),"wb");

        foreach($bmlist as $bm){
            if($bm === end($bmlist)){
                if(fwrite($fp,$bm) == false){
                    echo "ERR ";
                    exit();
                    //throw new Exception('書き込みエラー');
                }
            }else{
                if(fwrite($fp,$bm."\n") == false){
                    echo "ERR ";
                    exit();
                    //throw new Exception('書き込みエラー');
                }
            }
        }
        fclose($fp);

        echo "SUCCESS";
        break;

    case "search_query_add":
        if(isset($_POST['name'])){
            $name = $_POST['name'];
        }else{
            echo 'FAIL TO AJAX REQUEST';
            exit();
        }
        if(isset($_POST['query'])){
            $query = $_POST['query'];
        }else{
            echo 'FAIL TO AJAX REQUEST';
            exit();
        }
        if(add_search_query($name,$query) !== true){
            echo "FAIL";
            exit();
        }
        echo "SUCCESS";
        break;
    case "search_query_update":
        if(isset($_POST['name'])){
            $name = $_POST['name'];
        }else{
            echo 'FAIL TO AJAX REQUEST';
            exit();
        }
        if(isset($_POST['query'])){
            $query = $_POST['query'];
        }else{
            echo 'FAIL TO AJAX REQUEST';
            exit();
        }
        if(update_search_query($name,$query) !== true){
            echo "FAIL";
            exit();
        }
        echo "SUCCESS";
        break;
    case "search_query_remove":
            if(isset($_POST['name'])){
                $name = $_POST['name'];
            }else{
                echo 'FAIL TO AJAX REQUEST';
                exit();
            }
            if(rm_search_query($name) !== true){
                echo "FAIL";
            }else{
                echo "SUCCESS";
                exit();
            }
        break;

}
?>