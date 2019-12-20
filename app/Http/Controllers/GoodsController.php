<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Model\GoodsModel;

class GoodsController extends Controller
{
    public function detail(Request $request)
    {
        $goods_id=$request->input('id');
        $goods=GoodsModel::find($goods_id);
//        echo "<pre>";   print_r($goods->toArray());   echo "</pre>";
        $data=[
            'goods'=>$goods
        ];
        return view('index/detail',$data);
    }
}
