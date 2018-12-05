<?php

namespace App\Sockets;

use App\Models\User;
use App\Tasks\TestTask;
use Hhxsv5\LaravelS\Swoole\Socket\WebSocket;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Hhxsv5\LaravelS\Swoole\Traits\TimerTrait;

class TestWebSocket extends WebSocket
{


    //    public function __construct(\swoole_server_port $port)
    //    {
    //        parent::__construct($port);
    //
    //
    //
    //
    //
    //    }

    public function onOpen(\swoole_websocket_server $server, \swoole_http_request $request)
    {
        info('New WebSocket connection', [$request]);

        \Cache::

        $userId = mt_rand(1000, 10000);


        app('swoole')->wsTable->set('uid:' . $userId, ['value' => $request->fd]);// 绑定uid到fd的映射
        app('swoole')->wsTable->set('fd:' . $request->fd, ['value' => $userId]);// 绑定fd到uid的映射

        $server->push($request->fd, 'Welcome to LaravelS');

        //        $timer = \swoole_timer_tick(1000, function () {
        ////            info($request->fd . "time start: swoole_timer_after");
        ////            $server->push($request->fd, "time start: swoole_timer_after");
        //            echo "after 3000ms.\n";
        //            info('xxx');
        ////            \swoole_timer_after(14000, function () {
        ////                echo "after 14000ms.\n";
        ////            });
        //
        //        });
        //
        //        $server->push($request->fd, $timer);

        $server->push($request->fd, 'Welcome to WebSocket.');
    }

    public function onClose(\swoole_websocket_server $server, $fd, $reactorId)
    {
        info('Close WebSocke connection', [$fd]);

        $uid = app('swoole')->wsTable->get('fd:' . $fd);
        if ($uid !== false)
        {
            app('swoole')->wsTable->del('uid:' . $uid['value']);// 解绑uid映射
        }
        app('swoole')->wsTable->del('fd:' . $fd);// 解绑fd映射
        $server->push($fd, 'Goodbye');

    }

    public function onMessage(\swoole_websocket_server $server, \swoole_websocket_frame $frame)
    {

        //        info("receive from {$frame->fd}:{$frame->data},opcode:{$frame->opcode},fin:{$frame->finish}\n");

        $data = explode(',', $frame->data);


        if ($data[0] == 'exit')
        {
            $server->close($frame->fd);
        }

        if ($data[0] == 'task')
        {
            $arr = [
                'task' => !empty($data[1]) ? $data[1] : 'null',
                'fd' => $frame->fd,
            ];
            $task = new TestTask($arr);
            // $task->delay(3);// 延迟3秒投放任务
            $ret = Task::deliver($task);

            $server->push($frame->fd, "task:{$ret}");
        }

        if ($data[0] == 'time')
        {


        }

        if ($data[0] == 'mysql')
        {
            $user = new User();
            $user->phone = !empty($data[1]) ? $data[1] : random_int(1, 9999);
            $user->last_active_at = now();
            $user->save();

            $server->push($frame->fd, $user);
        }

        if ($data[0] == 'seedTable')
        {
            $server->push($frame->fd, "setSable start");


            foreach (app('swoole')->wsTable as $key => $row)
            {
                if (strpos($key, 'uid:') === 0)
                {
                    $server->push($row['value'], 'Broadcast: ' . date('Y-m-d H:i:s'));// 广播
                }
            }


        }

        if ($data[0] == 'getTable')
        {
            $server->push($frame->fd, "getTable start");

            foreach (app('swoole')->wsTable as $key => $row)
            {
                //                info($key . '---' . $row);
                $server->push($frame->fd, $key . '---' . json_encode($row));
            }
        }


        foreach ($data as $datum)
        {
            $server->push($frame->fd, "server : {$datum}");

        }


        //        $server->send($fd, 'LaravelS: ' . $frame);
        //        if ($data === "quit\r\n")
        //        {
        //            $port = $this->swoolePort; //获得`swoole_server_port`对象
        //
        //            $server->send($fd, 'LaravelS: bye' . PHP_EOL);
        //            $server->close($fd);
        //        }
    }

}