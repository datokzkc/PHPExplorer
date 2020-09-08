$(function(){
  var totop = $('#totop');
  // ボタン非表示
  totop.hide();
  // 100px スクロールしたらボタン表示
  $(window).scroll(function () {
     if ($(this).scrollTop() > 100) {
          totop.fadeIn();
     } else {
          totop.fadeOut();
     }
  });
  totop.click(function () {
     $('body, html').animate({ scrollTop: 0 }, 500);
     //herfの無効化
     return false;
  });
});