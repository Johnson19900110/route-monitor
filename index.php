<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>路由监控</title>
</head>
<body>
<h1>路由监控</h1>
<div id="msg">

</div>
<script>
    var msg = document.getElementById("msg");
    var wsServer = 'ws://10.10.83.233:9501';


    //调用websocket对象建立连接：
    var websocket = new WebSocket(wsServer);
    //onopen监听连接打开
    websocket.onopen = function (evt) {
        //websocket.readyState 属性：
        /*
         CONNECTING    0    The connection is not yet open.
         OPEN    1    The connection is open and ready to communicate.
         CLOSING    2    The connection is in the process of closing.
         CLOSED    3    The connection is closed or couldn't be opened.
         */
	//websocket.send('test');
        msg.innerHTML = "连接成功！<br>";
    };

    //监听连接关闭
    websocket.onclose = function (evt) {
        console.log("连接中断");
    };

    //onmessage 监听服务器数据推送
    websocket.onmessage = function (evt) {
        console.log(JSON.parse(evt.data));
        msg.innerHTML += evt.data +'<br>';
    };

    //监听连接错误信息
    websocket.onerror = function (evt, e) {
        console.log(evt.type);
    };

//     setInterval(function(){
//        websocket.send('[{"city":"上海市"}]');
//     },1000);

</script>
</body>
</html>
