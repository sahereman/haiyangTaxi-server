<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Driver extends Authenticatable implements JWTSubject
{
    use Notifiable;

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    protected $fillable = [
        'cart_number', 'order_count', 'last_active_at'
    ];

    protected $casts = [
    ];

    protected $dates = [
        'last_active_at'
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
