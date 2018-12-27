<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CityHotAddress extends Model
{
    public static $cityMap = [
        '海阳市' => '海阳市',
        '青岛市' => '青岛市'
    ];

    public $timestamps = false;

    protected $fillable = [
        'city', 'address_component', 'address', 'location', 'sort'
    ];

    protected $casts = [
        'location' => 'json',
    ];

    protected $dates = [
    ];
}
