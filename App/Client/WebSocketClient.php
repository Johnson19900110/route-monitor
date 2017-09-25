<?php
/**
 * Created by PhpStorm.
 * User: Xc
 * Date: 2017/8/25
 * Time: 10:42
 */

class WebSocketClient
{
    private $client;
    private $data;

    public function __construct() {
        //异步客户端
        $this->client = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);

        $this->client->on('connect', array($this, 'onConnect'));
        $this->client->on('receive', array($this, 'onReceive'));
        $this->client->on('close', array($this, 'onClose'));
    }
    public function connect($data) {
        $this->data = $data;
        $fp = $this->client->connect("127.0.0.1", 9501 , 1);
        if( !$fp ) {
            echo "Error: {$fp->errMsg}[{$fp->errCode}]\n";
            return;
        }

    }

    public function onConnect( $cli) {
        $cli->send($this->data);

    }

    public function onReceive( $cli, $data ) {

        echo $data;

    }

    public function onClose( $cli) {
        echo "WebSocketClient close connection\n";

    }
}
$cli = new WebSocketClient();
$cli->connect('WebSocketClient Test');
