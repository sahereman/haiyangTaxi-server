<?php

namespace App\Transformers\Driver;

use App\Models\Driver;
use League\Fractal\TransformerAbstract;

class DriverTransformer extends TransformerAbstract
{
    public function transform(Driver $driver)
    {
        return [
            'id' => $driver->id,
            'cart_number' => $driver->cart_number,
            'name' => $driver->name,
            'phone' => $driver->phone,
            'order_count' => $driver->order_count,
            'last_active_at' => $driver->last_active_at->toDateTimeString(),
            'created_at' => $driver->created_at->toDateTimeString(),
            'updated_at' => $driver->updated_at->toDateTimeString(),
        ];
    }
}