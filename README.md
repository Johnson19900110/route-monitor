# route-monitor

# 安装

安装PHP`swoole`拓展：`pecl install swoole`

或到[swoole官网](http://www.swoole.com/)获取安装帮助

# 运行

开启服务：
将client目录配置到Nginx/Apache的虚拟主机目录中，使index.php可访问。 修改`config.php`中，IP和端口为对应的配置。
``` bash
#开启swoole_server端
php App/App.php -i e
#开启swoole_websocket_server端
php App/Server/WebSocketServer.php
#模拟传输数据
php App/Server/SwooleClient.php
```