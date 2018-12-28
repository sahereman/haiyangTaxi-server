<?php
/**
 * @see https://github.com/hhxsv5/laravel-s/blob/master/Settings-CN.md  Chinese
 * @see https://github.com/hhxsv5/laravel-s/blob/master/Settings.md  English
 */
return [
    'listen_ip' => env('LARAVELS_LISTEN_IP', '127.0.0.1'),
    'listen_port' => env('LARAVELS_LISTEN_PORT', 5200),
    'socket_type' => env('LARAVELS_SOCKET_TYPE', defined('SWOOLE_SOCK_TCP') ? \SWOOLE_SOCK_TCP : 1),
    'enable_gzip' => env('LARAVELS_ENABLE_GZIP', false),
    'enable_coroutine' => false,
    'server' => env('LARAVELS_SERVER', 'LaravelS'),
    'handle_static' => env('LARAVELS_HANDLE_STATIC', false),
    'laravel_base_path' => env('LARAVEL_BASE_PATH', base_path()),
    'inotify_reload' => [
        'enable' => env('LARAVELS_INOTIFY_RELOAD', false),
        'watch_path' => base_path(),
        'file_types' => ['.php'],
        'excluded_dirs' => [base_path('vendor')],
        'log' => true,
    ],
    'websocket' => [
        'enable' => true,
        'handler' => \App\Services\WebSocketService::class,
    ],
    'sockets' => [
        [
            'host' => '0.0.0.0',
            'port' => 5301,
            'type' => \SWOOLE_SOCK_TCP,
            'settings' => [
                'open_http_protocol' => true,
                'open_websocket_protocol' => true,
            ],
            'handler' => \App\Sockets\ClientWebSocket::class,
        ],
        [
            'host' => '0.0.0.0',
            'port' => 5302,
            'type' => \SWOOLE_SOCK_TCP,
            'settings' => [
                'open_http_protocol' => true,
                'open_websocket_protocol' => true,
            ],
            'handler' => \App\Sockets\DriverWebSocket::class,
        ],
    ],
    'processes' => [
        //        \App\Processes\TestProcess::class,
    ],
    'timer' => [
        'enable' => false, // 启用Timer
        'jobs' => [ // 注册的定时任务类列表
            // 启用LaravelScheduleJob来执行`php artisan schedule:run`，每分钟一次，替代Linux Crontab
            // \Hhxsv5\LaravelS\Illuminate\LaravelScheduleJob::class,
            // 两种配置参数的方式：
            // [\App\Jobs\Timer\TestCronJob::class, [1000, true]], // 注册时传入参数
            //            \App\Jobs\Timers\TestCronJob::class, // 重载对应的方法来返回参数
        ],
    ],
    'events' => [
    ],
    'swoole_tables' => [
        // 场景：WebSocket中UserId与FD绑定
        //...继续定义其他Table
    ],
    'register_providers' => [
        /* 重置中间件 Provider*/
        \Dingo\Api\Provider\DingoServiceProvider::class,
        \Dingo\Api\Provider\HttpServiceProvider::class,

        /* 重置 Auth Provider*/
        Illuminate\Auth\AuthServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        \Tymon\JWTAuth\Providers\LaravelServiceProvider::class,
    ],
    'swoole' => [
        'daemonize' => env('LARAVELS_DAEMONIZE', false),
        'dispatch_mode' => 2,
        'reactor_num' => function_exists('\swoole_cpu_num') ? \swoole_cpu_num() * 2 : 4,
        'worker_num' => function_exists('\swoole_cpu_num') ? \swoole_cpu_num() * 2 : 8,
        'task_worker_num' => function_exists('\swoole_cpu_num') ? \swoole_cpu_num() * 2 : 8,
        'task_ipc_mode' => 1,
        'task_max_request' => 5000,
        'task_tmpdir' => @is_writable('/dev/shm/') ? '/dev/shm' : '/tmp',
        'message_queue_key' => ftok(base_path('public/index.php'), 1),
        'max_request' => 3000,
        'open_tcp_nodelay' => true,
        'pid_file' => storage_path('laravels.pid'),
        'log_file' => storage_path(sprintf('logs/swoole-%s.log', date('Y-m'))),
        'log_level' => 4,
        'document_root' => base_path('public'),
        'buffer_output_size' => 16 * 1024 * 1024,
        'socket_buffer_size' => 128 * 1024 * 1024,
        'package_max_length' => 4 * 1024 * 1024,
        'reload_async' => true,
        'max_wait_time' => 60,
        'enable_reuse_port' => true,

        // 表示每60秒遍历一次，一个连接如果600秒内未向服务器发送任何数据，此连接将被强制关闭
        'heartbeat_idle_time' => 12000,
        'heartbeat_check_interval' => 60,

        /**
         * More settings of Swoole
         * @see https://wiki.swoole.com/wiki/page/274.html  Chinese
         * @see https://www.swoole.co.uk/docs/modules/swoole-server/configuration  English
         */
    ],
];
