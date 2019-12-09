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

Route::get('/', function () {
    return view('welcome');
});

//微信
Route::any('/wechat/index','WechatController@index');



//测试
Route::any('/test/hello','test\\TestController@hello');
Route::any('/user/adduser','user\\LoginController@adduser');
Route::any('/user/redis1','user\\LoginController@redis1');
Route::any('/user/index','user\\LoginController@index');
