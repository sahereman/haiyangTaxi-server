<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    protected $fillable = [
        'cart_number', 'order_count', 'last_active_at'
    ];

    protected $casts = [
    ];

    protected $dates = [
    ];


    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function equipments()
    {
        return $this->hasMany(DriverEquipment::class);
    }
}
