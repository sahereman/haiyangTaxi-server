<?php

namespace App\Http\Controllers\Driver;

use App\Models\Order;
use App\Transformers\Driver\OrderTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrdersController extends Controller
{
    public function index(Request $request)
    {
        $driver = Auth::guard('driver')->user();

        $builder = $driver->orders()->orderBy('created_at', 'desc');

        switch ($request->input('date'))
        {
            case 'today' :
                $builder->whereBetween('created_at', [today(), today()->addDay()]);
                break;
            case 'yesterday' :
                $builder->whereBetween('created_at', [today()->subDay(), today()]);
                break;
            case 'month' :
                $builder->whereBetween('created_at', [today()->subMonth(), today()]);
                break;
        }

        $orders = $builder->paginate(3)->appends($request->except('page'));

        return $this->response->paginator($orders, new OrderTransformer());

    }
}
