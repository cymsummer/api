<?php
/**
 * Created by PhpStorm.
 * User: rwcym
 * Date: 2018/7/31
 * Time: 15:11
 */
namespace app\index\model;

use think\Model;
use think\cache\driver\Redis;
use think\Db;
class User extends Model{
    /*
     * 获取用户信心
     * */
    public static function getuser($openid){
        $redis = new Redis();
        $openid_info=$redis->get("openid_".$openid);
        if(empty($openid_info)){
            $openid_info=Db::table("small_program_user")->where("user_program_id",$openid)->find();
        }
        return $openid_info;
    }

    //获取用户信息
    public static function getUserInfo($openid){
        $redis = new Redis();
        $openid_info=$redis->hGetAll("openid_".$openid);
        if(empty($openid_info)){
            $openid_info=Db::table("small_program_user")->where("user_program_id",$openid)->find();
            $redis->hMset("openid_".$openid,$openid_info);
        }
        return $openid_info;
    }
}