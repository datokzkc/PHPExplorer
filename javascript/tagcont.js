$(document).ready(function () {
    var mode = "nomal";

    //上段用
    var $info1 = $('#info_text1');
    var $rm_list1 = $('#rm_tag1');
    var $add_list1 = $('#add_tag_list1');
    var $add_text1 = $('#add_tag_text1');
    var $enter1 = $('#enter_btn1');
    var $cancel1 = $('#cancel_btn1');
    var $add_btn1 = $('#add_tag_btn1');
    var $rm_btn1 = $('#rm_tag_btn1');
    var $clear_btn1 = $('#rm_all_btn1');

    var dir_path1 = $('#path1').text();


    $info1.hide();
    $rm_list1.hide();
    $add_list1.hide();
    $add_text1.hide();
    $enter1.hide();
    $cancel1.hide();

    $add_btn1.click(function () {
        mode = "add";
        hide_all(1);
        $info1.text("追加するタグを選択してください");
        $info1.show();
        $add_list1.show();
        $enter1.show();
        $cancel1.show();
        $rm_list1.hide();
        $rm_btn1.hide();
        $clear_btn1.hide();
        $add_btn1.hide();
        if ($add_list1.val() == "自分で入力(新規追加)") {
            $add_text1.prop("disabled", false);
            $add_text1.show();
        } else {
            $add_text1.prop("disabled", true);
            $add_text1.hide();
        }
    });

    $rm_btn1.click(function () {
        mode = "remove";
        hide_all(1);
        $info1.text("消去するタグを選択してください");
        $info1.show();
        $add_list1.hide();
        $add_text1.hide();
        $add_text1.prop("disabled", true);
        $enter1.show();
        $cancel1.show();
        $rm_list1.show();
        $rm_btn1.hide();
        $clear_btn1.hide();
        $add_btn1.hide();
    });

    $clear_btn1.click(function () {
        if (confirm('タグ情報はすべて削除されます。\n本当によろしいですか？')) {
            mode = "remove_all";
            $.ajax({
                url: './ajax.php',
                type: 'POST',
                data: {
                    'mode': mode,
                    'tag': null,
                    'path': dir_path1
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

    $add_list1.change(function () {
        if ($add_list1.val() == "自分で入力(新規追加)") {
            $add_text1.prop("disabled", false);
            $add_text1.show();
        }
        else {
            $add_text1.prop("disabled", true);
            $add_text1.hide();
        }
    });

    $enter1.click(function () {
        var tag1 = null;
        switch (mode) {
            case "add":
                tag1 = $add_list1.val();
                if (tag1 == "自分で入力(新規追加)") {
                    tag1 = $add_text1.val();
                    if (tag1 == "自分で入力(新規追加)"){
                        alert("そのタグは追加できません");
                        return;
                    }
                }
                break;

            case "remove":
                tag1 = $rm_list1.val();
                break;
            default:
                return;
        }

        $.ajax({
            url: './ajax.php',
            type: 'POST',
            data: {
                'mode': mode,
                'tag': tag1,
                'path': dir_path1
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
    });

    $cancel1.click(function () {
        $add_text1.val("");
        $info1.hide();
        $rm_list1.hide();
        $add_list1.hide();
        $add_text1.hide();
        $enter1.hide();
        $cancel1.hide();
        $add_btn1.show();
        $rm_btn1.show();
        $clear_btn1.show();

        $('#info_text2').hide();
        $('#rm_tag2').hide();
        $('#add_tag_list2').hide();
        $('#add_tag_text2').hide();
        $('#enter_btn2').hide();
        $('#add_tag_btn2').show();
        $('#rm_tag_btn2').show();
        $('#rm_all_btn2').show();
        $("#cancel_btn2").hide();
        mode = "nomal";
    })

    //下段用
    var $info2 = $('#info_text2');
    var $rm_list2 = $('#rm_tag2');
    var $add_list2 = $('#add_tag_list2');
    var $add_text2 = $('#add_tag_text2');
    var $enter2 = $('#enter_btn2');
    var $cancel2 = $('#cancel_btn2');
    var $add_btn2 = $('#add_tag_btn2');
    var $rm_btn2 = $('#rm_tag_btn2');
    var $clear_btn2 = $('#rm_all_btn2');

    var dir_path2 = $('#path2').text();


    $info2.hide();
    $rm_list2.hide();
    $add_list2.hide();
    $add_text2.hide();
    $enter2.hide();
    $cancel2.hide();

    $add_btn2.click(function () {
        mode = "add";
        hide_all(2);
        $info2.text("追加するタグを選択してください");
        $info2.show();
        $add_list2.show();
        $enter2.show();
        $cancel2.show();
        $rm_list2.hide();
        $rm_btn2.hide();
        $clear_btn2.hide();
        $add_btn2.hide();
        if ($add_list2.val() == "自分で入力(新規追加)") {
            $add_text2.prop("disabled", false);
            $add_text2.show();
        } else {
            $add_text2.prop("disabled", true);
            $add_text2.hide();
        }
    });

    $rm_btn2.click(function () {
        mode = "remove";
        hide_all(2);
        $info2.text("消去するタグを選択してください");
        $info2.show();
        $add_list2.hide();
        $add_text2.hide();
        $add_text2.prop("disabled", true);
        $enter2.show();
        $cancel2.show();
        $rm_list2.show();
        $rm_btn2.hide();
        $clear_btn2.hide();
        $add_btn2.hide();
    });

    $clear_btn2.click(function () {
        if (confirm('タグ情報はすべて削除されます。\n本当によろしいですか？')) {
            mode = "remove_all";
            $.ajax({
                url: './ajax.php',
                type: 'POST',
                data: {
                    'mode': mode,
                    'tag': null,
                    'path': dir_path2
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
                    $info2.text("Ajaxエラーが発生しました。");
                    console.log(data);
                })
                // Ajaxリクエストが成功・失敗どちらでも発動
                .always((data) => {

                });
        } else {
            //nothing do.
        }
    });

    $add_list2.change(function () {
        if ($add_list2.val() == "自分で入力(新規追加)") {
            $add_text2.prop("disabled", false);
            $add_text2.show();
        }
        else {
            $add_text2.prop("disabled", true);
            $add_text2.hide();
        }
    });

    $enter2.click(function () {
        var tag2 = null;
        switch (mode) {
            case "add":
                tag2 = $add_list2.val();
                if (tag2 == "自分で入力(新規追加)") {
                    tag2 = $add_text2.val();
                    if (tag2 == "自分で入力(新規追加)"){
                        alert("そのタグは追加できません");
                        return;
                    }
                }
                break;

            case "remove":
                tag2 = $rm_list2.val();
                break;
            default:
                return;
        }

        $.ajax({
            url: './ajax.php',
            type: 'POST',
            data: {
                'mode': mode,
                'tag': tag2,
                'path': dir_path2
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
                $info2.text("Ajaxエラーが発生しました。");
                console.log(data);
            })
            // Ajaxリクエストが成功・失敗どちらでも発動
            .always((data) => {

            });
    });

    $cancel2.click(function () {
        $add_text2.val("");
        $info2.hide();
        $rm_list2.hide();
        $add_list2.hide();
        $add_text2.hide();
        $enter2.hide();
        $cancel2.hide();
        $add_btn2.show();
        $rm_btn2.show();
        $clear_btn2.show();

        $('#info_text1').hide();
        $('#rm_tag1').hide();
        $('#add_tag_list1').hide();
        $('#add_tag_text1').hide();
        $('#enter_btn1').hide();
        $('#add_tag_btn1').show();
        $('#rm_tag_btn1').show();
        $('#rm_all_btn1').show();
        $("#cancel_btn1").hide();
        mode = "nomal";
    })


    function hide_all(i) {
        ex_i = 3 - i;
        $('#info_text' + ex_i).hide();
        $('#rm_tag' + ex_i).hide();
        $('#add_tag_list' + ex_i).hide();
        $('#add_tag_text' + ex_i).hide();
        $('#enter_btn' + ex_i).hide();
        $('#add_tag_btn' + ex_i).hide();
        $('#rm_tag_btn' + ex_i).hide();
        $('#rm_all_btn' + ex_i).hide();
        $("#cancel_btn" + ex_i).hide();
    }
});