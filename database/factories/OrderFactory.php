<?php

use Faker\Generator as Faker;
use App\Models\Order;

$factory->define(Order::class, function (Faker $faker) {
    // 现在时间
    $now = \Carbon\Carbon::now()->toDateTimeString();
    // 随机取一个月以内的时间
    $updated_at = $faker->dateTimeThisMonth($now);
    // 传参为生成最大时间不超过，创建时间永远比更改时间要早
    $created_at = $faker->dateTimeThisMonth($updated_at);
    return [
        'order_sn' => Order::generateOrderSn(),
        'from_address' => $faker->address,
        'from_location' => ['lat' => $faker->randomFloat(6, 30, 120), 'lng' => $faker->randomFloat(6, 30, 120)],
        'to_address' => $faker->address,
        'to_location' => ['lat' => $faker->randomFloat(6, 30, 120), 'lng' => $faker->randomFloat(6, 30, 120)],
        'created_at' => $created_at,
        'updated_at' => $updated_at,
    ];
});
