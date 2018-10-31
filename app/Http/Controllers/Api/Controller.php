<?php

namespace App\Http\Controllers\Api;

use Dingo\Api\Routing\Helpers;
use App\Http\Controllers\Controller as BaseController;

class Controller extends BaseController
{
    use Helpers;

    public function test()
    {
        return 'test';
    }
}
