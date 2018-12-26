<?php

namespace App\Http\Controllers\Client;

use App\Handlers\TencentMapHandler;
use App\Models\OrderSet;
use App\Sockets\WebSocket;
use Dingo\Api\Routing\Helpers;
use App\Http\Controllers\Controller as BaseController;
use Ramsey\Uuid\Uuid;

class Controller extends BaseController
{
    use Helpers;

    public function test()
    {
        //        $swoole = app('swoole');
        //        dd($swoole->stats());

        //        $map = new TencentMapHandler();
        //
        //        return $map->reverseGeocoder(36.092484, 120.380966);

//        return Uuid::uuid4()->getHex();
        return '444';
    }
}
