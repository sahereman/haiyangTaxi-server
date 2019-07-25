<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class img extends Model
{
    public $timestamps = false;


    public function getImgUrlAttribute()
    {

        return Storage::disk('public')->url($this->attributes['img']);
    }

    public function cat()
    {
        return $this->belongsTo(cat::class);

    }
}
