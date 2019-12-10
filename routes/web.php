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
Route::get('weixin','WechatController@index');
Route::post('weixin','WechatController@receiv');



//测试
Route::get('/test/hello','test\\TestController@hello');
Route::get('/user/adduser','user\\LoginController@adduser');
Route::get('/user/redis1','user\\LoginController@redis1');
Route::get('/user/index','user\\LoginController@index');
Route::get('/user/xml','user\\LoginController@xml');
