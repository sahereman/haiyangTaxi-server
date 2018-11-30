<?php

namespace App\Transformers\Client;

use App\Models\Order;
use League\Fractal\TransformerAbstract;

class OrderTransformer extends TransformerAbstract
{
    protected $defaultIncludes = ['user', 'driver'];

    public function transform(Order $order)
    {
        return [
            'id' => $order->id,
            'order_sn' => $order->order_sn,
            'status' => $order->status,
            'status_text' => Order::$orderStatusMap[$order->status],
            'from_address' => $order->from_address,
            'from_location' => $order->from_location,
            'to_address' => $order->to_address,
            'to_location' => $order->to_location,
            'close_from' => $order->close_from,
            'close_reason' => $order->close_reason,
            'closed_at' => $order->closed_at ? $order->closed_at->toDateTimeString() : null,
            'completed_at' => $order->completed_at ? $order->completed_at->toDateTimeString() : null,
            'created_at' => $order->created_at->toDateTimeString(),
        ];

    }

    public function includeUser(Order $order)
    {
        return $this->item($order->user, new UserTransformer());
    }

    public function includeDriver(Order $order)
    {
        return $this->item($order->driver, new DriverTransformer());
    }
}