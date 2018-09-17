<?php
/**
 * Created by PhpStorm.
 * User: rwcym
 * Date: 2018/7/13
 * Time: 22:01
 * Content：萝卜日荐信息
 */
namespace app\index\controller;

use think\Controller;
use think\Db;
use app\index\model\User;

class Recommend extends Controller{
    //日荐数据展示
    public function index(){
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:GET, POST, OPTIONS');
        $page=$this->request->request("page")?$this->request->request("page"):"1";
        $arr=Db::connect("db_config1")->table("luobo_content")->whereTime('publish_date', '<', date("Y-m-d H:i:s", time()))->whereOr("luobo_state = 1")->order("publish_date desc")->page($page,7)->select();
        $weekarray = array("日", "一", "二", "三", "四", "五", "六");
        foreach ($arr as$k=> $v){
            if (date("Y-m-d", strtotime($v['publish_date'])) == date("Y-m-d")) {
                $arr[$k]["new_date"] = "Today";
            } else {
                $arr[$k]["new_date"] = "星期" . $weekarray[date("w", strtotime($v['publish_date']))];
            }
            $arr[$k]["publish_date"]=date("Y年n月j日",strtotime($v['publish_date']));
            $trans   = array_flip(get_html_translation_table(HTML_ENTITIES));
            $arr[$k]["luobo_content"] = strtr($v['luobo_content'], $trans);
            $arr[$k]["id"]=(string)$v["id"];
            $arr[$k]["pl_id"]=(string)$v["pl_id"];
            $arr[$k]["dz"]=(string)$v["dz"];
            $arr[$k]["luobo_see_num"]=(string)$v["luobo_see_num"];
            $arr[$k]["luobo_state"]=(string)$v["luobo_state"];
        }
        $result["msg"]="成功！";
        $result["code"]="1";
        $result["data"]=$arr;
        return $result;
    }
    //详情
    public function info(){
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:GET, POST, OPTIONS');
        $id=$this->request->request("id");//文章id
        $act=$this->request->request("act");//类型
        $page = $this->request->request("page") ? $this->request->request("page") : "1";//页码
        if(empty($id)){
            $result["msg"]="缺少id";
            $result["code"]="-1";
            $result["data"]="";
            return $result;
        }
        //查询数据
        $arr=Db::connect("db_config1")->table("luobo_content")->find($id);
        if(empty($arr)){
            $result["msg"]="无此文章！";
            $result["code"]="-1";
            $result["data"]="";
            return $result;
        }
        //评论
        $comment=Db::table("small_program_comment")->where("comment_id=:comment_id and comment_state!=:comment_state")->bind(['comment_id' => $arr["pl_id"], 'comment_state' => '1'])->order("publish_time desc")->page($page, 20)->select();
        foreach ($comment as $k => $v) {
            $comment[$k]["userinfo"][] =User::getuser($v["comment_user_id"]);
        }
        //点赞添加
        if (!empty($act) && $act == "add") {
            Db::connect("db_config1")->table("luobo_content")->where('id', $id)->setInc("dz");
            Db::connect("db_config1")->table("luobo_content")->where('id', $id)->setInc("luobo_see_num");
        }
        //点赞展示
        $result["msg"]="成功！";
        $result["code"]="1";
        $result["data"]=$arr;
        $result["data"]["comment"]=$comment;
        $result["data"]["fabulous"]=$arr["dz"];
        return $result;
    }

//    //评论功能
//    public function comment()
//    {
//        $user_id = $this->request->request("openid");//用户id
//        if (empty($user_id)) {
//            $result["msg"] = "请登录";
//            $result["code"] = "-1";
//            $result["data"] = "";
//            return $result;
//        }
//        $id = $this->request->request("id");//评论总id
//        if (empty($id)) {
//            $result["msg"] = "缺少评论id";
//            $result["code"] = "-1";
//            $result["data"] = "";
//            return $result;
//        }
//        $data = array();
//        $data["luobo_user_id"] = $user_id;//用户id
//        $data["luobo_pl_info"] = $this->request->request("content");//评论内容
//        $data["pl_id"] = $id;//评论总id
//        $data["luobo_source"] = $this->request->request("source");//评论来源
//        $data["publish_time"] = date("Y-m-d H:i:s",time());//评论时间
//        if (Db::connect("db_config1")->table('luobo_pl')->insert($data)) {
//            $result["msg"] = "成功";
//        } else {
//            $result["msg"] = "失败";
//        };
//        $result["code"] = "1";
//        $result["data"] = "";
//        return $result;
//    }
}