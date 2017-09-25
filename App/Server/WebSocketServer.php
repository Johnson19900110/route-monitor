<?php
namespace App\Server;

class WebSocketServer {
    private $clients = array();
    private $lock;

    public function __construct()
    {
        $this->lock = new \swoole_lock(SWOOLE_MUTEX);

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
	$this->clients[$request->fd] = '';
	echo $request->fd . ' connect' . ' IP:' . $request->server['remote_addr'] . PHP_EOL;
    }

    public function onMessage($server, $frame)
    {
	print_r($this->clients);
        foreach ($this->clients as $k=>$v) {
            $server->push($k, $frame->data);
        }
    }

    public function onClose($server, $fd)
    {
        /*foreach ($this->clients as $k=>$v) {
            if($v === $fd) {
                $this->lock->lock();
                unset($this->clients[$k]);
                $this->lock->unlock();
            }
        }*/
	print_r($this->clients);
    }

}

$ws = new WebSocketServer();

