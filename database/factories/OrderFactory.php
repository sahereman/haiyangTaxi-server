<?php

use Faker\Generator as Faker;
use App\Models\Order;

$factory->define(Order::class, function (Faker $faker) {
    // 随机取一个周以内的时间
    $updated_at = $faker->dateTimeBetween($startDate = '-6 days', $endDate = 'now');
    // 传参为生成最大时间不超过，创建时间永远比更改时间要早
    $created_at = $faker->dateTimeBetween($startDate = '-6 days', $endDate = 'now');
    return [
        'order_sn' => Order::generateOrderSn(),
        'from_address' => $faker->address,
        'from_location' => ['lat' => (string)$faker->randomFloat(6, 30, 120), 'lng' => (string)$faker->randomFloat(6, 30, 120)],
        'to_address' => $faker->address,
        'to_location' => ['lat' => (string)$faker->randomFloat(6, 30, 120), 'lng' => (string)$faker->randomFloat(6, 30, 120)],
        'created_at' => $created_at,
        'updated_at' => $updated_at,
    ];
});
