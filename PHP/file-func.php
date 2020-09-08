<?php
/*
関数一覧
is_picture($file)
is_audio($file)
is_video($file)
全てtrue falseで返す
*/
function is_picture(String $file){
    if(preg_match("/.*\.jpg$/i",$file) == 1){
        return true;
    }
    else if(preg_match("/.*\.jpeg$/i",$file) == 1){
        return true;
    }
    else if(preg_match("/.*\.png$/i",$file) == 1){
        return true;
    }
    else if(preg_match("/.*\.gif$/i",$file) == 1){
        return true;
    }
    else if(preg_match("/.*\.bmp$/i",$file) == 1){
        return true;
    }
    else if(preg_match("/.*\.svg$/i",$file) == 1){
        return true;
    }
    else if(preg_match("/.*\.webp$/i",$file) == 1){
        return true;
    }
    else if(preg_match("/.*\.ico$/i",$file) == 1){
        return true;
    }
    else{
        return false;
    }
}
function is_audio(String $file){
    if(preg_match("/.*\.mp3$/i",$file) == 1){
        return true;
    }
    else if(preg_match("/.*\.m4a$/i",$file) == 1){
        return true;
    }
    else if(preg_match("/.*\.mp4$/i",$file) == 1){
        return true;
    }
    else if(preg_match("/.*\.flac$/i",$file) == 1){
        return true;
    }
    else if(preg_match("/.*\.alac$/i",$file) == 1){
        return true;
    }
    else if(preg_match("/.*\.wav$/i",$file) == 1){
        return true;
    }
    else if(preg_match("/.*\.wave$/i",$file) == 1){
        return true;
    }
    else if(preg_match("/.*\.aac$/i",$file) == 1){
        return true;
    }
    else if(preg_match("/.*\.ogg$/i",$file) == 1){
        return true;
    }
    else if(preg_match("/.*\.wma$/i",$file) == 1){
        return true;
    }
    else if(preg_match("/.*\.omg$/i",$file) == 1){
        return true;
    }
    else if(preg_match("/.*\.oma$/i",$file) == 1){
        return true;
    }
    else{
        return false;
    }
}
function is_video(String $file){
    if(preg_match("/.*\.mp4$/i",$file) == 1){
        return true;
    }
    else if(preg_match("/.*\.m4v$/i",$file) == 1){
        return true;
    }
    else if(preg_match("/.*\.mov$/i",$file) == 1){
        return true;
    }
    else if(preg_match("/.*\.mpg$/i",$file) == 1){
        return true;
    }
    else if(preg_match("/.*\.mpeg$/i",$file) == 1){
        return true;
    }
    else if(preg_match("/.*\.mpeg2$/i",$file) == 1){
        return true;
    }
    else if(preg_match("/.*\.avi$/i",$file) == 1){
        return true;
    }
    else if(preg_match("/.*\.wmv$/i",$file) == 1){
        return true;
    }
    else if(preg_match("/.*\.flv$/i",$file) == 1){
        return true;
    }
    else if(preg_match("/.*\.webm$/i",$file) == 1){
        return true;
    }
    else{
        return false;
    }
}
?>