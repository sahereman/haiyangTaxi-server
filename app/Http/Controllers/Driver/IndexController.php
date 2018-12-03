<?php

namespace App\Http\Controllers\Driver;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IndexController extends Controller
{
    public function stats()
    {
        $driver = Auth::guard('driver')->user();

        return $this->response->array([
            'order_count' => $driver->order_count,
            'today_order_count' => $driver->orders()->whereBetween('created_at', [today(), today()->addDay()])->count(),
            'yesterday_order_count' => $driver->orders()->whereBetween('created_at', [today()->subDay(), today()])->count(),
        ]);
    }
}
