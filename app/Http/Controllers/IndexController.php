<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Model\WechatModel;

class IndexController extends Controller
{
    public function index()
    {
        $code=$_GET['code'];
        $data=$this->getAccessToken($code);

        //判断用户是否存在1
        $openid=$data['openid'];
        $user=WechatModel::where(['openid'=>$openid])->first();
        if ($user){ //用户已存在1
            $userinfo=$user->toArray();
        }else{
            $userinfo=$this->getUserInfo($data['access_token'],$data['openid']);
            //入库用户信息1
            WechatModel::insertGetId($userinfo);
        }
        $data=[
            'user'=>$userinfo
        ];
        return view('index/index',$data);
    }


    /**
     * 通过dode获取access_token
     * @param $code
     */
    protected function getAccessToken($code)
    {
        $url='https://api.weixin.qq.com/sns/oauth2/access_token?appid='.env('APPID').'&secret='.env('APPSECRET').'&code='.$code.'&grant_type=authorization_code';
        $json_data=file_get_contents($url);
        return json_decode($json_data,true);
    }


    /**
     * 获取用户基本信息
     */
    protected function getUserInfo($access_token,$openid)
    {
        $url = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN';
        $json_data = file_get_contents($url);
        $data = json_decode($json_data,true);
        if(isset($data['errcode'])){
            // TODO  错误处理
            die("出错了 40001");       // 40001 标识获取用户信息失败
        }
        return $data;           // 返回用户信息
    }
}
