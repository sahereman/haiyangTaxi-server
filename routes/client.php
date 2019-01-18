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
    'prefix' => 'api/client',
    'namespace' => 'App\Http\Controllers\Client',
    'middleware' => ['serializer:array', 'bindings']
], function ($api) {

    /*短信接口调用频率 1分钟 2次*/
    $api->group([
        'middleware' => 'api.throttle',
        'limit' => 20,
        'expires' => 1,
    ], function ($api) {
        // 短信
        $api->post('sms/verification', 'SmsController@verification')->name('client.sms.verification');/*获取短信验证码*/

    });

    /*常规接口调用频率 1分钟 60次*/
    $api->group([
        'middleware' => 'api.throttle',
        'limit' => config('api.rate_limits.access.limit'),
        'expires' => config('api.rate_limits.access.expires'),
    ], function ($api) {
        /*游客可以访问的接口*/
        $api->get('test', 'Controller@test')->name('client.test');/*测试*/

        // 用户注册
        $api->post('users', 'UsersController@store')->name('client.users.store');/*注册*/

        // 登录
        $api->post('authorizations', 'AuthorizationsController@store')->name('client.authorizations.store');/*登录授权token*/

        // 文章展示
        $api->get('articles/{slug}', 'ArticlesController@show')->name('client.articles.show');/*详情*/

        // 城市热门地点
        $api->get('city_hot_addresses', 'CityHotAddressesController@index')->name('client.city_hot_addresses.index');/*列表*/

        /*需要 token 验证的接口*/
        $api->group(['middleware' => 'auth:client'], function ($api) {

            //登录
            $api->put('authorizations', 'AuthorizationsController@update')->name('client.authorizations.update');/*刷新授权token*/
            $api->delete('authorizations', 'AuthorizationsController@destroy')->name('client.authorizations.destroy');/*删除授权token*/

            // 用户
            $api->get('users/me', 'UsersController@me')->name('client.users.me');/*获取用户信息*/
            $api->patch('users', 'UsersController@update')->name('client.users.update');/*编辑用户信息*/
            $api->get('users/to_history', 'UsersController@toHistory')->name('client.users.to_history');/*获取用户目的地历史记录*/

            // 订单
            $api->get('orders', 'OrdersController@index')->name('client.orders.index');/*获取订单列表*/


        });
    });


});
