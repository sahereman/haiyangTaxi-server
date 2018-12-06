<?php

namespace App\Sockets;

use App\Handlers\SocketJsonHandler;
use App\Models\Driver;
use App\Models\Order;
use App\Models\OrderSet;
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
        'beat',        // 发送心跳包
        'active',      // 上班
        'close',       // 下班
        'location',    // 更新位置
        'accept',      // 接受订单
        'driverCancel',// 司机主动取消订单
        'received',    // 用户已上车
        'reach',       // 用户已到达
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


        $redis->zadd($this->driver_fd, intval($request->fd), $driver->id);
        $redis->zadd($this->driver_id, intval($driver->id), $request->fd);


        $server->push($request->fd, new SocketJsonHandler(200, 'OK', 'open'));
    }

    public function onClose(\swoole_websocket_server $server, $fd, $reactorId)
    {
        $redis = app('redis.connection');
        $driverId = array_first($redis->zrangebyscore($this->driver_fd, $fd, $fd));


        $redis->zremrangebyscore($this->driver_fd, $fd, $fd); // 删除fd关联
        $redis->zremrangebyscore($this->driver_id, $driverId, $driverId); // 删除司机id关联
        $redis->zremrangebyscore($this->driver_active, $driverId, $driverId); // 上班的所有司机中 车辆信息
    }

    public function onMessage(\swoole_websocket_server $server, \swoole_websocket_frame $frame)
    {
        // {"action":"beat"}
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
                case 'beat' :
                    $server->push($frame->fd, new SocketJsonHandler(200, 'OK', 'beat'));
                    break;
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
                case 'driverCancel':
                    $this->driverCancelAction($server, $frame, $data, $driverId);
                    break;
                case 'received':
                    $this->receivedAction($server, $frame, $data, $driverId);
                    break;
                case 'reach':
                    $this->reachAction($server, $frame, $data, $driverId);
                    break;
                default:
                    $server->push($frame->fd, new SocketJsonHandler(422, 'Unprocessable Entity', 'message'));
                    break;
            }
        }

    }

    public function activeAction($server, $frame, $data, $driverId)
    {
        /*
        {"action":"active","data":{"lat":"36.092484","lng":"120.380966"}}
        {"action":"active","data":{"lat":"36.091102","lng":"120.382556"}}
        {"action":"active","data":{"lat":"36.092936","lng":"120.381339"}}
        {"action":"active","data":{"lat":"36.089338","lng":"120.380437"}}
        {"action":"active","data":{"lat":"36.087153","lng":"120.379086"}}
        */
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

            $redis->zadd($this->driver_active, intval($driverId), json_encode([
                'id' => $driverId,
                'fd' => $frame->fd,
                'lat' => $data['data']['lat'],
                'lng' => $data['data']['lng'],
                'status' => self::DRIVER_STATUS_FREE,
            ]));

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

            $driverInfo = json_decode(array_first($redis->zrangebyscore($this->driver_active, $driverId, $driverId)), true);
            $redis->zremrangebyscore($this->driver_active, $driverId, $driverId);
            $redis->zadd($this->driver_active, intval($driverId), json_encode([
                'id' => $driverId,
                'fd' => $frame->fd,
                'lat' => $data['data']['lat'],
                'lng' => $data['data']['lng'],
                'status' => $driverInfo['status'],
            ]));

            $server->push($frame->fd, new SocketJsonHandler(200, 'OK', 'location'));
        }
    }

    public function acceptAction($server, $frame, $data, $driverId)
    {
        // {"action":"accept","data":{"order_key":"98ee23019f0c4a168ee3a626f2b6522e"}}
        $validator = Validator::make(array_add($data, 'driver', $driverId), [
            'data' => ['required'],
            'data.order_key' => ['required', 'exists:order_sets,key'],
        ], [
            'data.order_key.exists' => '订单不存在或被抢单'
        ]);

        if ($validator->fails())
        {
            $server->push($frame->fd, new SocketJsonHandler(422, 'Unprocessable Entity', 'accept', $validator->errors()));
        } else
        {
            $redis = app('redis.connection');
            $set = OrderSet::find($data['data']['order_key']);
            $user = $set->user;
            $driver = Driver::find($driverId);
            $driverInfo = json_decode(array_first($redis->zrangebyscore($this->driver_active, $driverId, $driverId)), true);
            $userFd = array_first($redis->zrangebyscore($this->client_id, $user->id, $user->id));

            // 创建订单
            $order = new Order();
            $order->user()->associate($user);
            $order->driver()->associate($driver);
            $order->status = Order::ORDER_STATUS_TRIPPING;
            $order->trip = Order::ORDER_TRIP_MEET;
            $order->from_address = $set->from_address;
            $order->from_location = $set->from_location;
            $order->to_address = $set->to_address;
            $order->to_location = $set->to_location;
            $order->save();

            // 删除订单Set
            $set->delete();

            // 司机状态设置为忙碌
            $redis->zremrangebyscore($this->driver_active, $driverId, $driverId);
            $redis->zadd($this->driver_active, intval($driverId), json_encode([
                'id' => $driverId,
                'fd' => $frame->fd,
                'lat' => $driverInfo['lat'],
                'lng' => $driverInfo['lng'],
                'status' => self::DRIVER_STATUS_BUSY,
            ]));

            // 通知用户
            $server->push(intval($userFd), new SocketJsonHandler(200, 'OK', 'meet', [
                'driver' => [
                    'id' => $driver->id,
                    'cart_number' => $driver->cart_number,
                    'phone' => $driver->phone,
                    'order_count' => $driver->order_count,
                    'location' => [
                        'lat' => $driverInfo['lat'],
                        'lng' => $driverInfo['lng'],
                    ],
                    'distance' => 1800, //距离单位(米)
                    'duration' => 600,  //时间单位(秒)
                ],
                'order' => [
                    'id' => $order->id,
                    'order_sn' => $order->order_sn,
                    'user_id' => $order->user_id,
                    'driver_id' => $order->driver_id,
                    'status' => $order->status,
                    'status_text' => Order::$orderStatusMap[$order->status],
                    'trip' => $order->trip,
                    'trip_text' => Order::$orderTripMap[$order->trip],
                    'from_address' => $order->from_address,
                    'from_location' => $order->from_location,
                    'to_address' => $order->to_address,
                    'to_location' => $order->to_location,
                ],
            ]));


            // 返回结果
            $server->push($frame->fd, new SocketJsonHandler(200, 'OK', 'accept', [
                'user' => [
                    'id' => $user->id,
                    'phone' => $user->phone,
                ],
                'order' => [
                    'id' => $order->id,
                    'order_sn' => $order->order_sn,
                    'user_id' => $order->user_id,
                    'driver_id' => $order->driver_id,
                    'status' => $order->status,
                    'status_text' => Order::$orderStatusMap[$order->status],
                    'trip' => $order->trip,
                    'trip_text' => Order::$orderTripMap[$order->trip],
                    'from_address' => $order->from_address,
                    'from_location' => $order->from_location,
                    'to_address' => $order->to_address,
                    'to_location' => $order->to_location,
                ]
            ]));
        }

    }

    public function driverCancelAction($server, $frame, $data, $driverId)
    {
        // {"action":"driverCancel","data":{"close_reason":"司机测试取消","order_id":"175"}}
        $validator = Validator::make($data, [
            'data' => ['required'],
            'data.close_reason' => ['required'],
            'data.order_id' => ['required',
                Rule::exists('orders', 'id')->where(function ($query) use ($driverId) {
                    $query->where('driver_id', $driverId)->where('status', Order::ORDER_STATUS_TRIPPING);
                })
            ],
        ]);

        if ($validator->fails())
        {
            $server->push($frame->fd, new SocketJsonHandler(422, 'Unprocessable Entity', 'driverCancel', $validator->errors()));
        } else
        {
            $redis = app('redis.connection');

            $order = Order::find($data['data']['order_id']);
            $user = $order->user;
            $userFd = array_first($redis->zrangebyscore($this->client_id, $user->id, $user->id));


            // 修改状态
            $order->status = Order::ORDER_STATUS_CLOSED;
            $order->close_from = Order::ORDER_CLOSE_FROM_DRIVER;
            $order->close_reason = $data['data']['close_reason'];
            $order->closed_at = now();
            $order->save();

            // 通知用户
            $server->push(intval($userFd), new SocketJsonHandler(200, 'OK', 'driverCancel', [
                'order' => [
                    'id' => $order->id,
                    'order_sn' => $order->order_sn,
                    'user_id' => $order->user_id,
                    'driver_id' => $order->driver_id,
                    'status' => $order->status,
                    'status_text' => Order::$orderStatusMap[$order->status],
                    'trip' => $order->trip,
                    'trip_text' => Order::$orderTripMap[$order->trip],
                    'from_address' => $order->from_address,
                    'from_location' => $order->from_location,
                    'to_address' => $order->to_address,
                    'to_location' => $order->to_location,
                    'close_from' => $order->close_from,
                    'close_reason' => $order->close_reason,
                    'closed_at' => $order->closed_at->toDateTimeString()
                ],
            ]));

            // 返回结果
            $server->push($frame->fd, new SocketJsonHandler(200, 'OK', 'driverCancel'));
        }
    }

    public function receivedAction($server, $frame, $data, $driverId)
    {
        // {"action":"received","data":{"order_id":"175"}}
        $validator = Validator::make($data, [
            'data' => ['required'],
            'data.order_id' => ['required',
                Rule::exists('orders', 'id')->where(function ($query) use ($driverId) {
                    $query->where('driver_id', $driverId)->where('status', Order::ORDER_STATUS_TRIPPING)
                        ->where('trip', Order::ORDER_TRIP_MEET);
                })
            ],
        ]);

        if ($validator->fails())
        {
            $server->push($frame->fd, new SocketJsonHandler(422, 'Unprocessable Entity', 'received', $validator->errors()));
        } else
        {
            $redis = app('redis.connection');

            $order = Order::find($data['data']['order_id']);
            $user = $order->user;
            $userFd = array_first($redis->zrangebyscore($this->client_id, $user->id, $user->id));


            // 修改状态
            $order->trip = Order::ORDER_TRIP_SEND;
            $order->save();

            // 通知用户
            $server->push(intval($userFd), new SocketJsonHandler(200, 'OK', 'received', [
                'order' => [
                    'id' => $order->id,
                    'order_sn' => $order->order_sn,
                    'user_id' => $order->user_id,
                    'driver_id' => $order->driver_id,
                    'status' => $order->status,
                    'status_text' => Order::$orderStatusMap[$order->status],
                    'trip' => $order->trip,
                    'trip_text' => Order::$orderTripMap[$order->trip],
                    'from_address' => $order->from_address,
                    'from_location' => $order->from_location,
                    'to_address' => $order->to_address,
                    'to_location' => $order->to_location,
                ],
            ]));

            // 返回结果
            $server->push($frame->fd, new SocketJsonHandler(200, 'OK', 'received', [
                'order' => [
                    'id' => $order->id,
                    'order_sn' => $order->order_sn,
                    'user_id' => $order->user_id,
                    'driver_id' => $order->driver_id,
                    'status' => $order->status,
                    'status_text' => Order::$orderStatusMap[$order->status],
                    'trip' => $order->trip,
                    'trip_text' => Order::$orderTripMap[$order->trip],
                    'from_address' => $order->from_address,
                    'from_location' => $order->from_location,
                    'to_address' => $order->to_address,
                    'to_location' => $order->to_location,
                ]
            ]));
        }
    }

    public function reachAction($server, $frame, $data, $driverId)
    {
        // {"action":"reach","data":{"order_id":"175"}}
        $validator = Validator::make($data, [
            'data' => ['required'],
            'data.order_id' => ['required',
                Rule::exists('orders', 'id')->where(function ($query) use ($driverId) {
                    $query->where('driver_id', $driverId)->where('status', Order::ORDER_STATUS_TRIPPING)
                        ->where('trip', Order::ORDER_TRIP_SEND);
                })
            ],
        ]);

        if ($validator->fails())
        {
            $server->push($frame->fd, new SocketJsonHandler(422, 'Unprocessable Entity', 'reach', $validator->errors()));
        } else
        {
            $redis = app('redis.connection');

            $order = Order::find($data['data']['order_id']);
            $user = $order->user;
            $userFd = array_first($redis->zrangebyscore($this->client_id, $user->id, $user->id));
            $driverInfo = json_decode(array_first($redis->zrangebyscore($this->driver_active, $driverId, $driverId)), true);


            // 修改状态
            $order->status = Order::ORDER_STATUS_COMPLETED;
            $order->trip = Order::ORDER_TRIP_REACH;
            $order->save();

            // 司机状态设置为忙碌
            $redis->zremrangebyscore($this->driver_active, $driverId, $driverId);
            $redis->zadd($this->driver_active, intval($driverId), json_encode([
                'id' => $driverId,
                'fd' => $frame->fd,
                'lat' => $driverInfo['lat'],
                'lng' => $driverInfo['lng'],
                'status' => self::DRIVER_STATUS_FREE,
            ]));

            // 通知用户
            $server->push(intval($userFd), new SocketJsonHandler(200, 'OK', 'reach', [
                'order' => [
                    'id' => $order->id,
                    'order_sn' => $order->order_sn,
                    'user_id' => $order->user_id,
                    'driver_id' => $order->driver_id,
                    'status' => $order->status,
                    'status_text' => Order::$orderStatusMap[$order->status],
                    'trip' => $order->trip,
                    'trip_text' => Order::$orderTripMap[$order->trip],
                    'from_address' => $order->from_address,
                    'from_location' => $order->from_location,
                    'to_address' => $order->to_address,
                    'to_location' => $order->to_location,
                ],
            ]));

            // 返回结果
            $server->push($frame->fd, new SocketJsonHandler(200, 'OK', 'reach', [
                'order' => [
                    'id' => $order->id,
                    'order_sn' => $order->order_sn,
                    'user_id' => $order->user_id,
                    'driver_id' => $order->driver_id,
                    'status' => $order->status,
                    'status_text' => Order::$orderStatusMap[$order->status],
                    'trip' => $order->trip,
                    'trip_text' => Order::$orderTripMap[$order->trip],
                    'from_address' => $order->from_address,
                    'from_location' => $order->from_location,
                    'to_address' => $order->to_address,
                    'to_location' => $order->to_location,
                ]
            ]));
        }
    }


}