<?php

use Faker\Generator as Faker;

$factory->define(App\Models\DriverEquipment::class, function (Faker $faker) {
    // 随机取一个周以内的时间
    $updated_at = $faker->dateTimeBetween($startDate = '-6 days', $endDate = 'now');
    // 传参为生成最大时间不超过，创建时间永远比更改时间要早
    $created_at = $faker->dateTimeBetween($startDate = '-6 days', $endDate = 'now');

    return [
        'imei' => $faker->randomNumber(6, true),
        'name' => $faker->name,
        'phone' => $faker->phoneNumber,
        'created_at' => $created_at,
        'updated_at' => $updated_at,
    ];
});
