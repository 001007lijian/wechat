<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Redis;
use Illuminate\Http\Request;

class VoteController extends Controller
{
    public function delKey()
    {
        $key=$_GET['k'];
        echo 'Delete Key'.$key;echo"</br>";
        Redis::del($key);
    }


    public function index()
    {
//        echo "<pre>"; print_r($_GET);  echo "</pre>";  die;
        $code=$_GET['code'];
        //获取access_token
        $data=$this->getAccessToken($code);
        //获取用户基本信息
        $userinfo=$this->getUserInfo($data['access_token'],$data['openid']);

        //保存用户信息
        $userinfo_="h:u".$data['openid'];
        Redis::hMset($userinfo_);


        //处理业务逻辑
        $openid=$userinfo['openid'];
        $key = 'ss:vote:lijian';

        //判断是否已经投过票
        if (Redis::zrank($key,$userinfo['openid'])){
            echo "您已经投过票了";
        }else{
            Redis::Zadd($key,time(),$openid);
        }

        $total=Redis::zCard($key);  //获取总数
//        echo '投票总人数：'.$total;   echo '</br>';
        $members=Redis::zRange($key,0,-1,true); //获取所有投票人的openid
        echo "<pre>";   print_r($members);  echo "</pre>";
        foreach ($members as $k=>$v){
            $u_k='h:u'.$k;
            $u=Redis::hgetAll($u_k);
//            $u=Redis::hMget($u_k,['nickname','sex','headimgurl']);
            echo '<img src="'.$u['headimgurl'].'">';
        }
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


    /**
     *
     */
    public function hashTest()
    {
        $uid=1000;
        $key='h:user_info:uid'.$uid;
        $user_info=[
            'user_name'=>'zhangsan',
            'email'=>'zhangsan@qq.com',
            'age'=>19,
            'sex'=>1
        ];
        Redis::hMset($key,$user_info);
        die;
        echo "<hr/>";
        $u=Redis::hGetAll($key);
        echo "<pre>";   print_r($u);   echo "</pre>";
    }
}
