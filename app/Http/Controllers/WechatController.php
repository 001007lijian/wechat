<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WechatController extends Controller
{   
    /*处理微信接入*/
    public function index()
    {
        $token = 'ljnbyzyq666';       //开发提前设置好的 token
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $echostr = $_GET["echostr"];


        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );


        if( $tmpStr == $signature ){        //验证通过
            echo $echostr;
        }else{
            die("not ok");
        }
    }


    /*接收微信推送事件*/
    public function receiv()
    {
        $logs="wechat.log";
        //将接收的数据记录到日志文件
        $data=json_decode($_POST);
        file_put_contents($logs,$data,FILE_APPEND);
    }
}
