<?php
/**
 * Created by PhpStorm.
 * User: rwcym
 * Date: 2019/6/11
 * Time: 18:29
 * Content：抓取小程序数据
 */
namespace app\index\controller;

use think\Controller;
use think\Db;
use think\Log;

class Spirde extends Controller{

    //抓取知晓程序
    public function zhixiao(){
        //接收数据
        $json=$this->request->post("data");//小程序信息
        if(empty($json)){
            return json_encode(['code'=>'-2','msg'=>"请求数据有问题！"]);
        }
        $data=json_decode($json,true);
        $zx_info=Db::table("small_program")->where(['zx_id'=>$data['zx_id']])->find();//小程序信息查询
        if(empty($zx_info)) {//数据入库
            $data['create_time']=time();
            $data['release_time']=time();
            $InsertId = Db::table("small_program")->insertGetId($data);//添加小程序
            $tag = $this->request->post("tag");//标签信息
            $cate_data = [];//添加分类的数组
            $cate_data['program_id'] = $InsertId;//小程序id
            $cate_data['program_type'] = $data['program_style'];//小程序类型
            $cate_data['publish_time'] = time();//创建时间
            $this->pro_cate($tag, $cate_data);//分类信息处理

            $tag_data = [];//添加标签的数组
            $tag_data['program_id'] = $InsertId;//小程序id
            $tag_data['label_name'] = $tag;//分类名称
            $tag_data['label_type'] = '1';//标签类型
            $tag_data['label_time'] = time();//创建时间
            if (!Db::table('small_program_label')->insert($tag_data)) {//数据添加
                Log::write($json, 'error');
                return json_encode(['code' => "-1", 'msg' => '错误！', 'data' => $json]);
            } else {
                Log::write($data, 'info');
                return json_encode(['code' => '1', 'msg' => '成功！']);
            }
        }
    }

    //知晓程序分类数据处理
    public function pro_cate($tag,$cate_data){
        $tag_arr=explode(',',$tag);
        foreach ($tag_arr as $v){
            $cate_info=Db::table('small_program_category')->where(['category_name'=>$v])->find();
            if($cate_info){
                $cate_data['category_id']=$cate_info['id'];//分类id
                Db::table("small_pro_cate")->insert($cate_data);//添加小程序分类信息
            }
        }
    }




}