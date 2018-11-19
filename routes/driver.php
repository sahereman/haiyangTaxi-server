<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

$api = app('Dingo\Api\Routing\Router');

$api->version('v1', [
    'prefix' => 'api/driver',
    'namespace' => 'App\Http\Controllers\Driver',
    'middleware' => ['serializer:array', 'bindings']
], function ($api) {

    /*常规接口调用频率 1分钟 60次*/
    $api->group([
        'middleware' => 'api.throttle',
        'limit' => config('api.rate_limits.access.limit'),
        'expires' => config('api.rate_limits.access.expires'),
    ], function ($api) {
        /*游客可以访问的接口*/
        $api->get('test', 'Controller@test')->name('driver.test');/*测试*/


        /*需要 token 验证的接口*/
        $api->group(['middleware' => 'api.auth'], function ($api) {

            $api->get('test2', 'Controller@test2')->name('driver.test2');/*测试*/

            
        });
    });


});
