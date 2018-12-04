<?php

use Faker\Generator as Faker;

$factory->define(App\Models\CityHotAddress::class, function (Faker $faker) {
    return [
        'address_component' => $faker->streetAddress,
        'address' => $faker->address,
        'location' => ['lat' => $faker->randomFloat(6, 30, 120), 'lng' => $faker->randomFloat(6, 30, 120)],
    ];
});
