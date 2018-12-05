<?php

namespace App\Sockets;

use App\Handlers\SocketJsonHandler;
use App\Handlers\TencentMapHandler;
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
        'publish',     // 发起打车订单寻找车辆
        'withdraw',    // 取消打车
        'meet',        // 司机已接单正在来的路上
        'meetRefresh', // 刷新司机正在来的位置
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

        $redis->zadd($this->client_fd, intval($request->fd), $user->id);

        $server->push($request->fd, new SocketJsonHandler(200, 'OK', 'open'));
    }

    public function onClose(\swoole_websocket_server $server, $fd, $reactorId)
    {
        $redis = app('redis.connection');
        $userId = array_first($redis->zrangebyscore($this->driver_fd, $fd, $fd));


        $redis->zremrangebyscore($this->client_fd, $fd, $fd);
    }

    public function onMessage(\swoole_websocket_server $server, \swoole_websocket_frame $frame)
    {
        /*
        {"action":"publish","data":{"from_address":"诺德广场","from_location":{"lat":"36.111114","lng":"120.444444"},
        "to_address":"五四广场","to_location":{"lat":"36.062030","lng":"120.384940"}}}
        */


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
                case 'publish' :
                    $this->publishAction($server, $frame, $data, $userId);
                    break;
                default:
                    $server->push($frame->fd, new SocketJsonHandler(422, 'Unprocessable Entity', 'message'));
                    break;
            }
        }

    }

    public function publishAction($server, $frame, $data, $userId)
    {
        $validator = Validator::make($data, [
            'data' => ['required'],
            'data.from_address' => ['required'],
            'data.from_location.lat' => ['required', 'numeric'],
            'data.from_location.lng' => ['required', 'numeric'],
            'data.to_address' => ['required'],
            'data.to_location.lat' => ['required', 'numeric'],
            'data.to_location.lng' => ['required', 'numeric'],
        ]);

        if ($validator->fails())
        {
            $server->push($frame->fd, new SocketJsonHandler(422, 'Unprocessable Entity', 'location', $validator->errors()));
        } else
        {
            $redis = app('redis.connection');
            $uuid = Uuid::uuid4();


            // 加入 order 集合
            $redis->hset($this->order_set, $uuid, json_encode([
                'user_id' => $userId,
                'from_address' => $data['data']['from_address'],
                'from_location' => ['lat' => $data['data']['from_location']['lat'], 'lng' => $data['data']['from_location']['lng']],
                'to_address' => $data['data']['to_address'],
                'to_location' => ['lat' => $data['data']['to_location']['lat'], 'lng' => $data['data']['to_location']['lng']],
                'create_at' => now()->toDateTimeString(),
            ]));

            // 通知车辆
            $active_drivers = $redis->zrange($this->driver_active, 0, -1);

            $driver_locations = array();

            foreach ($active_drivers as $key => $driver)
            {
                $active_drivers[$key] = json_decode($driver, true);
                $driver_locations[] = ['lat' => $active_drivers[$key]['lat'], 'lng' => $active_drivers[$key]['lng']];

                $server->push($active_drivers[$key]['fd'], new SocketJsonHandler(200, 'OK', 'notify', [
                    'order_key' => $uuid,
                    'from_address' => $data['data']['from_address'],
                    'distance' => 2000, //距离单位(米)
                ]));
            }

            $server->push($frame->fd, new SocketJsonHandler(200, 'OK', 'publish'));
        }
    }

}