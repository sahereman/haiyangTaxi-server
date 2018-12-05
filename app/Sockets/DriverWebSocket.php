<?php

namespace App\Sockets;

use App\Handlers\SocketJsonHandler;
use App\Models\Driver;
use App\Models\Order;
use App\Models\User;
use App\Rules\RedisHashExists;
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
     * 司机端主动的action列表
     * @var array
     */
    private $actions = [
        'active',      // 上班
        'close',       // 下班
        'location',    // 更新位置
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
                case 'close' :
                    $server->close($frame->fd);
                    break;
                case 'location' :
                    $this->locationAction($server, $frame, $data, $driverId);
                    break;
                case 'accept' :
                    $this->acceptAction($server, $frame, $data, $driverId);
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

    public function acceptAction($server, $frame, $data, $driverId)
    {
        // {"action":"accept","data":{"order_key":"6bf47d94-3d1a-4de8-b7c1-c62570076070"}}
        $validator = Validator::make(array_add($data, 'driver', $driverId), [
            'data' => ['required'],
            'data.order_key' => ['required', new RedisHashExists($this->order_set)],
        ]);

        if ($validator->fails())
        {
            $server->push($frame->fd, new SocketJsonHandler(422, 'Unprocessable Entity', 'location', $validator->errors()));
        } else
        {
            $redis = app('redis.connection');
            $set = json_decode($redis->hget($this->order_set, $data['data']['order_key']), true);
            $user = User::find($set['user_id']);
            $driver = Driver::find($driverId);
            $userFd = array_first($redis->zrange($this->client_fd, $set['user_id'], $set['user_id']));


            // 创建订单
            $order = new Order();
            $order->user()->associate($user);
            $order->driver()->associate($driver);
            $order->status = Order::ORDER_STATUS_TRIPPING;
            $order->from_address = $data['data']['from_address'];
            $order->from_location = $data['data']['from_location'];
            $order->to_address = $data['data']['to_address'];
            $order->to_location = $data['data']['to_location'];
            $order->save();

            // 通知用户
            $server->push($userFd, new SocketJsonHandler(200, 'OK', 'accept'));


            //
            //            $redis->zremrangebyscore($this->driver_active, $driverId, $driverId);
            //            $redis->zadd($this->driver_active, intval($driverId), json_encode(['id' => $driverId, 'lat' => $data['data']['lat'], 'lng' => $data['data']['lng']]));

            $server->push($frame->fd, new SocketJsonHandler(200, 'OK', 'accept'));
        }

    }


}