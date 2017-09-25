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
            'task_worker_num' => 10,
            'max_request' => 1000,
        ));

        $server->on('open', array($this, 'onOpen'));
        $server->on('message', array($this, 'onMessage'));
        $server->on('task', array($this, 'onTask'));
        $server->on('finish', array($this, 'onFinish'));
        $server->on('close', array($this, 'onClose'));

        $server->start();
    }

    public function onOpen($server, $request)
    {
        $this->clients[] = $request->fd;
        print_r($request);
    }

    public function onMessage($server, $frame)
    {
        $server->task($frame);
    }

    public function onTask($server, $task_id, $from_id, $frame)
    {
//        echo $frame->data . PHP_EOL;
        foreach ($this->clients as $v) {
            $server->push($v, $frame->data);
        }
        $server->finish($frame->fd);
    }

    public function onFinish($server, $task_id, $data)
    {
        echo $data . " push success" . PHP_EOL;
    }

    public function onClose($server, $fd)
    {
        foreach ($this->clients as $v) {
            if($v === $fd) {
                unset($v);
            }
        }
    }

}

$ws = new WebSocketServer();

