<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Model\WechatModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use GuzzleHttp\Client;

class WechatController extends Controller
{
    protected $access_token;

    public function __construct()
    {
        //获取access_token
        $this->access_token=$this->getAccessToken();
    }

    public function test()
    {
        echo $this->access_token;
    }

    //获取access_token
    public function getAccessToken()
    {
        $key="weixin_access_token";
        $access_token=Redis::get($key);
        if ($access_token){
            return $access_token;
        }
        $url='https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.env('APPID').'&secret='.env('APPSECRET');
        $data_json=file_get_contents($url);
        $arr=json_decode($data_json,true);
        Redis::set($key,$arr['access_token']);
        Redis::expire($key,3600);
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
        $data=date('Y-m-d H:i:s').'>>>>>>\n'. $xml_str."\n\n";
        file_put_contents($log,$data,FILE_APPEND);


        $xml_obj=simplexml_load_string($xml_str);//处理xml数据

        $event=$xml_obj->Event;//获取事件类型
        $openid=$xml_obj->FromUserName;//获取用户的openid
        if ($event=='subscribe') {
            $user=WechatModel::where(['openid'=>$openid])->first();
            if ($user) {
                $msg="欢迎回来";
                $response_text=
                    '<xml>
                          <ToUserName><![CDATA['.$openid.']]></ToUserName>
                          <FromUserName><![CDATA['.$xml_obj->ToUserName.']]></FromUserName>
                          <CreateTime>'.time().'</CreateTime>
                          <MsgType><![CDATA[text]]></MsgType>
                          <Content><![CDATA['.$msg.']]></Content>
                    </xml>';
                //欢迎回家
                echo $response_text;
            }else{
                /*获取用户信息*/
                $url="https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$this->access_token."&openid=".$openid."&lang=zh_CN";
                $user_info=file_get_contents($url);
                $data=json_decode($user_info,true);
                //
                $user_data=[
                    'openid'=> $openid,
                    'subscribe_time'=>$data['subscribe_time'],
                    'nickname'=>$data['nickname'],
                    'sex'=>$data['sex'],
                    'headimgurl'=>$data['headimgurl'],
                ];
                //信息入库
                $uid=WechatModel::insertGetId($user_data);
                $msg=$user_data['nickname']."谢谢你的关注";
                $response_text=
                    '<xml>
                          <ToUserName><![CDATA['.$openid.']]></ToUserName>
                          <FromUserName><![CDATA['.$xml_obj->ToUserName.']]></FromUserName>
                          <CreateTime>'.time().'</CreateTime>
                          <MsgType><![CDATA[text]]></MsgType>
                          <Content><![CDATA['.$msg.']]></Content>
                    </xml>';
                echo $response_text;
            }
        }elseif ($event=='CLICK'){   //菜单点击事件
            if ($xml_obj->EventKey=='weather'){
                $weather_api="https://free-api.heweather.net/s6/weather/now?location=beijing&key=d0f58d16f0794bdcabea30cf1daf1e0f";
                $weather_info=file_get_contents($weather_api);
                $weather_info_arr=json_decode($weather_info,true);

                $cond_txt=$weather_info_arr['HeWeather6'][0]['now']['cond_txt'];
                $tmp=$weather_info_arr['HeWeather6'][0]['now']['tmp'];
                $wind_dir=$weather_info_arr['HeWeather6'][0]['now']['wind_dir'];

                $msg=$cond_txt.'温度：'.$tmp.   '风向：'.$wind_dir;
                $response_weather=
                    '<xml>
                  <ToUserName><![CDATA['.$openid.']]></ToUserName>
                  <FromUserName><![CDATA['.$xml_obj->ToUserName.']]></FromUserName>
                  <CreateTime>'.time().'</CreateTime>
                  <MsgType><![CDATA[text]]></MsgType>
                  <Content><![CDATA['. date('Y-m-d H:i:s') .  $msg .']]></Content>
                </xml>';
                echo $response_weather;
            }
        }

        //判断消息类型
        $msg_type=$xml_obj->MsgType;

        $touser=$xml_obj->FromUserName;//接收消息的用户的openid
        $fromuser=$xml_obj->ToUserName;//开发者公众号的ID
        $time=time();
        $media_id=$xml_obj->MediaId;
        if ($msg_type=="text") {
            $content="现在是格林威治时间" . date('Y-m-d H:i:s') . "，您发送的内容是：" . $xml_obj->Content;
            $response_text=
            '<xml>
                  <ToUserName><![CDATA['.$touser.']]></ToUserName>
                  <FromUserName><![CDATA['.$fromuser.']]></FromUserName>
                  <CreateTime>'.$time.'</CreateTime>
                  <MsgType><![CDATA[text]]></MsgType>
                  <Content><![CDATA['.$content.']]></Content>
            </xml>';
            echo $response_text;    //回复用户消息
            //文本消息入库

        }elseif($msg_type=='image'){    //图片消息
            //下载图片
            $this->getMedia($media_id,$msg_type);
            //回复图片
            $response_img=
                '<xml>
                  <ToUserName><![CDATA['.$touser.']]></ToUserName>
                  <FromUserName><![CDATA['.$fromuser.']]></FromUserName>
                  <CreateTime>'.time().'</CreateTime>
                  <MsgType><![CDATA[image]]></MsgType>
                  <Image>
                    <MediaId><![CDATA['.$media_id.']]></MediaId>
                  </Image>
            </xml>';
            echo $response_img;
        }elseif ($msg_type=='voice'){   //语音消息
            //下载语音
            $this->getMedia($media_id,$msg_type);
            //回复语音
            $response_voice=
                '<xml>
                  <ToUserName><![CDATA['.$touser.']]></ToUserName>
                  <FromUserName><![CDATA['.$fromuser.']]></FromUserName>
                  <CreateTime>'.time().'</CreateTime>
                  <MsgType><![CDATA[voice]]></MsgType>
                  <Voice>
                    <MediaId><![CDATA['.$media_id.']]></MediaId>
                  </Voice>
            </xml>';
            echo $response_voice;
        }elseif($msg_type=='video'){
            //下载视频
            $this->getMedia($media_id,$msg_type);
            //回复
            $response_video=
                '<xml>
                  <ToUserName><![CDATA['.$touser.']]></ToUserName>
                  <FromUserName><![CDATA['.$fromuser.']]></FromUserName>
                  <CreateTime>'.time().'</CreateTime>
                  <MsgType><![CDATA[video]]></MsgType>
                  <Video>
                    <MediaId><![CDATA['.$media_id.']]></MediaId>
                    <Title><![CDATA[测试]]></Title>
                    <Description><![CDATA[不可描述]]></Description>
                  </Video>
            </xml>';
            echo $response_video;
        }
    }


    /**
     * 获取用户基本信息
     */
    public function getUserInfo($access_token,$openid)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN';
        //发送网络请求
        $json_str = file_get_contents($url);
        $log = 'wechat.log';
        file_put_contents($log,$json_str,FILE_APPEND);
    }


//    public function getMedia_voice()


//    {
//        $media_id="YUyNd-9bywMNy86weAf-prhLDCqBLia4bGZxB3LTRQ5GaQk1_sw6rD99xASNcPB9";
//        $url="https://api.weixin.qq.com/cgi-bin/media/get?access_token=".$this->access_token."&media_id=".$media_id;
//        $data=file_get_contents($url);
//        $file_name=time().'.arm';
//        file_put_contents($file_name,$data);
//        echo "下载文件成功";
//    }


    /**
     * 素材管理
     */
    protected function getMedia($media_id,$msg_type)
    {
        $url="https://api.weixin.qq.com/cgi-bin/media/get?access_token=".$this->access_token."&media_id=".$media_id;
        //获取素材内容
        $client=new Client();
        $response=$client->request('GET',$url);
        //获取文件后缀名
        $f = $response->getHeader('Content-disposition')[0];
        $extension=substr(trim($f,'"'),strpos($f,'.'));
        //获取文件内容
        $file_content=$response->getBody();
        //保存文件
        $save_path='wechat_media/';
        if ($msg_type=='image'){  //保存图片文件
            $file_name=date('YmdHis').mt_rand(11111,99999).$extension;
            $save_path=$save_path .'img/'. $file_name;
        }elseif($msg_type=='voice'){    //保存语音文件
            $file_name=date('YmdHis').mt_rand(11111,99999).$extension;
            $save_path=$save_path .'voice/'. $file_name;
        }elseif($msg_type=='video'){    //保存视频文件
            $file_name=date('YmdHis').mt_rand(11111,99999).$extension;
            $save_path=$save_path .'video/'. $file_name;
        }
        file_put_contents($save_path,$file_content);
    }

    /**
     * 刷新access_token
     */
    public function flushAccessToken()
    {
        $key="wexin_access_token";
        Redis::del($key);
        echo $this->getAccessToken();
    }


    /**
     * 自定义菜单
     */
    public function createMenu()
    {
        $url='http://1905lijian.comcto.com/vote/index';
        $redirect_uri=urlencode($url);  //授权后跳转页面
        //创建自定义菜单的接口地址
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$this->access_token;
        $menu = [
            'button'    => [
                [
                    'type'  => 'click',
                    'name'  => '获取天气',
                    'key'   => 'weather'
                ],
                [
                    'type'  => 'view',
                    'name'  => '投票',
                    'url'   => 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx313c304baa2906a7&redirect_uri='.$redirect_uri.'&response_type=code&scope=snsapi_userinfo&state=wechat#wechat_redirectm'
                ],
            ]
        ];
        echo "<pre>"; print_r($menu);  echo "</pre>";
        $menu_json = json_encode($menu,JSON_UNESCAPED_UNICODE);
        $client = new Client();
        $response = $client->request('POST',$url,[
            'body'  => $menu_json
        ]);

        echo $response->getBody();      //接收 微信接口的响应数据
    }
}
