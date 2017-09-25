<?php

class WebSocketClient
{
    private $client;
    private $data;

    public function __construct() {
        //异步客户端
        $this->client = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);

        $this->client->on('Connect', array($this, 'onConnect'));
        $this->client->on('Receive', array($this, 'onReceive'));
        $this->client->on('Close', array($this, 'onClose'));
        $this->client->on('Error', array($this, 'onError'));
        $this->client->on('BufferFull', array($this, 'onBufferFull'));
        $this->client->on('BufferEmpty', array($this, 'onBufferEmpty'));

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
echo $this->data . PHP_EOL;
        $this->client->send($this->data);

    }

    public function onReceive( $cli, $data ) {

        echo $data;

    }

    public function onClose( $cli) {
        echo "WebSocketClient close connection\n";

    }
    public function onError() {
	echo 'Error';
    }

    public function onBufferFull($cli){

    }

    public function onBufferEmpty($cli){

    }

}
$cli = new WebSocketClient();
$cli->connect('WebSocketClient Test');
