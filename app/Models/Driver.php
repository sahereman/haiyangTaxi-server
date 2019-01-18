<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Cache;
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

    public static $redis_id;
    public static $redis_fd;
    public static $redis_active;

    protected $fillable = [
        'cart_number', 'name', 'phone', 'remark', 'order_count', 'last_active_at'
    ];

    protected $casts = [
    ];

    protected $dates = [
        'last_active_at'
    ];

    protected static function boot()
    {
        parent::boot();
        self::$redis_id = Cache::getPrefix() . 'driver_id_keys';
        self::$redis_fd = Cache::getPrefix() . 'driver_fd_keys';
        self::$redis_active = Cache::getPrefix() . 'driver_active_drivers';
    }


    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function equipments()
    {
        return $this->hasMany(DriverEquipment::class);
    }
}
