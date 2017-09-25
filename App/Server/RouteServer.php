<?php

/**
 * Created by PhpStorm.
 * User: XiongChao
 * Date: 2017/8/25
 * Time: 9:46
 */
namespace App\Server;

use App\Log\Writer;

class RouteServer
{
    private $serv;   //swoole server
    private $writer;  //writer object
    const ALARM_TIME = 15;
    private $clients;  //client

    /**
     * 初始化swoole
     */
    public function __construct()
    {
        $this->serv = new \swoole_server('0.0.0.0', 9503, SWOOLE_BASE, SWOOLE_SOCK_TCP);
        $this->writer = new Writer();
        $this->clients=[];
        $this->serv->set(array(
            'worker_num' => 10,
            'reactor_num' => 5,
            'daemonize' => true,
            'backlog' => 128,
            'task_worker_num' => 4,     //设置此参数后，服务器会开启异步task功能。此时可以使用task方法投递异步任务。
            'max_request' => 10000,
            'dispatch_mode' => 2,  //数据包分发策略  默认为2 固定模式
            'package_max_length' => 81920,   //所能接收的包最大长度 根据实际情况自行配置
            'open_length_check' => true,   //打开固定包头协议解析功能
            'package_length_offset' => 0,  //规定了包头中第几个字节开始是长度字段
            //'package_body_offset' => 42,    //规定了包头的长度
            'package_body_offset' => 0,    //length的值包含了整个包（包头+包体）
            'package_length_type' => 'N',   //规定了长度字段的类型
            'task_max_request' => 100,  //
            'heartbeat_idle_time' => 150,  //表示连接最大允许空闲的时间
            'heartbeat_check_interval' => 30,  //轮询检测时间
            'log_file' => ROOT_PATH . '/storage/logs/swoole.log'
        ));
        $this->serv->on('Start', array(
            $this,
            'onStart'
        ));
        $this->serv->on('Connect', array(
            $this,
            'onConnect'
        ));
        $this->serv->on('Receive', array(
            $this,
            'onReceive'
        ));
        $this->serv->on('Close', array(
            $this,
            'onClose'
        ));
        $this->serv->on('WorkerStart', array(
            $this,
            'onWorkerStart'
        ));
        //1.7？ 版本后不支持
        /* $this->serv->on('Timer', array(
             $this,
             'onTimer'
         ));*/
        // bind callback
        $this->serv->on('Task', array(
            $this,
            'onTask'
        ));
        $this->serv->on('Finish', array(
            $this,
            'onFinish'
        ));

        $this->serv->start();
    }

    /**
     * Server启动在主进程的主线程回调此函数
     *
     * @param unknown $serv
     */
    public function onStart($serv)
    {
        // 设置进程名称
        cli_set_process_title("root_server");
       // echo "Start\n";
    }

    /**
     * 有新的连接进入时，在worker进程中回调
     *
     * @param swoole_server $serv
     * @param int $fd
     * @param int $from_id
     */
    public function onConnect($serv, $fd, $from_id)
    {
       // echo "WebSocketClient {$fd} connect\n";
        $clientinfo = $serv->connection_info($fd);
        //可根据具体需求对客户端进行相关处理
        //暂对数据进行日志记录
        try {
            $this->writer->info([
                'remote_ip' => $clientinfo['remote_ip'], //客户端连接的端口
                'remote_port' => $clientinfo['remote_port'], //连接到Server的时间，单位秒
                'connect_time' => date('Y-m-d H:i:s', $clientinfo['connect_time']), //连接到Server的时间，单位秒
                //  'message' => substr($data, 42)    //消息内容
            ]);
        } catch (\InvalidArgumentException $e) {
            $this->writer->error($e->getMessage());
        }
    }

    /**
     * 接收到数据时回调此函数，发生在worker进程中
     *
     * @param swoole_server $serv
     * @param int $fd
     * @param int $from_id
     * @param var $data
     */
    public function onReceive($serv, $fd, $from_id, $data)
    {
        // send a task to task worker.
        $param = array(
            'fd' => $fd,
            'data' => base64_encode($data)
        );
        $serv->task(json_encode($param));
    }

    /**
     * TCP客户端连接关闭后，在worker进程中回调此函数
     *
     * @param swoole_server $serv
     * @param int $fd
     * @param int $from_id
     */
    public function onClose($serv, $fd, $from_id)
    {
        foreach ($this->clients as $uuid=>$c_fd){
            if($c_fd === $fd){
                unset($this->clients[$uuid]);
            }
        }
       // echo "WebSocketClient {$fd} close connection\n";
    }

    /**
     * 在task_worker进程内被调用。
     * worker进程可以使用swoole_server_task函数向task_worker进程投递新的任务。
     * 当前的Task进程在调用onTask回调函数时会将进程状态切换为忙碌，这时将不再接收新的Task，
     * 当onTask函数返回时会将进程状态切换为空闲然后继续接收新的Task
     *
     * @param swoole_server $serv
     * @param int $task_id
     * @param int $from_id
     * @param
     *            json string $param
     * @return string
     */
    public function onTask($serv, $task_id, $from_id, $param)
    {
      //  echo "This Task {$task_id} from Worker {$from_id}\n";
        //get client info

        $paramArr = json_decode($param, true);
        $fd = $paramArr['fd'];
        $data = base64_decode($paramArr['data']);
        //先获取请求数据

        //消息类型
        $msg_type = unpack("C", $data)[1];
        $data = substr($data, 1);

        //服务端响应包体是否需要加密标识  0-不需要加密  1-需要加密  保留字段
        $replyCipher = unpack("C", $data)[1];
        $data = substr($data, 1);

        //获取包体是否需要压缩标识  0-未压缩 1-压缩  保留字段
        $compress = unpack("C", $data)[1];
        $data = substr($data, 1);

        //获取整个消息的长度
        $msg_length = unpack("N", $data)[1];
        $data = substr($data, 4);

        //请求者ID
        $uuid = substr($data, 0, 33);

        //获取包体
        $data = substr($data, 33);

        /**
         * 1-心跳请求消息
         * 2-心跳应答消息
         * 3-非心跳请求消息
         * 4-非心跳应答消息
         */
        switch ($msg_type) {
            case 1:
                //响应心跳
                $msg_type = 2;
                $serv->send($fd, pack("C", $msg_type));
                $serv->send($fd, pack("C", $replyCipher));
                $serv->send($fd, pack("C", $compress));
                $serv->send($fd, pack("N", 39));
                $serv->send($fd, $uuid);
                return "Task {$task_id}'s do heart";    //会将结果反馈给finish方法
                break;
            case 3:
                //对客户端进行记录
                if(array_key_exists($uuid,$this->clients)){
                    $serv->close($this->clients[$uuid]);
                }
                $this->clients[$uuid]=$fd;
                //对推送消息进行业务处理
				$msg_type = 4;
				$length=39+strlen($data);
                $serv->send($fd, pack("C", $msg_type));
                $serv->send($fd, pack("C", $replyCipher));
                $serv->send($fd, pack("C", $compress));
				$serv->send($fd, pack("N", $length));
                $serv->send($fd, $uuid);

                switch ($replyCipher) {
                    case 0:
                }

				$serv->send($fd,$data);
                break;
            default:
                $clientinfo = $serv->connection_info($fd);
                $this->writer->warning([
                    'remote_ip' => $clientinfo['remote_ip'], //客户端连接的端口
                    'remote_port' => $clientinfo['remote_port'], //连接到Server的时间，单位秒
                    'connect_time' => date('Y-m-d H:i:s', $clientinfo['connect_time']), //连接到Server的时间，单位秒
                    'message' => '错误请求消息'    //消息内容
                ]);
                break;
        }
        return "Task {$task_id}'s result";
    }

    /**
     * 当worker进程投递的任务在task_worker中完成时，
     * task进程会通过swoole_server->finish()方法将任务处理的结果发送给worker进程
     *
     * @param swoole_server $serv
     * @param int $task_id
     * @param string $data
     */
    public function onFinish($serv, $task_id, $data)
    {

     //   echo "Task {$task_id} finish\n";
      //  echo "Result: {$data}\n";
    }

    /**
     * 此事件在worker进程/task进程启动时发生
     *
     * @param swoole_server $serv
     * @param int $worker_id
     */
    function onWorkerStart($serv, $worker_id)
    {
      //  echo "onWorkerStart\n";
        // 只有当worker_id为0时才添加定时器,避免重复添加
        if ($worker_id == 0) {
            // 在Worker进程开启时绑定定时器
            // 低于1.8.0版本task进程不能使用tick/after定时器，所以需要使用$serv->taskworker进行判断
            if (!$serv->taskworker) {
                $serv->tick(15000, function ($id) {
                    $this->tickerEvent($this->serv);
                });
            } else {
                $serv->addtimer(15000);
            }
        //    echo "start timer finished\n";
        }
    }

    /**
     * 定时任务
     *
     * @param swoole_server $serv
     * @param int $interval
     */
    public function onTimer($serv, $interval)
    {
        // TODO 根据实际情况进行操作
    }

    /**
     * 定时任务
     *
     * @param swoole_server $serv
     */
    private function tickerEvent($serv)
    {
        //规定时间内没有接收到数据则对相关人员进行通知
        foreach ($this->clients as $uuid=>$fd){
            $last_time = $serv->connection_info($fd)['last_time'];
            if(time()-$last_time>self::ALARM_TIME){

            }
        }
        // TODO 根据实际情况进行操作
     //   echo "tickerEvent down" . "\n";
    }
}
