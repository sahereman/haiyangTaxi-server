<?php

namespace App\Tasks;

use Hhxsv5\LaravelS\Swoole\Task\Task;


class TestTask extends Task
{
    private $data;
    private $result;

    public function __construct($data)
    {
        $this->data = $data;
    }

    // 处理任务的逻辑，运行在Task进程中，不能投递任务
    public function handle()
    {

        info(__CLASS__ . ':handle start : ', $this->data);

        sleep(5);

        $this->result = $this->data;
        $this->result['dasd'] = 'dasd';

        // throw new \Exception('an exception');// handle时抛出的异常上层会忽略，并记录到Swoole日志，需要开发者try/catch捕获处理
    }

    // 可选的，完成事件，任务处理完后的逻辑，运行在Worker进程中，可以投递任务
    public function finish()
    {

        info(__CLASS__ . ':finish start', [$this->result]);

    }
}