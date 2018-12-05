<?php

namespace App\Sockets;

use App\Handlers\SocketJsonHandler;
use Hhxsv5\LaravelS\Swoole\Socket\WebSocket as WebSocketService;
use Illuminate\Support\Facades\Cache;

class WebSocket extends WebSocketService
{

    public $client_fd;
    public $driver_fd;
    public $driver_active;
    public $order_set;


    public function __construct(\swoole_server_port $port)
    {
        parent::__construct($port);

        $this->client_fd = Cache::getPrefix() . 'client_fd_keys';
        $this->driver_fd = Cache::getPrefix() . 'driver_fd_keys';
        $this->driver_active = Cache::getPrefix() . 'driver_active_drivers';
        $this->order_set = Cache::getPrefix() . 'order_set';
    }

    public function onOpen(\swoole_websocket_server $server, \swoole_http_request $request)
    {

    }

    public function onClose(\swoole_websocket_server $server, $fd, $reactorId)
    {

    }

    public function onMessage(\swoole_websocket_server $server, \swoole_websocket_frame $frame)
    {

    }

}