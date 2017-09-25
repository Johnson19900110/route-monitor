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

    public function __construct()
    {
        $this->client = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
    }
}