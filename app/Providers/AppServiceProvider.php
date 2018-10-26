<?php

namespace App\Providers;

use App\Models\Config;
use App\Observers\ConfigObserver;
use Carbon\Carbon;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     * @return void
     */
    public function boot()
    {
        Config::observe(ConfigObserver::class);


        // Carbon 中文化配置
        Carbon::setLocale('zh');
    }

    /**
     * Register any application services.
     * @return void
     */
    public function register()
    {
        //
    }
}
