<?php

namespace App\Http\Controllers\Driver;

use App\Transformers\Driver\DriverTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DriversController extends Controller
{
    public function me(Request $request)
    {
        $driver = Auth::guard('driver')->user();

        return $this->response->item($driver, new DriverTransformer());
    }
}
