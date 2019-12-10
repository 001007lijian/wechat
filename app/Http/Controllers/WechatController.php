<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WechatController extends Controller
{
    protected $access_token;

    public function __construct()
    {
        //获取access_token
        $this->access_token=$this->getAccessToken();
    }


    //获取access_token
    public function getAccessToken()
    {
        $url='https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.env('APPID').'&secret='.env('APPSECRET');
        $data_json=file_get_contents($url);
        $arr=json_decode($data_json,true);
        return $arr['access_token'];
    }

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
        $log="wechat.log";
        $xml_str=file_get_contents("php://input");
        //将接收的数据记录到日志文件
        $data= date('Y-m-d H:i:s') . $xml_str;
        file_put_contents($log,$data,FILE_APPEND);
        $xml_obj=simplexml_load_string($xml_str);//处理xml数据

        $event=$xml_obj->Event;//获取事件类型
        if ($event=='subscribe') {
            $openid=$xml_obj->FromUserName;//获取用的openid
            /*获取用户信息*/
            $url="https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$this->access_token."&openid=".$openid."&lang=zh_CN";
            $user_info=file_get_contents($url);
            file_put_contents('wechat.log,$user_info,FILE_APPEND');
        }
    }
}
