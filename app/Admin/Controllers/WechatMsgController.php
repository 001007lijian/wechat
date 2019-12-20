<?php

namespace App\Admin\Controllers;

use App\Model\WechatModel;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Redis;



class WechatMsgController extends AdminController
{
    protected $title="微信群发";
    protected $access_token;

    public function __construct()
    {
        //获取access_token
        $this->access_token=$this->getAccessToken();
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

    public function sendMsg()
    {
//        echo __METHOD__;
        $openid_arr=WechatModel::select('openid','nickname','sex')->get()->toArray();
//        echo "<pre>";   print_r($openid_arr); echo "</pre>";
        $openid = array_column($openid_arr,'openid');
        echo '<pre>';print_r($openid);echo '</pre>';
        $url = 'https://api.weixin.qq.com/cgi-bin/message/mass/send?access_token='.$this->access_token;
        $msg=date('Y-m-d H:i:s')."马上过年了";

        $data=[
            'touser' => $openid,
            'msgtype' => 'text',
            'text' => ['content'=>$msg]
        ];

        $client=new Client();
        $response=$client->request('POST',$url,[
            'body'=>json_encode($data,JSON_UNESCAPED_UNICODE),
        ]);

        echo $response->getBody();
    }
}
