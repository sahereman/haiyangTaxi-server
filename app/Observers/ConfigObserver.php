<?php

namespace App\Observers;


use App\Models\Config;
use Illuminate\Support\Facades\Cache;

class ConfigObserver
{
    /*Eloquent 的模型触发了几个事件，可以在模型的生命周期的以下几点进行监控：
    retrieved、creating、created、updating、updated、saving、saved、deleting、deleted、restoring、restored
    事件能在每次在数据库中保存或更新特定模型类时轻松地执行代码。*/


    public function created(Config $config)
    {
        Cache::forget($config::$cache_key);
    }

    public function saved(Config $config)
    {
        Cache::forget($config::$cache_key);
    }

    public function deleted(Config $config)
    {
        Cache::forget($config::$cache_key);
    }

}