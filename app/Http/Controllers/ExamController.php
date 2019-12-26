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
        //将接收的数据记录到日志文件
        $data = date('Y-m-d H:i:s') . '>>>>>> \n' . $xml_str . "\n\n";
        file_put_contents($log, $data, FILE_APPEND);


        $xml_obj = simplexml_load_string($xml_str);//处理xml数据

        $event = $xml_obj->Event;//获取事件类型
        $openid = $xml_obj->FromUserName;//获取用户的openid
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
                /*获取用户信息*/
                $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=" . $this->access_token . "&openid=" . $openid . "&lang=zh_CN";
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
                $msg = $user_data['nickname'] . "谢谢你的关注";
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
        }

        //判断消息类型
        $msg_type = $xml_obj->MsgType;

        $touser = $xml_obj->FromUserName;//接收消息的用户的openid
        $fromuser = $xml_obj->ToUserName;//开发者公众号的ID
        $media_id = $xml_obj->MediaId;
        if ($msg_type == "text") {
            $content = "现在是格林威治时间" . date('Y-m-d H:i:s') . "，您发送的内容是：" . $xml_obj->Content;
            $response_text =
                '<xml>
                  <ToUserName><![CDATA[' . $touser . ']]></ToUserName>
                  <FromUserName><![CDATA[' . $fromuser . ']]></FromUserName>
                  <CreateTime>' . time() . '</CreateTime>
                  <MsgType><![CDATA[text]]></MsgType>
                  <Content><![CDATA[' . $content . ']]></Content>
            </xml>';
            echo $response_text;    //回复用户消息
            //文本消息入库

        }
    }
}
