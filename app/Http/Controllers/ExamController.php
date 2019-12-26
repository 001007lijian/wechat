<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\WechatModel;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use GuzzleHttp\Client;
class ExamController extends Controller
{
    protected $access_token;

    public function __construct()
    {
        //获取access_token
        $this->access_token = $this->getAccessToken();
    }

    public function getAccessToken()
    {
        $key = "exam_token";
        $access_token = Redis::get($key);
        if ($access_token) {
            return $access_token;
        }
        $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . env('APPID') . '&secret=' . env('APPSECRET');
        $data_json = file_get_contents($url);
        $arr = json_decode($data_json, true);
        Redis::set($key, $arr['access_token']);
        Redis::expire($key, 3600);
        return $arr['access_token'];
    }

    public function index()
    {
        $token = 'exam20191226';       //开发提前设置好的 token
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

    public function receiv()
    {
        $log = "exam.log";
        $xml_str = file_get_contents("php://input");
        $data = date('Y-m-d H:i:s') . '>>>>>> \n' . $xml_str . "\n\n";
        file_put_contents($log, $data, FILE_APPEND);

        $xml_obj = simplexml_load_string($xml_str);

        $event = $xml_obj->Event;
        $openid = $xml_obj->FromUserName;
        if ($event == 'subscribe') {
            $user = WechatModel::where(['openid' => $openid])->first();
            if ($user) {
                $msg = "欢迎回来";
                $response_text =
                    '<xml>
                          <ToUserName><![CDATA[' . $openid . ']]></ToUserName>
                          <FromUserName><![CDATA[' . $xml_obj->ToUserName . ']]></FromUserName>
                          <CreateTime>' . time() . '</CreateTime>
                          <MsgType><![CDATA[text]]></MsgType>
                          <Content><![CDATA[' . $msg . ']]></Content>
                    </xml>';
                //欢迎回家
                echo $response_text;
            } else {
                $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$this->access_token."&openid=".$openid."&lang=zh_CN";
                $user_info = file_get_contents($url);
                $data = json_decode($user_info, true);
                //
                $user_data = [
                    'openid' => $openid,
                    'subscribe_time' => $data['subscribe_time'],
                    'nickname' => $data['nickname'],
                    'sex' => $data['sex'],
                    'headimgurl' => $data['headimgurl'],
                ];
                //信息入库
                $uid = WechatModel::insertGetId($user_data);
                $msg = "欢迎".$user_data['nickname'] ."同学关注";
                $response_text =
                    '<xml>
                          <ToUserName><![CDATA[' . $openid . ']]></ToUserName>
                          <FromUserName><![CDATA[' . $xml_obj->ToUserName . ']]></FromUserName>
                          <CreateTime>' . time() . '</CreateTime>
                          <MsgType><![CDATA[text]]></MsgType>
                          <Content><![CDATA[' . $msg . ']]></Content>
                    </xml>';
                echo $response_text;
            }
        }//elseif($event == 'CLICK'){
//            //如果是查询积分
//            if ($xml_obj->EventKey=='jf'){
//                $data=WechatModel::where(['openid'=>$openid])->first();
//                $msg="积分总数为：".$data;
//                $response_xml =
//                    '<xml>
//                      <ToUserName><![CDATA['.$openid.']]></ToUserName>
//                      <FromUserName><![CDATA['.$xml_obj->ToUserName.']]></FromUserName>
//                      <CreateTime>'.time().'</CreateTime>
//                      <MsgType><![CDATA[text]]></MsgType>
//                      <Content><![CDATA['. date('Y-m-d H:i:s') .  $msg .']]></Content>
//                    </xml>';
//                echo $response_xml;
//            }elseif($xml_obj->EventKey=='qd'){
//                //如果是签到
//
//            }
//        }
    }

//    public function createMenu()
//    {
//        $menu = [
//            'button' => [
//                [
//                    'type' => 'click',
//                    'name' => '积分查询',
//                    'key' => 'jf'
//                ],
//                [
//                    'type' => 'click',
//                    'name' => '签到',
//                    'key' => 'qd'
//                ],
//            ]
//        ];
//        echo '<pre>'; print_r($menu); echo '</pre>';
//    }
}
