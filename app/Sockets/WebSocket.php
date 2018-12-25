<?php

namespace App\Sockets;

use App\Handlers\SocketJsonHandler;
use Hhxsv5\LaravelS\Swoole\Socket\WebSocket as WebSocketService;
use Illuminate\Support\Facades\Cache;

class WebSocket extends WebSocketService
{
    const DRIVER_STATUS_BUSY = 'busy';
    const DRIVER_STATUS_FREE = 'free';

    public $client_fd; // 客户端 fd 对应 用户ID zset
    public $client_id; // 客户端 用户ID 对应 fd zset

    public $driver_fd; // 司机端 fd 对应 司机ID zset
    public $driver_id; // 司机端 司机ID 对应 fd zset

    public $driver_active; // 上班的所有司机ID 对应 车辆信息 zset

    public function __construct(\swoole_server_port $port)
    {
        parent::__construct($port);

        $this->client_fd = Cache::getPrefix() . 'client_fd_keys';
        $this->client_id = Cache::getPrefix() . 'client_id_keys';
        $this->driver_fd = Cache::getPrefix() . 'driver_fd_keys';
        $this->driver_id = Cache::getPrefix() . 'driver_id_keys';
        $this->driver_active = Cache::getPrefix() . 'driver_active_drivers';
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

    protected function activeUpdate($driverId, $data = [])
    {
        $redis = app('redis.connection');

        $driverInfo = json_decode(array_first($redis->zrangebyscore($this->driver_active, $driverId, $driverId)), true);

        if (empty($driverInfo))
        {
            $driverInfo = [];
        }

        $redis->zremrangebyscore($this->driver_active, $driverId, $driverId);
        $redis->zadd($this->driver_active, intval($driverId), json_encode(array_merge($driverInfo, $data)));
    }

}