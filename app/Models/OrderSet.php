<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

class OrderSet extends Model
{

    protected $primaryKey = 'key';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id', 'from_address', 'from_location',
        'to_address', 'to_location', 'created_at',
    ];

    protected $casts = [
        'from_location' => 'json',
        'to_location' => 'json',
    ];

    protected $dates = [
        'created_at',
    ];

    public $timestamps = false;

    protected static function boot()
    {
        parent::boot();
        // 监听模型创建事件，在写入数据库之前触发
        static::creating(function ($model) {
            // 自动生成 主键 key
            $model->key = Uuid::uuid4()->getHex();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
