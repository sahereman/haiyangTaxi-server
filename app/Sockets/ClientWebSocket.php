<?php

namespace App\Sockets;

use App\Handlers\DriverHandler;
use App\Handlers\SocketJsonHandler;
use App\Handlers\Tools\Coordinate;
use App\Jobs\DriverNotify;
use App\Models\Config;
use App\Models\Order;
use App\Models\OrderSet;
use App\Models\UserSocketToken;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class ClientWebSocket extends WebSocket
{

    /**
     * 客户端主动的action列表
     * @var array
     */
    private $actions = [
        'beat',        // 发送心跳包
        'nearby',      // 查找附近车辆位置
        'publish',     // 发起打车订单寻找车辆
        'withdraw',    // 主动取消打车
        'meetRefresh', // 刷新车辆正在来的位置
        'userCancel',  // 用户主动取消订单
        'close',       // 主动关闭连接
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
            $token = UserSocketToken::where('token', $request->get['token'])->where('expired_at', '>', now())->first();

            if ($token == null)
            {
                throw new TokenInvalidException();
            }

            $user = $token->user;

            /*开发调试*/
//            $user = User::find($request->get['token']);

            $redis = app('redis.connection');

            info($user);
            $userFd = array_first($redis->zrangebyscore($this->client_id, $user->id, $user->id));
            if ($userFd != null)
            {
                $server->close($userFd);
            }

            $redis->zremrangebyscore($this->client_id, $user->id, $user->id); // 删除用户id关联
            $redis->zadd($this->client_fd, intval($request->fd), $user->id);
            $redis->zadd($this->client_id, intval($user->id), $request->fd);

            /* (用户) Socket连接成功*/
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
        $userId = array_first($redis->zrangebyscore($this->client_fd, $fd, $fd));


        $redis->zremrangebyscore($this->client_fd, $fd, $fd); // 删除fd关联
        $redis->zremrangebyscore($this->client_id, $userId, $userId); // 删除用户id关联
        OrderSet::where('user_id', $userId)->delete(); // 删除订单集合 该用户的订单

        /* (用户) Socket已断开连接*/
        $server->push($fd, new SocketJsonHandler(200, 'OK', 'close'));
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
                    /* (用户) 心跳包结果*/
                    $server->push($frame->fd, new SocketJsonHandler(200, 'OK', 'beat'));
                    break;
                case 'nearby':
                    $this->nearbyAction($server, $frame, $data, $userId);
                    break;
                case 'publish' :
                    $this->publishAction($server, $frame, $data, $userId);
                    break;
                case 'withdraw' :
                    $this->withdrawAction($server, $frame, $data, $userId);
                    break;
                case 'meetRefresh':
                    $this->meetRefreshAction($server, $frame, $data, $userId);
                    break;
                case 'userCancel':
                    $this->userCancelAction($server, $frame, $data, $userId);
                    break;
                case 'close' :
                    $server->push($frame->fd, new SocketJsonHandler(200, 'OK', 'close'));
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
        // {"action":"nearby","data":{"lat":"36.088436","lng":"120.379145"}}
        $validator = Validator::make($data, [
            'data' => ['required'],
            'data.lat' => ['required', 'numeric'],
            'data.lng' => ['required', 'numeric'],
        ]);

        if ($validator->fails())
        {
            $server->push($frame->fd, new SocketJsonHandler(422, 'Unprocessable Entity', 'nearby', $validator->errors()));
        } else
        {

            $redis = app('redis.connection');

            // 查找附近闲置车辆
            $active_drivers = $redis->zrange($this->driver_active, 0, -1);
            $drivers = DriverHandler::getDrivers($active_drivers);
            $drivers = DriverHandler::findFreeDrivers($drivers);

            $drivers = DriverHandler::findDistanceRangeDrivers($drivers, $data['data']['lat'], $data['data']['lng'],0,99999);

            /* (用户) 附近车辆数据*/
            $server->push($frame->fd, new SocketJsonHandler(200, 'OK', 'nearby', [
                'drivers' => $drivers
            ]));
        }
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
//            'user' => ['unique:order_sets,user_id']
        ], [
            'user.unique' => '已经存在进行中的订单'
        ]);

        if ($validator->fails())
        {
            $server->push($frame->fd, new SocketJsonHandler(422, 'Unprocessable Entity', 'publish', $validator->errors()));
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
            $drivers = DriverHandler::getDrivers($active_drivers);
            $drivers = DriverHandler::findFreeDrivers($drivers);
            $drivers = DriverHandler::findDistanceRangeDrivers($drivers, $set->from_location['lat']
                , $set->from_location['lng'], 0, Config::config('order_notify_4'));


            Task::deliver(new DriverNotify($set->key, $drivers));


            /* (用户) 正在寻找车辆中*/
            $server->push($frame->fd, new SocketJsonHandler(200, 'OK', 'publish', [
                'order_key' => $set->key,
                'from_address' => $set->from_address,
                'from_location' => $set->from_location,
                'to_address' => $set->to_address,
                'to_location' => $set->to_location,
            ]));
        }
    }

    public function withdrawAction($server, $frame, $data, $userId)
    {
        // {"action":"withdraw","data":{"order_key":"eacd945c61fc485c87e4c17eae60d02b"}}
        $validator = Validator::make($data, [
            'data' => ['required'],
            'data.order_key' => ['required',
                Rule::exists('order_sets', 'key')->where(function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                })
            ],
        ]);

        if ($validator->fails())
        {
            $server->push($frame->fd, new SocketJsonHandler(422, 'Unprocessable Entity', 'withdraw', $validator->errors()));
        } else
        {
            // orderSet 订单集合表 删除该记录
            OrderSet::find($data['data']['order_key'])->delete();


            /* (用户) 通知用户已取消打车*/
            $server->push($frame->fd, new SocketJsonHandler(200, 'OK', 'withdraw'));
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

            if (empty($driverInfo))
            {
                $server->push($frame->fd, new SocketJsonHandler(422, 'Unprocessable Entity', 'meetRefresh', [
                    'data.order_id' => ['司机已下班,请取消订单']
                ]));
                return false;
            }

            $distance = DriverHandler::calcDistance(new Coordinate($order->from_location['lat'], $order->from_location['lng']),
                new Coordinate($driverInfo['lat'], $driverInfo['lng']));


            /* (用户) 刷新车辆位置返回的数据*/
            $server->push($frame->fd, new SocketJsonHandler(200, 'OK', 'meetRefresh', [
                'driver' => array_merge($driverInfo, [
                    'distance' => $distance,
                ])
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
            if (Order::where('id', $data['data']['order_id'])->where('status', Order::ORDER_STATUS_CLOSED)->first())
            {
                /* (用户) 主动取消打车成功*/
                $server->push($frame->fd, new SocketJsonHandler(200, 'OK', 'userCancel'));
            } else
            {
                $server->push($frame->fd, new SocketJsonHandler(422, 'Unprocessable Entity', 'userCancel', $validator->errors()));
            }
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

            // 司机状态设置为闲置
            $this->activeUpdate($driver->id, [
                'status' => self::DRIVER_STATUS_FREE,
            ]);

            /* (用户) 主动取消打车成功*/
            $server->push($frame->fd, new SocketJsonHandler(200, 'OK', 'userCancel'));


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

        }
    }

}