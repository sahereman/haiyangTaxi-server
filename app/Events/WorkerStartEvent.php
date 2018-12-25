<?php

namespace App\Events;

use Hhxsv5\LaravelS\Swoole\Events\WorkerStartInterface;
use Illuminate\Support\Facades\Cache;

class WorkerStartEvent implements WorkerStartInterface
{
    const DRIVER_STATUS_BUSY = 'busy';
    const DRIVER_STATUS_FREE = 'free';

    public $client_fd; // 客户端 fd 对应 用户ID zset
    public $client_id; // 客户端 用户ID 对应 fd zset

    public $driver_fd; // 司机端 fd 对应 司机ID zset
    public $driver_id; // 司机端 司机ID 对应 fd zset

    public $driver_active; // 上班的所有司机ID 对应 车辆信息 zset

    public function __construct()
    {
        $this->client_fd = Cache::getPrefix() . 'client_fd_keys';
        $this->client_id = Cache::getPrefix() . 'client_id_keys';
        $this->driver_fd = Cache::getPrefix() . 'driver_fd_keys';
        $this->driver_id = Cache::getPrefix() . 'driver_id_keys';
        $this->driver_active = Cache::getPrefix() . 'driver_active_drivers';
    }

    public function handle(\swoole_http_server $server, $workerId)
    {
        /*清空Redis*/
        if ($workerId == 0)
        {
            $redis = app('redis.connection');

            if ($redis->exists($this->client_id))
            {
                $redis->del($this->client_id);
            }

            if ($redis->exists($this->client_fd))
            {
                $redis->del($this->client_fd);
            }

            if ($redis->exists($this->driver_id))
            {
                $redis->del($this->driver_id);
            }

            if ($redis->exists($this->driver_fd))
            {
                $redis->del($this->driver_fd);
            }

            if ($redis->exists($this->driver_active))
            {
                $redis->del($this->driver_active);
            }
        }
    }
}