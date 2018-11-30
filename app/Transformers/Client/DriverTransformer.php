<?php

namespace App\Transformers\Client;

use App\Models\Driver;
use League\Fractal\TransformerAbstract;

class DriverTransformer extends TransformerAbstract
{
    public function transform(Driver $driver)
    {
        return [
            'id' => $driver->id,
            'order_count' => $driver->order_count,
            'cart_number' => $driver->cart_number,
            'last_active_at' => $driver->last_active_at->toDateTimeString(),
        ];
    }
}