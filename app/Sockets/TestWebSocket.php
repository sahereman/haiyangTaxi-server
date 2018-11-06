<?php

namespace App\Sockets;

use Hhxsv5\LaravelS\Swoole\Socket\WebSocket;

class TestWebSocket extends WebSocket
{

    public function onOpen(\swoole_websocket_server $server, \swoole_http_request $request)
    {
        info('New WebSocket connection', [$request]);

        $server->push($request->fd, 'Welcome to WebSocket.');
    }

    public function onMessage(\swoole_websocket_server $server, \swoole_websocket_frame $frame)
    {

        info("receive from {$frame->fd}:{$frame->data},opcode:{$frame->opcode},fin:{$frame->finish}\n");

        if ($frame->data == 'exit')
        {
            $server->disconnect($frame->fd, 1000, '主动向websocket客户端发送关闭帧并关闭该连接');
        }

        $server->push($frame->fd, "{$frame->data}");

        //        $server->send($fd, 'LaravelS: ' . $frame);
        //        if ($data === "quit\r\n")
        //        {
        //            $port = $this->swoolePort; //获得`swoole_server_port`对象
        //
        //            $server->send($fd, 'LaravelS: bye' . PHP_EOL);
        //            $server->close($fd);
        //        }
    }

    public function onClose(\swoole_websocket_server $server, $fd, $reactorId)
    {
        info('Close WebSocke connection', [$fd]);
        $server->push($fd, 'Goodbye');
    }

    //    public function onConnect(\swoole_server $server, $fd, $reactorId)
    //    {
    //        \Log::info('New TCP connection', [$fd]);
    //        $server->send($fd, 'Welcome to LaravelS.');
    //    }
    //
    //    public function onReceive(\swoole_server $server, $fd, $reactorId, $data)
    //    {
    //        \Log::info('Received data', [$fd, $data]);
    //        $server->send($fd, 'LaravelS: ' . $data);
    //        if ($data === "quit\r\n")
    //        {
    //            $port = $this->swoolePort; //获得`swoole_server_port`对象
    //
    //            $server->send($fd, 'LaravelS: bye' . PHP_EOL);
    //            $server->close($fd);
    //        }
    //    }
    //
    //    public function onClose(\swoole_server $server, $fd, $reactorId)
    //    {
    //        \Log::info('Close TCP connection', [$fd]);
    //        $server->send($fd, 'Goodbye');
    //    }
}