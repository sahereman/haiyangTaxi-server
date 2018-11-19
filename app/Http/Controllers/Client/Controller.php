<?php

namespace App\Http\Controllers\Client;

use Dingo\Api\Routing\Helpers;
use App\Http\Controllers\Controller as BaseController;

class Controller extends BaseController
{
    use Helpers;

    public function test()
    {
//        $swoole = app('swoole');
//        dd($swoole->stats());

        return '111';
    }
}
