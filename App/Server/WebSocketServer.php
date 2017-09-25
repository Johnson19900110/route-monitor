<?php
namespace App\Server;

class WebSocketServer {
    private $clients = array();

    public function __construct()
    {
        $server = new \swoole_server("0.0.0.0", 9501);
        $server->set(array(
            'daemonize' => 0,
            'worker_num' => 4,
            'max_request' => 1000,
	    'open_websocket_protocol' => true
        ));

        $server->on('connect', array($this, 'onConnect'));
        $server->on('receive', array($this, 'onReceive'));
        $server->on('close', array($this, 'onClose'));

        $server->start();
    }

    public function onConnect($server, $fd)
    {
        $this->clients[] = $fd;
    }

    public function onReceive($server, $fd, $from_id, $data)
    {
echo $data . PHP_EOL . 'test';
        foreach ($this->clients as $v) {
            $server->send($v, $data);
        }
    }

    public function onClose($server, $fd)
    {
        foreach ($this->clients as $k=>$v) {
            if($v === $fd) {
                unset($this->clients[$k]);
            }
        }
    }

}

$ws = new WebSocketServer();

