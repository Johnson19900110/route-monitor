<?php
/**
 * Created by PhpStorm.
 * User: Xc
 * Date: 2017/8/25
 * Time: 10:42
 */

class Client
{
    private $client;

    public function __construct() {
        //异步客户端
        $this->client = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
        $this->client->set(array(
           /* 'open_eof_check' => true,
            'package_eof' => "\r\n\r\n",*/
            'open_length_check'     => 1,
            'package_length_type'   => 'N',
            'package_length_offset' => 3,       //第N个字节是包长度的值
            'package_body_offset'   => 0,       //第几个字节开始计算长度
            'package_max_length'    => 2000000,  //协议最大长度
            'socket_buffer_size'     => 1024*1024*10, //10M缓存区
        ));
  //      $this->client = new swoole_client(SWOOLE_SOCK_TCP);
        $this->client->on('Connect', array($this, 'onConnect'));
        $this->client->on('Receive', array($this, 'onReceive'));
        $this->client->on('Close', array($this, 'onClose'));
        $this->client->on('Error', array($this, 'onError'));
        $this->client->on('BufferFull', array($this, 'onBufferFull'));
        $this->client->on('BufferEmpty', array($this, 'onBufferEmpty'));
    }
    public function connect() {
        $fp = $this->client->connect("127.0.0.1", 9503 , 1);
        if( !$fp ) {
            echo "Error: {$fp->errMsg}[{$fp->errCode}]\n";
            return;
        }

    }

    public function onConnect( $cli) {
	echo "Start\n";
        $message=json_encode([
            'code'=>'demo',
            'status'=>'1'
        ]);
        $length=40+strlen($message);
        $uuid=md5(uniqid(microtime(true),true)) . "t";
	echo $uuid . PHP_EOL;
        $this->client->send(pack("C",3));      //消息类型
        $this->client->send(pack("C",0));    //服务端响应包体是否需要加密    0-不加密  1-加密；如果需要加密，请先请求密钥
        $this->client->send(pack("C",0));    //0-未压缩 1-压缩
        $this->client->send(pack("N",$length));  //整个消息的长度(包头+包体)
        $this->client->send($uuid);          //请求者ID
        $this->client->send($message);

        //根据服务端设置60S请求一次心跳数据
        swoole_timer_tick(12000, function() use($cli,$uuid){
            //发送心跳数据
            $this->client->send(pack("C",1));     //消息类型 暂定3为心跳请求
            $this->client->send(pack("C",0));     //服务端响应包体是否需要加密    0-不加密  1-加密；如果需要加密，请先请求密钥
            $this->client->send(pack("C",0));     //0-未压缩 1-压缩；
            $this->client->send(pack("N",40));    //整个消息的长度
            $this->client->send($uuid);                         //请求者ID
        });

    }

    public function onReceive( $cli, $data ) {

        //消息类型
        $msg_type=unpack("C",$data)[1];
        // TODO 根据消息类型不同进行处理
        echo "消息类型:".$msg_type.PHP_EOL;
        $data=substr($data,1);

        //服务端响应包体是否需要加密标识  0-不需要加密  1-需要加密
        $replyCipher=unpack("C",$data)[1];
        // TODO 根据请求进行加密处理
        echo "响应包体是否加密标识:".$replyCipher.PHP_EOL;
        $data=substr($data,1);

        //获取包体是否需要压缩标识  0-未压缩 1-压缩
        $compress=unpack("C",$data)[1];
        echo "包体是否压缩标识:".$compress.PHP_EOL;
        $data=substr($data,1);

        //获取整个消息的长度
        $msg_length=unpack("N",$data)[1];
        echo "整个消息的长度:".$msg_length.PHP_EOL;
        $data=substr($data,4);

        //请求者ID
        $uuid=substr($data,0,33);
        // TODO 记录uuid作为请求者的唯一ID
        echo "请求者ID:". $uuid.PHP_EOL;
        $data=substr($data,33);

        //消息类型为4为应答非心跳消息
        if($msg_type === 4 ){
            //获取包体
            $data=substr($data,1);
            echo "包体:".PHP_EOL;
            echo $data.PHP_EOL;
        }

    }

    public function onClose( $cli) {
        echo "WebSocketClient close connection\n";

    }
    public function onError() {
    }

    public function onBufferFull($cli){

    }

    public function onBufferEmpty($cli){

    }

    public function send($data) {
        $this->client->send( $data );
    }

    public function isConnected() {
        return $this->client->isConnected();
    }

}
$cli = new Client();
$cli->connect();
