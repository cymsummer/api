<?php
/**
 * Created by PhpStorm.
 * User: rwcym
 * Date: 2018/8/8
 * Time: 18:28
 */

namespace app\index\model;

use think\Model;
use think\cache\driver\Redis;
use think\Db;

class Info extends Model
{
    /*
     * 获取文章信息
     * */
    public static function index($id, $act = "none")
    {
        $redis = new Redis();
        $img_url = config("img_config");
        if ($act == "special") {
            $arr = Db::table("small_program")->where('id', $id)->field("id,program_title,program_icon,program_tab_id")->find();
            if ($arr["id"] <= 5774) {
                $arr["program_icon"] = $img_url["src"] . $arr["program_icon"];
            }
            $label_arr=Db::table("small_program_label")->where("label_id",$arr["program_tab_id"])->find();
            $arr["program_tab_name"]=$label_arr["label_name"];
            $redis->hMset("wz_cover_detail:" . $id, $arr);
        } else {
            $arr = Db::table("small_program")->where('id', $id)->find();
            if ($arr["id"] <= 5774) {
                $arr["program_icon"] = $img_url["src"] . $arr["program_icon"];
                $arr["program_qrcode"] = $img_url["src"] . $arr["program_qrcode"];
                $new_arr = explode("，", $arr["program_pic"]);
                unset($new_arr[count($new_arr) - 1]);
                $arr["program_pic"] = "";
                foreach ($new_arr as $key => $val) {
                    $arr["program_pic"] .= $img_url["src"] . $val . "，";
                }
            }
            $label_arr=Db::table("small_program_label")->where("label_id",$arr["program_tab_id"])->find();
            $arr["program_tab_name"]=$label_arr["label_name"];
            $redis->hMset("wz_detail:" . $id, $arr);
        }
        return $arr;
    }
}