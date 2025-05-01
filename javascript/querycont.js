$(document).ready(function () {
    var $db_query_list = $('#db_query_list');
    var $add_query_text = $('#add_query_text');
    var $read_btn = $('#read_query_btn');
    var $add_btn = $('#add_query_btn');
    var $rm_btn = $('#rm_query_btn');

    var now_query = $('#now_query_text').text();

    set_form();

    $read_btn.click(function () {
        if ($('#db_query_list option:selected').attr('label') == "自分で入力(新規追加)") {
            //nothing do.
            return;
        } else {
            var to_query = $('#db_query_list option:selected').val();
            location.href = "./db_search.php?search=" + encodeURIComponent(to_query);
        }
    });

    $add_btn.click(function () {
        var name = "";
        var org_query = "";
        var mode = "";
        if ($('#db_query_list option:selected').attr('label') == "自分で入力(新規追加)") {
            //nothing do.
            name = $add_query_text.val();
            if (name == "自分で入力(新規追加)" || name == "") {
                alert("その名前は追加できません");
                return;
            }
            org_query = "Nothing";
            mode = "search_query_add";
        } else {
            name = $('#db_query_list option:selected').attr('label');
            org_query = $('#db_query_list option:selected').val();
            mode = "search_query_update";
        }
        if (confirm('名前「'+ name + '」クエリを追加しますか？\n登録クエリ内容：「' + org_query + '」→「' + now_query + '」')) {
            $.ajax({
                url: './ajax.php',
                type: 'POST',
                data: {
                    'mode': mode,
                    'name': name,
                    'query': now_query
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
                $info1.text("Ajaxエラーが発生しました。");
                console.log(data);
            })
            // Ajaxリクエストが成功・失敗どちらでも発動
            .always((data) => {

            });
        } else {
            //nothing do.
        }
    });

    $rm_btn.click(function () {
        var name = $('#db_query_list option:selected').attr('label');
        if (name == "自分で入力(新規追加)") {
            //nothing do.
            return;
        }
        if (confirm('名前「' + name +'」で保存された検索クエリを削除します。\n本当によろしいですか？')) {
            mode = "search_query_remove";
            $.ajax({
                url: './ajax.php',
                type: 'POST',
                data: {
                    'mode': mode,
                    'name': name
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
                $info1.text("Ajaxエラーが発生しました。");
                console.log(data);
            })
            // Ajaxリクエストが成功・失敗どちらでも発動
            .always((data) => {

            });
        } else {
            //nothing do.
        }
    });

    $db_query_list.change(function () {
        set_form();
    });

    function set_form() {
        if( $('#db_query_list option:selected').attr('label') == "自分で入力(新規追加)") {
            $add_query_text.prop("disabled", false);
            $add_query_text.show();
            $read_btn.prop("disabled", true);
            $rm_btn.prop("disabled", true);
        } else {
            $add_query_text.prop("disabled", true);
            $add_query_text.hide();
            $read_btn.prop("disabled", false);
            $rm_btn.prop("disabled", false);
        }
    }

});