<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSocketToken extends Model
{

    protected $fillable = [
        'token', 'user_id', 'expired_at'

    ];

    protected $casts = [
    ];

    protected $dates = [
        'expired_at',
    ];

    public $timestamps = false;


    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
