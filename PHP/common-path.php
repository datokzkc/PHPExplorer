<?php

mb_internal_encoding("UTF-8");

//相対パスの抜き出し(頭には常にスラッシュは入らない)
function getRelativePath(string $absolutePath, string $basePath){
    // パスを正規化して、末尾のスラッシュを削除
    $absolutePath = rtrim(str_replace(['\\', '//'], '/', $absolutePath), '/');
    $basePath = rtrim(str_replace(['\\', '//'], '/', $basePath), '/');

    if (mb_strpos($absolutePath, $basePath) !== 0) {
        throw new Exception("basePath外のパスが指定されました。");
    }

    $relativePath = mb_substr($absolutePath, mb_strlen($basePath));

    // 先頭にスラッシュがあれば削除
    if (mb_strpos($relativePath, '/') === 0) {
        $relativePath = mb_substr($relativePath, 1);
    }
    return $relativePath;
}

function pathCombine(string $dir, string $file){
    return rtrim($dir, '\\/') . DIRECTORY_SEPARATOR . ltrim($file, '\\/');
}


function pathSlashEncode(string $path){
    return str_replace(DIRECTORY_SEPARATOR, '/', $path);
}

function pathSlashDecode(string $path){
    return str_replace('/', DIRECTORY_SEPARATOR , $path);
}

function dirPathNormalize(string $path){
    $path = pathSlashEncode($path);
    $path = rtrim($path, '/');
    $path = $path. '/';
    return $path;
}

function webPathEncode(string $path){

    $path = pathSlashEncode($path);

    // パスの各セグメントを個別にURLエンコード
    $segments = explode('/', $path);
    $encodedSegments = array_map('rawurlencode', $segments);
    $path = implode('/', $encodedSegments);

    return $path;
}



//ディレクトリは最後にスラッシュを入れる
define("WEB_ROOT_DIR", dirPathNormalize($_SERVER['DOCUMENT_ROOT']) );

$org_dir = getcwd();

chdir(__DIR__);

define("BASE_DIR", dirPathNormalize(realpath("../")) );
define("BASE_PATH", "/" . getRelativePath(BASE_DIR, WEB_ROOT_DIR));

define("JQUERY_FILE", dirPathNormalize(realpath("../jquery-3.5.0.js")) );
define("JQUERY_FILE_PATH", "/" . getRelativePath(JQUERY_FILE, WEB_ROOT_DIR));

define("DATA_DIR", dirPathNormalize(realpath("../data")) );

chdir($org_dir);

?>