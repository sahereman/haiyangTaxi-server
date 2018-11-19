<?php

namespace App\Http\Controllers\Driver;

use Dingo\Api\Routing\Helpers;
use App\Http\Controllers\Controller as BaseController;

class Controller extends BaseController
{
    use Helpers;

    public function test()
    {

        return 'driver test';
    }

    public function test2()
    {

        return 'driver test2';
    }
}
