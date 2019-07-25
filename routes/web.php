<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Horizon::auth(function ($request) {
    return Auth::guard('admin')->check();
});


Route::get('/', function () {
    return view('welcome');
});



Route::get('demo', function () {

    if(\Browser::isMobile())
    {
        return "<center><h1>请使用电脑访问</h1></center>";
    }

    return view('demo');
});


Route::get('demo/{img}', 'Controller@img')->name('img');

