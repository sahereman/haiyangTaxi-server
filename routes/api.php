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
    'namespace' => 'App\Http\Controllers\Api',
    'middleware' => ['serializer:array', 'bindings']
], function ($api) {

    /*短信接口调用频率 1分钟 2次*/
    $api->group([
        'middleware' => 'api.throttle',
        'limit' => 2,
        'expires' => 1,
    ], function ($api) {
        // 短信
        $api->post('sms/verification', 'SmsController@verification')->name('api.sms.verification');/*获取短信验证码*/

    });

    /*常规接口调用频率 1分钟 60次*/
    $api->group([
        'middleware' => 'api.throttle',
        'limit' => config('api.rate_limits.access.limit'),
        'expires' => config('api.rate_limits.access.expires'),
    ], function ($api) {
        /*游客可以访问的接口*/
        $api->get('test', 'Controller@test')->name('api.test');/*测试*/

        // 用户注册
        $api->post('users', 'UsersController@store')->name('api.users.store');/*注册*/

        // 登录
        $api->post('authorizations', 'AuthorizationsController@store')->name('api.authorizations.store');/*登录授权token*/
        $api->put('authorizations', 'AuthorizationsController@update')->name('api.authorizations.update');/*刷新授权token*/
        $api->delete('authorizations', 'AuthorizationsController@destroy')->name('api.authorizations.destroy');/*删除授权token*/

        // 找回密码
        $api->post('password/reset/sms', 'PasswordResetController@resetBySms')->name('api.password.reset.sms');/*找回密码(短信)*/
        $api->patch('password/reset', 'PasswordResetController@reset')->name('api.password.reset');/*重置密码*/


        /*需要 token 验证的接口*/
        $api->group(['middleware' => 'api.auth'], function ($api) {

            // 用户
            $api->get('users/me', 'UsersController@me')->name('api.users.me');/*获取当前用户信息*/
            $api->patch('users', 'UsersController@update')->name('api.users.update');/*编辑登录用户信息*/


        });
    });


});
