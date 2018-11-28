<?php

namespace App\Processes;

use App\Tasks\TestTask;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;

class TestProcess implements CustomProcessInterface
{
    public static function getName()
    {
        // 进程名称
        return 'test';
    }

    public static function isRedirectStdinStdout()
    {
        // 是否重定向输入输出
        return false;
    }

    public static function getPipeType()
    {
        // 管道类型：0不创建管道，1创建SOCK_STREAM类型管道，2创建SOCK_DGRAM类型管道
        return 0;
    }

    public static function callback(\swoole_server $swoole)
    {
        // 进程运行的代码，不能退出，一旦退出Manager进程会自动再次创建该进程。
        \Log::info(__METHOD__, [posix_getpid(), $swoole->stats()]);
//        while (true)
//        {
//            \Log::info('Do something');
//            sleep(10);
//            // 自定义进程中也可以投递Task，但不支持Task的finish()回调。
//            // 注意：
//            // 1.参数2需传true
//            // 2.config/laravels.php中修改配置task_ipc_mode为1或2，参考 https://wiki.swoole.com/wiki/page/296.html
//            $ret = Task::deliver(new TestTask(['process' => 'this process']), true);
//
//            info($ret);
//
//        }
    }
}