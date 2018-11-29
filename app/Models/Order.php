<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Order extends Model
{

    // 订单状态
    const ORDER_STATUS_CLOSED = 'closed';
    const ORDER_STATUS_TRIPPING = 'tripping';
    const ORDER_STATUS_COMPLETED = 'completed';

    public static $orderStatusMap = [
        self::ORDER_STATUS_CLOSED => '已取消',
        self::ORDER_STATUS_TRIPPING => '进行中',
        self::ORDER_STATUS_COMPLETED => '已完成',
    ];

    // 订单取消者
    const ORDER_CLOSE_FROM_USER = 'user';
    const ORDER_CLOSE_FROM_DRIVER = 'driver';
    const ORDER_CLOSE_FROM_SYSTEM = 'system';

    public static $orderCloseFromMap = [
        self::ORDER_CLOSE_FROM_USER => '用户主动取消',
        self::ORDER_CLOSE_FROM_DRIVER => '司机主动取消',
        self::ORDER_CLOSE_FROM_SYSTEM => '系统取消',
    ];

    protected $fillable = [
        'order_sn', 'status', 'from_address', 'from_location', 'to_address', 'to_location', 'close_from',
        'close_reason', 'closed_at', 'completed_at'

    ];

    protected $casts = [
        'from_location' => 'json',
        'to_location' => 'json',
    ];

    protected $dates = [
        'closed_at',
        'completed_at',
    ];


    protected static function boot()
    {
        parent::boot();
        // 监听模型创建事件，在写入数据库之前触发
        static::creating(function ($model) {
            // 如果模型的 order_sn 字段为空
            if (!$model->order_sn)
            {
                // 调用 generateOrderSn 生成订单流水号
                $model->order_sn = static::generateOrderSn();
                // 如果生成失败，则终止创建订单
                if (!$model->order_sn)
                {
                    return false;
                }
            }
        });
    }

    //  生成订单流水号
    public static function generateOrderSn()
    {
        // 订单流水号前缀
        $prefix = date('YmdHis');
        for ($i = 0; $i < 10; $i++)
        {
            // 随机生成 6 位的数字
            $orderSn = $prefix . str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            // 判断是否已经存在
            if (!static::query()->where('order_sn', $orderSn)->exists())
            {
                return $orderSn;
            }
        }
        Log::error('generating order sn failed');
        return false;
    }
}
