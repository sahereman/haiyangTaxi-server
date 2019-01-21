<?php

namespace App\Providers;

use App\Models\Config;
use App\Models\User;
use App\Observers\ConfigObserver;
use App\Observers\UserObserver;
use Carbon\Carbon;
use Dingo\Api\Facade\API;
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
        User::observe(UserObserver::class);

        // Carbon 中文化配置
        Carbon::setLocale('zh');
    }

    /**
     * Register any application services.
     * @return void
     */
    public function register()
    {
        //路由模型绑定没有找到模型后的异常处理
        API::error(function (\Illuminate\Database\Eloquent\ModelNotFoundException $exception) {
            abort(404);
        });

        //用户权限异常返回正确的状态码
        API::error(function (\Illuminate\Auth\Access\AuthorizationException $exception) {
            abort(403, $exception->getMessage());
        });

        //Token已过期,不能再刷新,返回正确的状态码
        API::error(function (\Tymon\JWTAuth\Exceptions\TokenExpiredException $exception) {
            abort(401, $exception->getMessage()); //Token has expired and can no longer be refreshed
        });

        //Token无效异常返回正确的状态码
        API::error(function (\Tymon\JWTAuth\Exceptions\TokenInvalidException $exception) {
            abort(401, $exception->getMessage());
        });

    }
}
