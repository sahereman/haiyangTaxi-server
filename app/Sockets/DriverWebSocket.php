<?php

namespace App\Sockets;

use App\Handlers\SocketJsonHandler;
use App\Models\Driver;
use App\Models\User;
use App\Rules\RedisZsetExists;
use App\Rules\RedisZsetUnique;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DriverWebSocket extends WebSocket
{
    /**
     * 司机端可用的action列表
     * @var array
     */
    private $actions = [
        'active',      // 上班
        'quiet',       // 下班
        'location',    // 更新位置
        'notify',      // 收到订单通知
        'accept',      // 接受订单
    ];


    public function onOpen(\swoole_websocket_server $server, \swoole_http_request $request)
    {
        $request->get = $request->get ?? array();

        /*
        $validator = Validator::make($request->get, [
            'token' => ['required', 'string'],
        ]);

        if ($validator->fails())
        {
            $server->push($request->fd, new SocketJsonHandler(401, 'Unauthorized'));
            $server->close($request->fd);
        }

        try
        {
            $user = Auth::guard('driver')->setToken($request->get['token'])->user();
        } catch (\Exception $exception)
        {
            $server->push($request->fd, new SocketJsonHandler(401, 'Unauthorized'));
            $server->close($request->fd);
        }
        */

        $driver = Driver::find($request->get['token']);


        $redis = app('redis.connection');

        //        info();

        $redis->zadd($this->driver_fd, intval($request->fd), $driver->id);

        $server->push($request->fd, new SocketJsonHandler(200, 'OK', 'open'));
    }

    public function onClose(\swoole_websocket_server $server, $fd, $reactorId)
    {
        $redis = app('redis.connection');
        $driverId = array_first($redis->zrangebyscore($this->driver_fd, $fd, $fd));


        $redis->zremrangebyscore($this->driver_fd, $fd, $fd);
        $redis->zremrangebyscore($this->driver_active, $driverId, $driverId);
    }

    public function onMessage(\swoole_websocket_server $server, \swoole_websocket_frame $frame)
    {
        // {"action":"active"}

        $redis = app('redis.connection');
        $driverId = array_first($redis->zrangebyscore($this->driver_fd, $frame->fd, $frame->fd));
        $data = is_array(json_decode($frame->data, true)) ? json_decode($frame->data, true) : array();


        $validator = Validator::make($data, [
            'action' => ['required', Rule::in($this->actions)],
        ]);

        if ($validator->fails())
        {
            $server->push($frame->fd, new SocketJsonHandler(422, 'Unprocessable Entity', 'message', $validator->errors()));
        } else
        {
            switch ($data['action'])
            {
                case 'active' :
                    $this->activeAction($server, $frame, $data, $driverId);
                    break;
                case 'quiet' :
                    $server->close($frame->fd);
                    break;
                case 'location' :
                    $this->locationAction($server, $frame, $data, $driverId);
                    break;
                case 'notify' :
                    $this->notifyAction($server, $frame, $data, $driverId);
                    break;
                default:
                    $server->push($frame->fd, new SocketJsonHandler(422, 'Unprocessable Entity', 'message'));
                    break;
            }
        }

    }

    public function activeAction($server, $frame, $data, $driverId)
    {
        // {"action":"active","data":{"lat":"36.092484","lng":"120.380966"}}
        $validator = Validator::make(array_add($data, 'driver', $driverId), [
            'data' => ['required'],
            'data.lat' => ['required', 'numeric'],
            'data.lng' => ['required', 'numeric'],
            'driver' => ['required', new RedisZsetUnique($this->driver_active)]
        ]);

        if ($validator->fails())
        {
            $server->push($frame->fd, new SocketJsonHandler(422, 'Unprocessable Entity', 'active', $validator->errors()));
        } else
        {
            $redis = app('redis.connection');

            $redis->zadd($this->driver_active, intval($driverId), json_encode(['id' => $driverId, 'fd' => $frame->fd, 'lat' => $data['data']['lat'], 'lng' => $data['data']['lng']]));

            $server->push($frame->fd, new SocketJsonHandler(200, 'OK', 'active'));
        }
    }

    public function locationAction($server, $frame, $data, $driverId)
    {
        // {"action":"location","data":{"lat":"36.111114","lng":"120.444444"}}
        $validator = Validator::make(array_add($data, 'driver', $driverId), [
            'data' => ['required'],
            'data.lat' => ['required', 'numeric'],
            'data.lng' => ['required', 'numeric'],
            'driver' => ['required', new RedisZsetExists($this->driver_active)]
        ]);

        if ($validator->fails())
        {
            $server->push($frame->fd, new SocketJsonHandler(422, 'Unprocessable Entity', 'location', $validator->errors()));
        } else
        {
            $redis = app('redis.connection');

            $redis->zremrangebyscore($this->driver_active, $driverId, $driverId);
            $redis->zadd($this->driver_active, intval($driverId), json_encode(['id' => $driverId, 'lat' => $data['data']['lat'], 'lng' => $data['data']['lng']]));

            $server->push($frame->fd, new SocketJsonHandler(200, 'OK', 'location'));
        }
    }

    public function notifyAction($server, $frame, $data, $driverId)
    {
        $server->push($frame->fd, new SocketJsonHandler(200, 'OK', 'notify'));

        // {"action":"notify","data":{"orderKey":"36.111114"}}
        //        $validator = Validator::make(array_add($data, 'driver', $driverId), [
        //            'data' => ['required'],
        //            'data.lat' => ['required', 'numeric'],
        //            'data.lng' => ['required', 'numeric'],
        //            'driver' => ['required', new RedisZsetExists($this->driver_active)]
        //        ]);
        //
        //        if ($validator->fails())
        //        {
        //            $server->push($frame->fd, new SocketJsonHandler(422, 'Unprocessable Entity', 'location', $validator->errors()));
        //        } else
        //        {
        //            $redis = app('redis.connection');
        //
        //            $redis->zremrangebyscore($this->driver_active, $driverId, $driverId);
        //            $redis->zadd($this->driver_active, intval($driverId), json_encode(['id' => $driverId, 'lat' => $data['data']['lat'], 'lng' => $data['data']['lng']]));
        //
        //            $server->push($frame->fd, new SocketJsonHandler(200, 'OK', 'location'));
        //        }
    }

}