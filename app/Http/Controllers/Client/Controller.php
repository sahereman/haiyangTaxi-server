<?php

namespace App\Http\Controllers\Client;

use App\Handlers\TencentMapHandler;
use Dingo\Api\Routing\Helpers;
use App\Http\Controllers\Controller as BaseController;

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


        return '111';
    }
}
