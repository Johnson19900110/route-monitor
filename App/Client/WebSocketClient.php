<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/25
 * Time: 9:22
 */

namespace App\Client;


class WebSocketClient
{
    private $client;
    private $data;

    public function __construct()
    {
        $this->client = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
        $this->client->on('connect', array($this, 'onConnect'));
        $this->client->on('receive', array($this, 'onReceive'));
        $this->client->on('close', array($this, 'onClose'));
    }

    public function sendData($data)
    {
        $this->data = $data;
        $fp = $this->client->connect("127.0.0.1", 9503 , 1);
        if( !$fp ) {
            echo "Error: {$fp->errMsg}[{$fp->errCode}]\n";
            return;
        }
    }

    public function onConnect($cli)
    {
        $this->client->send($this->data);
    }

    public function onReceive($cli, $data)
    {
        echo $data;
    }

    public function onClose($cli) {
        echo "Connection close!\n";

    }
}

$ws = new WebSocketClient();
$ws->sendData('WebSocketClient Test');
