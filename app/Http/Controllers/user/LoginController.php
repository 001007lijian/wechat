<?php

namespace App\Http\Controllers\user;

use  App\Model\UserModel;
use App\Http\Controllers\Controller;
// use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Redis;


class LoginController extends Controller
{
    public function adduser()
    {
    	$pass="123456";
    	$email='2351584897@qq.com';
    	$user_name=Str::random();
    	//使用密码函数
    	$password=password_hash($pass,PASSWORD_DEFAULT);
    	$data=[
    		'user_name'=>$user_name,
    		'password'=>$password,
    		'email'=>$email,
    	];

    	echo '<pre>'; print_r($data); echo '</pre>';
    	$uid=UserModel::insertGetId($data);
    	var_dump($uid);
    }

    public function redis1()
    {
    	$key='1007';
    	$val='李剑';
    	redis::set($key,$val);
    }

    public function index()
    {
        echo '线下测试';
    }

    public function xml()
    {
        $xml_str="<xml>
              <ToUserName><![CDATA[toUser]]></ToUserName>
              <FromUserName><![CDATA[fromUser]]></FromUserName>
              <CreateTime>12345678</CreateTime>
              <MsgType><![CDATA[text]]></MsgType>
              <Content><![CDATA[你好]]></Content>
            </xml>";

        $xml_arr=simplexml_load_string($xml_str);
        echo '<pre>';  print_r($xml_arr); echo '</pre>';echo "<hr/>";
        echo "ToUserName:" . $xml_arr->ToUserName;
    }
}
