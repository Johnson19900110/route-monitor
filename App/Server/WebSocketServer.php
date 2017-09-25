<?php
namespace App\Server;

class WebSocketServer {
    private $clients = array();

    public function __construct()
    {
        $server = new \swoole_websocket_server("0.0.0.0", 9501);
        $server->set(array(
            'daemonize' => 0,
            'worker_num' => 4,
            'max_request' => 1000,
        ));

        $server->on('open', array($this, 'onOpen'));
        $server->on('message', array($this, 'onMessage'));
        $server->on('close', array($this, 'onClose'));

        $server->start();
    }

    public function onOpen($server, $request)
    {
        $this->clients[] = $request->fd;
    }

    public function onMessage($server, $frame)
    {
	print_r($this->clients);
        foreach ($this->clients as $v) {
            $server->push($v, $frame->data);
        }
    }

    public function onClose($server, $fd)
    {
	echo $fd . 'disconnect' . PHP_EOL;
        foreach ($this->clients as $k=>$v) {
            if($v === $fd) {
                unset($this->clients[$k]);
            }
        }
    }

}

$ws = new WebSocketServer();

