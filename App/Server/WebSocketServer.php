<?php
namespace App\Server;

class WebSocketServer {
    private $clients = array();
    private $server;
    private $lock;

    public function __construct()
    {
        $this->lock = new \swoole_lock(SWOOLE_MUTEX);

        $this->server = new \swoole_websocket_server("0.0.0.0", 9501);
        $this->server->set(array(
            'daemonize' => 0,
            'worker_num' => 4,
            'max_request' => 1000,
        ));

        $this->server->on('open', array($this, 'onOpen'));
        $this->server->on('message', array($this, 'onMessage'));
        $this->server->on('close', array($this, 'onClose'));

        $this->server->start();
    }

    public function onOpen($server, $request)
    {
	    $this->clients[$request->fd] = '';
	    echo $request->fd . ' connect' . ' IP:' . $request->server['remote_addr'] . PHP_EOL;
    }

    public function onMessage($server, $frame)
    {
        foreach ($this->server->connections as $fd) {
            $this->server->push($fd, $frame->data);
        }
    }

    public function onClose($server, $fd)
    {
        /*foreach ($this->server->connections as $k=>$v) {
            if($v === $fd) {
                $this->lock->lock();
                unset($this->clients[$k]);
                $this->lock->unlock();
            }
        }*/
    }

}

$ws = new WebSocketServer();

