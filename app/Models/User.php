<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
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

    protected $fillable = [
        'avatar', 'phone', 'last_active_at'
    ];

    protected $hidden = [
    ];

    protected $casts = [
    ];

    protected $dates = [
        'last_active_at'
    ];

    protected $appends = ['avatar_url'];


    protected static function boot()
    {
        parent::boot();
        self::$redis_id = Cache::getPrefix() . 'client_id_keys';
        self::$redis_fd = Cache::getPrefix() . 'client_fd_keys';
    }


    public function getAvatarUrlAttribute()
    {
        // 如果 image 字段本身就已经是完整的 url 就直接返回
        if (Str::startsWith($this->attributes['avatar'], ['http://', 'https://']))
        {
            return $this->attributes['avatar'];
        }
        return \Storage::disk('public')->url($this->attributes['avatar']);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
