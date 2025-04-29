<?php
include_once("db-func.php");
/*
検索クエリを処理するクラス
public のメソッドは以下の通り
        set_query_str(String $query_str) : void; //検索クエリをセットする
        get_query_str() : String; //検索クエリを取得する
        set_target_str(String $target_str) : void; //検索対象文字列をセットする
        get_target_str() : String; //検索対象文字列を取得する
        is_match() : bool; //検索条件に一致しているかを判定する
        filter_list_by_query(array $list) : array; //リストの中から検索条件に一致するものをフィルタリングする
*/
class SearchClass{
    protected $query_str = "";
    protected $query_tokens = array();
    protected $eval_tokens = array(); 
    protected $is_need_tag = false; //検索条件にtag:があり、タグ情報が必要かどうか

    protected $target_str = ""; //検索対象文字列

    protected $target_tags = false; //検索対象のタグ一覧
    protected $tagged_cache_list = array();

    const DEFAULT_OPERATOR = "&&"; //検索条件間のデフォルトの演算子

    const KEEP_QUERY_MODE = 0; //検索クエリの更新が少ない場合
    const KEEP_TARGET_MODE = 1; //検索対象の更新が少ない場合
    protected $cache_mode = self::KEEP_QUERY_MODE; //データキャッシュのモード

    //コンストラクタ 
    public function __construct ($cache_mode = self::KEEP_QUERY_MODE){
        $this->cache_mode = $cache_mode; //キャッシュモードの設定
        //初期化
        $this->query_str = "";
        $this->query_tokens = array();
        $this->eval_tokens = array();
        $this->target_str = "";
        $this->target_tags = false;
    }

    public function set_query_str(String $query_str){
        $this->tagged_cache_list = array(); //初期化
        $this->query_str = $query_str;
        if($this->query_anarysis() === false){
            trigger_error("検索クエリの解析に失敗しました",E_USER_ERROR);
        }
    }

    public function get_query_str(){
        return $this->query_str;
    }

    public function set_target_str(String $target_str){
        $this->target_tags = false; //初期化
        $this->target_str = $target_str;
        if ($this->is_need_tag === true && $this->cache_mode === self::KEEP_TARGET_MODE){
            //キャッシュモードがKEEP_TARGET_MODEの場合は、target_strの更新が少ないので、
            //tag情報が必要な場合は、タグ情報を取得しておく
            $this->target_tags = dir_tag_list($this->target_str);
        }
    }

    public function get_target_str(){
        return $this->target_str;
    }

    //　target_strがquery_strの検索条件に一致しているかを判定する
    public function is_match(){
        if ($this->query_str === ""){
            trigger_error("検索クエリが設定されていません",E_USER_WARNING);
            return false;
        }
        if ($this->target_str === ""){
            trigger_error("検索対象が設定されていません",E_USER_WARNING);
            return false;
        }
        if ($this->is_ready_eval() === false){
            trigger_error("eval文の事前処理に不足があります",E_USER_WARNING);
            return false;
        }
        //eval関数を使って検索条件を評価する
        $eval_query = "return ".implode(" ",$this->eval_tokens).";";
        return eval($eval_query);
    }

    //リストの中から検索条件に一致するものをフィルタリングする
    public function filter_list_by_query(array $list){
        $result = array();
        foreach ($list as $item){
            $this->set_target_str($item);
            if ($this->is_match()){
                $result[] = $item;
            }
        }
        return $result;
    }

    //eval実行前の準備が完了しているかを確認する
    protected function is_ready_eval(){
        if ($this->is_need_tag === true && $this->cache_mode === self::KEEP_TARGET_MODE && $this->target_tags === false){
            //tag情報が必要な場合は、タグ情報を取得しておく
            $this->target_tags = dir_tag_list($this->target_str);
        }
        if (in_array(false,$this->query_tokens,true) === true){
            //eval文の準備ができていない場合はfalse
            return false;
        }
        return true;
    }

    //検索クエリに対する解析・処理を行う
    protected function query_anarysis(){
        if ($this->query_separate_tokens() === false){
            //クエリの解析に失敗した場合はfalseを返す
            return false;
        }
        if ($this->set_specify_eval() === false){
            //クエリの解析に失敗した場合はfalseを返す
            return false;
        }
        if ($this->set_findstr_eval() === false){
            //クエリの解析に失敗した場合はfalseを返す
            return false;
        }
        return true;
    }

    //検索クエリをトークンごとに分解する
    //AND,OR,NOT,()についてはここで処理する
    protected function query_separate_tokens(){

        //正規表現でトークンごとに分解
        $pattern = "(?:tag:)?(?:\\\"[^\\\"]*\\\"|\\'[^\\']*\\')|\\(|\\)|[^\\s　\\(\\)]+|[\\s　]+";
        if (!preg_match("/(".$pattern.")+/u",$this->query_str)){
            $this->query_tokens = array();
            $this->eval_tokens = array();
            //失敗として返す
            return false;
        }
        preg_match_all("/".$pattern."/u",$this->query_str,$matches);
        $raw_query_tokens = $matches[0];

        $is_need_next_operator = false;
        $this->query_tokens = array();
        $this->eval_tokens = array();
        foreach ($raw_query_tokens as $raw_query_token){
            //空白の場合はスキップ
            if (preg_match("/^[\\s　]+$/u",$raw_query_token)){
                continue;
            }
            if ($raw_query_token === "("){
                if ($is_need_next_operator){
                    //演算子が必要なところに演算子が入っていなかったらデフォルトを入れる
                    $this->query_tokens[] = self::DEFAULT_OPERATOR;
                    $this->eval_tokens[] = self::DEFAULT_OPERATOR;
                }
                $this->query_tokens[] = $raw_query_token;
                $this->eval_tokens[] = $raw_query_token;
                $is_need_next_operator = false;
                continue;
            }
            if ($raw_query_token === ")"){
                $this->query_tokens[] = $raw_query_token;
                $this->eval_tokens[] = $raw_query_token;
                $is_need_next_operator = true;
                continue;
            }
            //大文字小文字の区別しない場合に使用する
            $low_raw_query_token = mb_strtolower($raw_query_token);
            if($low_raw_query_token === "not"){
                if ($is_need_next_operator){
                    //演算子が必要なところに演算子が入っていなかったらデフォルトを入れる
                    $this->query_tokens[] = self::DEFAULT_OPERATOR;
                    $this->eval_tokens[] = self::DEFAULT_OPERATOR;
                }
                $this->query_tokens[] = "!";
                $this->eval_tokens[] = "!";
                $is_need_next_operator = false;
                continue;
            }
            if($low_raw_query_token === "and"){
                $this->query_tokens[] = "&&";
                $this->eval_tokens[] = "&&";
                $is_need_next_operator = false;
                continue;
            }
            if($low_raw_query_token === "or"){
                $this->query_tokens[] = "||";
                $this->eval_tokens[] = "||";
                $is_need_next_operator = false;
                continue;
            }
            else{  //演算子でない場合
                if ($is_need_next_operator){
                    //演算子が必要なところに演算子が入っていなかったらデフォルトを入れる
                    $this->query_tokens[] = self::DEFAULT_OPERATOR;
                    $this->eval_tokens[] = self::DEFAULT_OPERATOR;
                }
                $this->query_tokens[] = $raw_query_token;
                //演算子ではなければeval条件はひとまずfalse
                $this->eval_tokens[] = false;
                //次のトークンは演算子が必要
                $is_need_next_operator = true;
                continue;
            }

        }

        return true;
    }

    //属性検索条件の場合の処理をeval文にセットする
    protected function set_specify_eval(){
        $this->is_need_tag = false;
        $this->tagged_cache_list = array(); //キャッシュされたタグ情報
        for($i = 0; $i < count($this->eval_tokens); $i++){
            $this->tagged_cache_list[$i] = false; //タグキャッシュはfalsseで無効化
            if ($this->eval_tokens[$i] === false){
                //"tag:"条件に対する処理
                if (0 === mb_stripos($this->query_tokens[$i], "tag:")){
                    $this->is_need_tag = true; //tag:がある場合はタグ情報が必要
                    if ($this->cache_mode === self::KEEP_QUERY_MODE){
                        if( false === ($this->tagged_cache_list[$i] = tagged_dir_list([trim(mb_substr($this->query_tokens[$i],4), "\"'")],[]))){
                            $this->tagged_cache_list[$i] = array(); //タグが見つからなかった場合は空の配列をセット
                        }
                        $this->eval_tokens[$i] = "(in_array(\$this->target_str,\$this->tagged_cache_list[".strval($i)."]) === true ? true : false)";
                    } else if ($this->cache_mode === self::KEEP_TARGET_MODE){
                        $this->eval_tokens[$i] = "(in_array(trim(mb_substr(\$this->query_tokens[".strval($i)."],4), \"\\\"'\"),\$this->target_tags) === true ? true : false)";
                    }
                }
            }
        }
        return true;
    }

    //文字列検索条件の場合の処理をeval文にセットする
    protected function set_findstr_eval(){
        for($i = 0; $i < count($this->eval_tokens); $i++){
            if ($this->eval_tokens[$i] === false){
                if ($this->query_tokens[$i][0] === "\"" || $this->query_tokens[$i][0] === "'"){
                    //""や''で囲まれている場合は大文字小文字区別しない
                    $this->eval_tokens[$i] = "(mb_strpos(\$this->target_str,trim(\$this->query_tokens[".strval($i)."], \"\\\"'\")) !== false ? true : false)";
                } else {
                    //トークンが演算子でない場合は、findstrの条件をセットする
                    $this->eval_tokens[$i] = "(mb_stripos(\$this->target_str,\$this->query_tokens[".strval($i)."]) !== false ? true : false)";
                }
            }
        }
        return true;
    }
}
?>