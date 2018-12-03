<?php

namespace App\Http\Controllers\Client;

use App\Models\Order;
use App\Transformers\Client\OrderTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrdersController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::guard('client')->user();

        $builder = $user->orders()->orderBy('created_at', 'desc');

        switch ($request->input('status'))
        {
            case 'closed' :
                $builder->where('status', Order::ORDER_STATUS_CLOSED);
                break;
            case 'tripping' :
                $builder->where('status', Order::ORDER_STATUS_TRIPPING);
                break;
            case 'completed' :
                $builder->where('status', Order::ORDER_STATUS_COMPLETED);
                break;
            case 'noTripping' :
                $builder->where('status', '!=', Order::ORDER_STATUS_TRIPPING);
                break;
        }

        $orders = $builder->paginate(3)->appends($request->except('page'));

        return $this->response->paginator($orders, new OrderTransformer());

    }
}
