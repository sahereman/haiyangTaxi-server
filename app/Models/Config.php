<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Config extends Model
{
    protected $fillable = [
        'parent_id', 'name', 'name_code',
        'type', 'select_range', 'value',
        'help', 'sort'
    ];

    protected $casts = [
        'select_range' => 'json',
    ];

    public $timestamps = false;

    public static $cache_key;

    protected static $cache_expire_in_minutes = 1440;//24小时

    protected static function boot()
    {
        parent::boot();

        self::$cache_key = config('app.name') . '_configs';
    }

    public static function configs()
    {
        // 尝试从缓存中取出 cache_key 对应的数据。如果能取到，便直接返回数据。
        // 否则运行匿名函数中的代码来取出 gifts 表中所有的数据，返回的同时做了缓存。
        return Cache::remember(self::$cache_key, self::$cache_expire_in_minutes, function () {
            return Config::all();
        });
    }

    public static function config($code = null)
    {
        if (empty($code) || !self::configs()->where('code', $code)->first())
        {
            return '';
        }
        return self::configs()->where('code', $code)->first()->value;
    }
}
