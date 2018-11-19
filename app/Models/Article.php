<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{

    public static $slugMap = [
        'about' => '关于我们',
        'event' => '礼品活动'
    ];


    protected $fillable = [
    ];

    protected $casts = [
    ];

    protected $dates = [
    ];
}
