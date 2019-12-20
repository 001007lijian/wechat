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

//Route::get('/', function () {
//    return view('welcome');
//});
Route::get('/','IndexController@index');    //网站首页


Route::get('/phpinfo', function () {
    phpinfo();
});



//刷新access_token
Route::get('/flush/access_token','WechatController@flushAccessToken');



//微信
Route::get('weixin','WechatController@index');
Route::post('weixin','WechatController@receiv');


//素材管理
Route::get('wechat/getMedia','WechatController@getMedia');


//自定义菜单
Route::get('wechat/createMenu','WechatController@createMenu');


//测试access_token存入Redis
Route::get('wechat/test','WechatController@test');




//微信投票
Route::get('vote/index','VoteController@index');
Route::get('vote/delkey','VoteController@delKey');






//测试
Route::get('/test/hello','test\\TestController@hello');
Route::get('/user/adduser','user\\LoginController@adduser');
Route::get('/user/redis1','user\\LoginController@redis1');
Route::get('/user/index','user\\LoginController@index');
Route::get('/user/xml','user\\LoginController@xml');
