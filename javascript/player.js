var $play = $('#playpause');
var $stop = $('#stop');
var $vol = $('#volume');
var $seek = $('#nowtime');
var $time = $('#time');
var is_play =false;

$play.show();
$stop.show();
$("#volume_div").show();
$("#nowtime_div").show();
var total_min = Math.floor(player.duration/1000/60);
var total_sec = ("00" +Math.floor(player.duration/1000) %60).slice(-2);
$time.text("0:00/"+total_min+":"+total_sec);

$play.click(function() {
    if(player.playing == true){
        player.pause();
        $play.val("再生");
    }else{
        player.play();
        $play.val("一時停止");
        is_play = true;
    }
})
$stop.click(function() {
    player.pause();
    player.seek(0);
    $play.val("再生");
    $seek.val(0);
})
$vol.on("change",function(event){
    player.volume =($vol.val());
});
$vol.on("input",function(event){
    player.volume =($vol.val());
});
//seekは入力させない


//シークバー表示
setInterval(function(){
    var time_par = Math.round(player.currentTime*1000/player.duration);
    $seek.val(time_par);
    total_min = Math.floor(player.duration/1000/60);
    total_sec = ("00" +Math.floor(player.duration/1000) %60).slice(-2);
    now_min = Math.floor(player.currentTime/1000/60);
    now_sec = ("00" +Math.floor(player.currentTime/1000) %60).slice(-2);
    $time.text(now_min+":"+now_sec+"/"+total_min+":"+total_sec);
    if(player.currentTime >= player.duration && is_play == true){
        $time.text("再生終了");
    }
}, 500);