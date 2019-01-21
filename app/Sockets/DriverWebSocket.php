<?php

namespace App\Sockets;

use App\Handlers\DriverHandler;
use App\Handlers\SocketJsonHandler;
use App\Handlers\Tools\Coordinate;
use App\Models\Driver;
use App\Models\DriverSocketToken;
use App\Models\Order;
use App\Models\OrderSet;
use App\Rules\RedisZsetExists;
use App\Rules\RedisZsetUnique;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

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


        $validator = Validator::make($request->get, [
            'token' => ['required', 'string'],
        ]);

        if ($validator->fails())
        {
            $server->push($request->fd, new SocketJsonHandler(401, 'Unauthorized', 'open'));
            $server->close($request->fd);
            return false;
        }


        try
        {
            $token = DriverSocketToken::where('token', $request->get['token'])->where('expired_at', '>', now())->first();

            if ($token == null)
            {
                throw new TokenInvalidException();
            }

            $driver = $token->driver;

            /*开发调试*/
//            $driver = Driver::find($request->get['token']);
            
            $redis = app('redis.connection');

            info($driver);
            $driverFd = array_first($redis->zrangebyscore($this->driver_id, $driver->id, $driver->id));
            if ($driverFd != null)
            {
                $server->close($driverFd);
            }

            $redis->zremrangebyscore($this->driver_id, $driver->id, $driver->id); // 删除司机id关联
            $redis->zadd($this->driver_fd, intval($request->fd), $driver->id);
            $redis->zadd($this->driver_id, intval($driver->id), $request->fd);

            $server->push($request->fd, new SocketJsonHandler(200, 'OK', 'open'));
        } catch (\ReflectionException $exception)
        {
            Log::error('ReflectionException : laravels reload');
            Artisan::call('laravels', [
                'action' => 'reload'
            ]);
            $server->push($request->fd, new SocketJsonHandler(429, 'Too Many Requests', 'open'));
            $server->close($request->fd);
            return false;
        } catch (\Exception $exception)
        {
            info($exception);
            $server->push($request->fd, new SocketJsonHandler(401, 'Unauthorized', 'open'));
            $server->close($request->fd);
            return false;
        }


    }

    public function onClose(\swoole_websocket_server $server, $fd, $reactorId)
    {
        $redis = app('redis.connection');
        $driverId = array_first($redis->zrangebyscore($this->driver_fd, $fd, $fd));


        $redis->zremrangebyscore($this->driver_fd, $fd, $fd); // 删除fd关联
        $redis->zremrangebyscore($this->driver_id, $driverId, $driverId); // 删除司机id关联
        $redis->zremrangebyscore($this->driver_active, $driverId, $driverId); // 上班的所有司机中 车辆信息

        $server->push($fd, new SocketJsonHandler(200, 'OK', 'close'));
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
                    $server->push($frame->fd, new SocketJsonHandler(200, 'OK', 'close'));
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
        {"action":"active","data":{"lat":"36.094054","lng":"120.402861"}}   2223m
        {"action":"active","data":{"lat":"36.096135","lng":"120.392904"}}   1505m
        {"action":"active","data":{"lat":"36.092936","lng":"120.381339"}}   538m
        {"action":"active","data":{"lat":"36.089338","lng":"120.380437"}}   153m
        {"action":"active","data":{"lat":"36.087153","lng":"120.379086"}}   142m
        {"action":"active","data":{"lat":"36.067540","lng":"120.301830"}}   7334m
        五四广场 :
        {"action":"active","data":{"lat":"36.062030","lng":"120.384940"}}
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

            $this->activeUpdate($driverId, [
                'id' => $driverId,
                'fd' => $frame->fd,
                'lat' => $data['data']['lat'],
                'lng' => $data['data']['lng'],
                'status' => self::DRIVER_STATUS_FREE,
                'angle' => random_int(1, 359),
            ]);

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
            $angle = DriverHandler::calcAngle(new Coordinate($driverInfo['lat'], $driverInfo['lng']), new Coordinate($data['data']['lat'], $data['data']['lng']));

            $this->activeUpdate($driverId, [
                'lat' => $data['data']['lat'],
                'lng' => $data['data']['lng'],
                'angle' => $angle == null ? $driverInfo['angle'] : $angle,
            ]);

            $server->push($frame->fd, new SocketJsonHandler(200, 'OK', 'location'));
        }
    }


    /**
     * @param $server
     * @param $frame
     * @param $data
     * @param $driverId
     * @throws \Throwable
     */
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
            /*事物*/
            try
            {
                DB::transaction(function () use ($server, $frame, $data, $driverId) {
                    $redis = app('redis.connection');
                    $set = OrderSet::lockForUpdate()->find($data['data']['order_key']);
                    if ($set == false)
                    {
                        throw new \Exception();
                    }
                    $user = $set->user;
                    $driver = Driver::find($driverId);
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
                    $this->activeUpdate($driverId, [
                        'status' => self::DRIVER_STATUS_BUSY,
                    ]);


                    /* (用户) 车辆已接单正在赶来*/
                    $driverInfo = json_decode(array_first($redis->zrangebyscore($this->driver_active, $driverId, $driverId)), true);
                    $distance = DriverHandler::calcDistance(new Coordinate($order->from_location['lat'], $order->from_location['lng']),
                        new Coordinate($driverInfo['lat'], $driverInfo['lng']));

                    $server->push(intval($userFd), new SocketJsonHandler(200, 'OK', 'meet', [
                        'driver' => array_merge($driverInfo, [
                            'distance' => $distance,
                            'cart_number' => $driver->cart_number,
                            'phone' => $driver->phone,
                            'order_count' => $driver->order_count,
                        ]),
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

                });
            } catch (\Exception $e)
            {
                info($e);
                $server->push($frame->fd, new SocketJsonHandler(422, 'Unprocessable Entity', 'accept', [
                    'data.order_key' => ['订单不存在或被抢单']
                ]));
            }

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
            if (Order::where('id', $data['data']['order_id'])->where('status', Order::ORDER_STATUS_CLOSED)->first())
            {
                $server->push($frame->fd, new SocketJsonHandler(200, 'OK', 'driverCancel'));
            } else
            {
                $server->push($frame->fd, new SocketJsonHandler(422, 'Unprocessable Entity', 'driverCancel', $validator->errors()));
            }
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

            // 司机状态设置为闲置
            $this->activeUpdate($driverId, [
                'status' => self::DRIVER_STATUS_FREE,
            ]);


            // 返回结果
            $server->push($frame->fd, new SocketJsonHandler(200, 'OK', 'driverCancel'));

            /* (用户) 司机已将订单取消的通知*/
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

            /* (用户) 已上车,正在前往目的地*/
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
            $driver = $order->driver;
            $userFd = array_first($redis->zrangebyscore($this->client_id, $user->id, $user->id));

            //增加司机总接单数
            $driver->increment('order_count');

            // 修改状态
            $order->status = Order::ORDER_STATUS_COMPLETED;
            $order->trip = Order::ORDER_TRIP_REACH;
            $order->save();

            // 司机状态设置为闲置
            $this->activeUpdate($driverId, [
                'status' => self::DRIVER_STATUS_FREE,
            ]);

            /* (用户) 已到达目的地,行程结束*/
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