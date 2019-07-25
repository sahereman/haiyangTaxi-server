<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Encore\Admin\Traits\AdminBuilder;
use Encore\Admin\Traits\ModelTree;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class cat extends Model
{

    use ModelTree, AdminBuilder;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        /*初始化Tree属性*/
        $this->setParentColumn('parent_id');
        $this->setTitleColumn('name');
        $this->setOrderColumn('sort');
    }

    public $timestamps = false;


    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }


    /* Eloquent Relationships */
    public function imgs()
    {
        return $this->hasMany(img::class, 'cat_id');
    }

}
