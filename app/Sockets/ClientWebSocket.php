<?php

namespace App\Sockets;

use App\Handlers\SocketJsonHandler;
use App\Handlers\TencentMapHandler;
use App\Models\Order;
use App\Models\OrderSet;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Ramsey\Uuid\Uuid;

class ClientWebSocket extends WebSocket
{

    /**
     * 客户端主动的action列表
     * @var array
     */
    private $actions = [
        'beat',        // 发送心跳包
        'nearby',      // 附近车辆位置
        'publish',     // 发起打车订单寻找车辆
        'withdraw',    // 取消打车
        'meetRefresh', // 刷新司机正在来的位置
        'userCancel',  // 用户主动取消订单
        'close',       // 关闭连接
    ];


    public function onOpen(\swoole_websocket_server $server, \swoole_http_request $request)
    {
        $request->get = $request->get ?? array();

        //        $validator = Validator::make($request->get, [
        //            'token' => ['required', 'string'],
        //        ]);
        //
        //        if ($validator->fails())
        //        {
        //            $server->push($request->fd, new SocketJsonHandler(401, 'Unauthorized'));
        //            $server->close($request->fd);
        //        }
        //
        //        try
        //        {
        //            $user = Auth::guard('client')->setToken($request->get['token'])->user();
        //        } catch (\Exception $exception)
        //        {
        //            $server->push($request->fd, new SocketJsonHandler(401, 'Unauthorized'));
        //            $server->close($request->fd);
        //        }

        $user = User::find($request->get['token']);


        $redis = app('redis.connection');


        $redis->zremrangebyscore($this->client_id, $user->id, $user->id); // 删除用户id关联
        $redis->zadd($this->client_fd, intval($request->fd), $user->id);
        $redis->zadd($this->client_id, intval($user->id), $request->fd);

        $server->push($request->fd, new SocketJsonHandler(200, 'OK', 'open'));
    }

    public function onClose(\swoole_websocket_server $server, $fd, $reactorId)
    {
        $redis = app('redis.connection');
        $userId = array_first($redis->zrangebyscore($this->client_fd, $fd, $fd));


        $redis->zremrangebyscore($this->client_fd, $fd, $fd); // 删除fd关联
        $redis->zremrangebyscore($this->client_id, $userId, $userId); // 删除用户id关联
        OrderSet::where('user_id', $userId)->delete(); // 删除订单集合 该用户的订单
    }

    public function onMessage(\swoole_websocket_server $server, \swoole_websocket_frame $frame)
    {
        // {"action":"beat"}
        $redis = app('redis.connection');
        $userId = array_first($redis->zrangebyscore($this->client_fd, $frame->fd, $frame->fd));
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
                case 'nearby':
                    $this->nearbyAction($server, $frame, $data, $userId);
                    break;
                case 'publish' :
                    $this->publishAction($server, $frame, $data, $userId);
                    break;
                case 'meetRefresh':
                    $this->meetRefreshAction($server, $frame, $data, $userId);
                    break;
                case 'userCancel':
                    $this->userCancelAction($server, $frame, $data, $userId);
                    break;
                case 'close' :
                    $server->close($frame->fd);
                    break;
                default:
                    $server->push($frame->fd, new SocketJsonHandler(422, 'Unprocessable Entity', 'message'));
                    break;
            }
        }

    }

    public function nearbyAction($server, $frame, $data, $userId)
    {
        // {"action":"nearby","data":{"lat":"36.111114","lng":"120.444444"}}
//        $validator = Validator::make($data, [
//            'data' => ['required'],
//            'data.lat' => ['required', 'numeric'],
//            'data.lng' => ['required', 'numeric'],
//        ]);
//
//        if ($validator->fails())
//        {
//            $server->push($frame->fd, new SocketJsonHandler(422, 'Unprocessable Entity', 'nearby', $validator->errors()));
//        } else
//        {
//            $redis = app('redis.connection');
//
//            $driverInfo = json_decode(array_first($redis->zrangebyscore($this->driver_active, $driverId, $driverId)), true);
//            $redis->zremrangebyscore($this->driver_active, $driverId, $driverId);
//            $redis->zadd($this->driver_active, intval($driverId), json_encode([
//                'id' => $driverId,
//                'fd' => $frame->fd,
//                'lat' => $data['data']['lat'],
//                'lng' => $data['data']['lng'],
//                'status' => $driverInfo['status'],
//            ]));
//
//            $server->push($frame->fd, new SocketJsonHandler(200, 'OK', 'nearby'));
//        }
    }

    public function publishAction($server, $frame, $data, $userId)
    {
        /*
        {"action":"publish","data":{"from_address":"CBD万达广场","from_location":{"lat":"36.088436","lng":"120.379145"},
        "to_address":"五四广场","to_location":{"lat":"36.062030","lng":"120.384940"}}}
        */

        $validator = Validator::make(array_add($data, 'user', $userId), [
            'data' => ['required'],
            'data.from_address' => ['required'],
            'data.from_location.lat' => ['required', 'numeric'],
            'data.from_location.lng' => ['required', 'numeric'],
            'data.to_address' => ['required'],
            'data.to_location.lat' => ['required', 'numeric'],
            'data.to_location.lng' => ['required', 'numeric'],
            'user' => ['unique:order_sets,user_id']
        ], [
            'user.unique' => '已经存在进行中的订单'
        ]);

        if ($validator->fails())
        {
            $server->push($frame->fd, new SocketJsonHandler(422, 'Unprocessable Entity', 'location', $validator->errors()));
        } else
        {
            $redis = app('redis.connection');

            // 加入 orderSet 订单集合表
            $set = OrderSet::create([
                'user_id' => $userId,
                'from_address' => $data['data']['from_address'],
                'from_location' => ['lat' => $data['data']['from_location']['lat'], 'lng' => $data['data']['from_location']['lng']],
                'to_address' => $data['data']['to_address'],
                'to_location' => ['lat' => $data['data']['to_location']['lat'], 'lng' => $data['data']['to_location']['lng']],
                'created_at' => now(),
            ]);

            // 通知车辆
            $active_drivers = $redis->zrange($this->driver_active, 0, -1);

            $driver_locations = array();

            foreach ($active_drivers as $key => $driver)
            {
                $active_drivers[$key] = json_decode($driver, true);
                $driver_locations[] = ['lat' => $active_drivers[$key]['lat'], 'lng' => $active_drivers[$key]['lng']];

                $server->push($active_drivers[$key]['fd'], new SocketJsonHandler(200, 'OK', 'notify', [
                    'order_key' => $set->key,
                    'from_address' => $data['data']['from_address'],
                    'from_location' => ['lat' => $data['data']['from_location']['lat'], 'lng' => $data['data']['from_location']['lng']],
                    'distance' => 1800, //距离单位(米)
                    'duration' => 600,  //时间单位(秒)
                ]));
            }

            $server->push($frame->fd, new SocketJsonHandler(200, 'OK', 'publish'));
        }
    }

    public function meetRefreshAction($server, $frame, $data, $userId)
    {
        // {"action":"meetRefresh","data":{"order_id":"176"}}
        $validator = Validator::make($data, [
            'data' => ['required'],
            'data.order_id' => ['required',
                Rule::exists('orders', 'id')->where(function ($query) use ($userId) {
                    $query->where('user_id', $userId)->where('status', Order::ORDER_STATUS_TRIPPING)
                        ->where('trip', Order::ORDER_TRIP_MEET);
                })
            ],
        ]);

        if ($validator->fails())
        {
            $server->push($frame->fd, new SocketJsonHandler(422, 'Unprocessable Entity', 'meetRefresh', $validator->errors()));
        } else
        {
            $redis = app('redis.connection');

            $order = Order::find($data['data']['order_id']);
            $driverInfo = json_decode(array_first($redis->zrangebyscore($this->driver_active, $order->driver_id, $order->driver_id)), true);

            $server->push($frame->fd, new SocketJsonHandler(200, 'OK', 'meetRefresh', [
                'driver' => [
                    'id' => $driverInfo['id'],
                    'location' => [
                        'lat' => $driverInfo['lat'],
                        'lng' => $driverInfo['lng'],
                    ],
                    'distance' => 1800, //距离单位(米)
                    'duration' => 600,  //时间单位(秒)
                ]
            ]));
        }

    }

    public function userCancelAction($server, $frame, $data, $userId)
    {
        // {"action":"userCancel","data":{"close_reason":"用户测试取消","order_id":"175"}}
        $validator = Validator::make($data, [
            'data' => ['required'],
            'data.close_reason' => ['required'],
            'data.order_id' => ['required',
                Rule::exists('orders', 'id')->where(function ($query) use ($userId) {
                    $query->where('user_id', $userId)->where('status', Order::ORDER_STATUS_TRIPPING);
                })
            ],
        ]);

        if ($validator->fails())
        {
            $server->push($frame->fd, new SocketJsonHandler(422, 'Unprocessable Entity', 'userCancel', $validator->errors()));
        } else
        {
            $redis = app('redis.connection');

            $order = Order::find($data['data']['order_id']);
            $driver = $order->driver;
            $driverFd = array_first($redis->zrangebyscore($this->driver_id, $driver->id, $driver->id));


            // 修改状态
            $order->status = Order::ORDER_STATUS_CLOSED;
            $order->close_from = Order::ORDER_CLOSE_FROM_USER;
            $order->close_reason = $data['data']['close_reason'];
            $order->closed_at = now();

            $order->save();

            // 通知司机
            $server->push(intval($driverFd), new SocketJsonHandler(200, 'OK', 'userCancel', [
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
            $server->push($frame->fd, new SocketJsonHandler(200, 'OK', 'userCancel'));
        }
    }

}