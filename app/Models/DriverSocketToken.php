<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverSocketToken extends Model
{

    protected $fillable = [
        'token', 'driver_id', 'expired_at'
    ];

    protected $casts = [
    ];

    protected $dates = [
        'expired_at',
    ];

    public $timestamps = false;


    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}
