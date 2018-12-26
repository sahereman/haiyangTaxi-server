<?php

namespace App\Jobs;

use App\Handlers\DriverHandler;
use App\Handlers\SocketJsonHandler;
use App\Models\OrderSet;
use Hhxsv5\LaravelS\Swoole\Task\Task;


class DriverNotify extends Task
{
    private $drivers;
    private $order_set;
    private $startDistance;
    private $endDistance;
    private $index; //job运行次数

    public function __construct($set_key, $drivers, $startDistance = 0, $endDistance = 500, $index = 0)
    {
        $this->drivers = $drivers;
        $this->order_set = OrderSet::find($set_key);
        $this->startDistance = $startDistance;
        $this->endDistance = $endDistance;
        $this->index = $index;
    }

    // 处理任务的逻辑，运行在Task进程中，不能投递任务
    public function handle()
    {
        if (empty($this->order_set))
        {
            $this->drivers = array();
            return false;
        }

        $server = app('swoole');

        $drivers = DriverHandler::findDistanceRangeDrivers($this->drivers, $this->order_set->from_location['lat']
            , $this->order_set->from_location['lng'], $this->startDistance, $this->endDistance);

        //        info($this->drivers);
        //
        //        info($drivers);

        foreach ($drivers as $driver)
        {
            //司机只通知一次
            $unset_key = array_search($driver['id'], array_column($this->drivers, 'id'));
            unset($this->drivers[$unset_key]);
            $this->drivers = array_values($this->drivers);

            if (!empty($server->connection_info($driver['fd'])))
            {
                $server->push($driver['fd'], new SocketJsonHandler(200, 'OK', 'notify', [
                    'order_key' => $this->order_set->key,
                    'from_address' => $this->order_set->from_address,
                    'from_location' => $this->order_set->from_location,
                    'distance' => $driver['distance'], //距离单位(米)
                    //                        'duration' => $driver['duration'],  //时间单位(秒)
                ]));
            }
        }

        // 最终通知不再通知车辆
        if ($this->index == 3)
        {
            $this->drivers = array();
        }


        if (empty($drivers))
        {
            // 大范围通知
            $this->index = 3;
        } else
        {
            $this->index++;
        }
    }

    // 可选的，完成事件，任务处理完后的逻辑，运行在Worker进程中，可以投递任务
    public function finish()
    {
        if (!empty($this->drivers))
        {
            switch ($this->index)
            {
                case 1 :
                    $startDistance = $this->startDistance += 501;
                    $endDistance = $this->endDistance += 500;
                    $driverNotify = new DriverNotify($this->order_set->key, $this->drivers, $startDistance, $endDistance, $this->index);
                    $driverNotify->delay(10);
                    Task::deliver($driverNotify);
                    //                    sleep(20);
                    //                    Task::deliver(new DriverNotify($this->order_set->key, $this->drivers, $startDistance, $endDistance, $this->index));
                    break;
                case 2 :
                    $startDistance = $this->startDistance += 500;
                    $endDistance = $this->endDistance += 500;
                    $driverNotify = new DriverNotify($this->order_set->key, $this->drivers, $startDistance, $endDistance, $this->index);
                    $driverNotify->delay(10);
                    Task::deliver($driverNotify);
                    break;
                case 3:
                    $startDistance = $this->startDistance += 500;
                    $endDistance = 9999;
                    $driverNotify = new DriverNotify($this->order_set->key, $this->drivers, $startDistance, $endDistance, $this->index);
                    $driverNotify->delay(10);
                    Task::deliver($driverNotify);
                    break;
                default:
                    break;
            }
        }
    }
}