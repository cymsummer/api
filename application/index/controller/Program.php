<?php
/**
 * Created by PhpStorm.
 * User: rwcym
 * Date: 2018/7/14
 * Time: 10:10
 */

namespace app\index\controller;

use app\index\model\Info;
use app\index\model\User;
use think\Controller;
use think\Db;
use think\Image;
use think\cache\driver\Redis;

class Program extends Controller
{
    //数据展示
    public function index()
    {
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:GET, POST, OPTIONS');
        $page = $this->request->request("page") ? $this->request->request("page") : "1";//分页
        $act = $this->request->request("act") ? $this->request->request("act") : "new";//最热
        $type = $this->request->request("type") ? $this->request->request("type") : "xcx";//小程序
        $cate = $this->request->request("category") ? $this->request->request("category") : "";//小程序类别
        if ($type == "xcx") {
            $where = "1";
        } else {
            $where = "2";
        }
        if (!empty($cate)) {
            $cate_where = " and program_category_id=$cate and program_audit_status=2";
        } else {
            $cate_where = " and program_audit_status=2";
        }
        $category = Db::table("small_program_category")->where("category_type", $where)->field('id,category_name')->select();
        //获取文章id
        if ($act == "hot") {
            $arr = Db::table("small_program")->where("program_style=" . $where  .$cate_where)->field("id,program_title,program_subtitle,program_icon,program_audit_status")->page($page, 24)->order("program_see_num desc,release_time desc")->select();
        } elseif ($act == "new") {
            $arr = Db::table("small_program")->where("program_style=" . $where  .$cate_where)->field("id,program_title,program_subtitle,program_icon,program_audit_status")->page($page, 24)->order("id desc")->select();
        }
        $count = count($arr);
        $pagecount = ceil($count / 24);
        if (empty($arr)) {
            $arr=[];
        }
        $result["msg"] = "成功！";
        $result["code"] = "1";
        $result["data"]["page"] = $page;
        $result["data"]["pagecount"] = $pagecount;
        $result["data"]["category"] = $category;
        $result["data"]["$act"] = $arr;
        return $result;
    }

    //分类数据
    public function cate_show($type){
        if ($type == "xcx") {
            $where = "1";
        } else {
            $where = "2";
        }
        $category = Db::table("small_program_category")->where("category_type", $where)->field('id,category_name')->select();
        return $category;
    }

    //数据查询
    public function proShow(){
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:GET, POST, OPTIONS');
        $page = $this->request->request("page") ? $this->request->request("page") : "1";//分页
        $act = $this->request->request("act") ? $this->request->request("act") : "new";//最热
        $type = $this->request->request("type") ? $this->request->request("type") : "xcx";//小程序
        $cate = $this->request->request("category") ? $this->request->request("category") : "";//小程序类别
        if (!empty($cate)) {//有分类查询
            $where=" a.program_audit_status=2 and b.category_id=$cate ";
        }else{//默认查询已经审核通过数据
            $where=" program_audit_status=2";
        }
        
        if ($type == "xcx") {//请求来源是小程序
            if(!empty($where)){
                $where.=" and ";
            }
            if(!empty($cate)){
                $where .= " a.program_style=1 ";
            }else{
                $where .= " program_style=1 ";
            }
        } else {//请求来源为小游戏
            if(!empty($where)){
                $where.=" and ";
            }
            if(!empty($cate)){
                $where .= " a.program_style=2 ";
            }else{
                $where .= " program_style=2 ";
            }
        }

        if ($act == "hot") {//热门顺序
            if(!empty($cate)){
                $order = "a.program_see_num desc,a.release_time desc ";
            }else{
                $order="program_see_num desc,release_time desc";
            }
        } elseif ($act == "new") {//最新排序
            if(!empty($cate)){
                $order = "a.id desc";
            }else{
                $order="id desc";
            }
        }
        if (!empty($cate)) {//有分类的时候连表查询
            $sql = "select * from small_program as a inner join small_pro_cate as b on a.id=b.program_id where $where order by $order limit $page,24";
            $count_sql="select count(1) as total from small_program as a inner join small_pro_cate as b on a.id=b.program_id where $where order by $order";
        }else{//全部数据
            $sql = "select * from small_program where $where order by $order limit $page,24";
            $count_sql="select count(1) as total from small_program where $where order by $order ";
        }
        $arr=Db::query($sql);
        $count=Db::query($count_sql);
        $pagecount = ceil($count[0]['total'] / 24);
        if (empty($arr)) {
            $arr=[];
        }
        //分类
        $category=$this->cate_show($type);
        $result["msg"] = "成功！";
        $result["code"] = "1";
        $result["data"]["page"] = $page;
        $result["data"]["pagecount"] = $pagecount;
        $result["data"]["category"] = $category;
        $result["data"]["$act"] = $arr;
        return $result;
    }


    //数据详情
    public function detail()
    {
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:GET, POST, OPTIONS');
        $redis = new Redis();
        $id = $this->request->request("id");//小程序id
        $page = $this->request->request("page") ? $this->request->request("page") : "1";//页码
        $act = $this->request->request("act");//类型
        $num = $this->request->request("score"); //分数
        $source = $this->request->request("source"); //来源
        $ip = $this->request->request("ip"); //ip
        $openid = $this->request->request("user_program_id");
        $result["data"]["user_info"] = User::getuser($openid);
        if (empty($id)) {
            $result["msg"] = "缺少id";
            $result["code"] = "-1";
            $result["data"] = "";
            return $result;
        }

        //浏览量加1
        Db::table('small_program')->where('id', $id)->setInc('program_see_num');

//        $redis->rm("wz_detail:" . $id);//删除redis文章信息
        $arr = $redis->hMGet("wz_detail:" . $id, $redis->get("wz_detail_key"));
        if (empty($arr["id"])) {
            $arr = Info::index($id);
        }
        //评论内容
        $comment = Db::table('small_program_comment')->where("comment_id=:comment_id and comment_state!=:comment_state")->bind(['comment_id' => $arr["program_comment_id"], 'comment_state' => '1'])->order("publish_time desc")->page($page, 20)->select();
        foreach ($comment as $k => $v) {
            $comment[$k]["userinfo"][] = Db::table('small_program_user')->where("user_program_id", $v["comment_user_id"])->find();
        }
        //评分添加
        if (!empty($act) && $act == "add") {
            $data = array();
            $data["score_num"] = $num;
            $data["score_id"] = $id;
            $data["score_source"] = $source;
            $data["score_ip"] = $ip;
            Db::table("small_program_score")->insert($data);
        }
        $score["count"] = (string)Db::table("small_program_score")->where("score_id=$id")->count();
        $score["avg"] = (string)Db::table("small_program_score")->where("score_id=$id")->avg("score_num");
        $score["avg"] = sprintf("%.1f", $score["avg"]);
        //相关推荐
        if (empty($arr["program_title"])) {
            $result["msg"] = "缺少小程序标题";
            $result["code"] = "-1";
            $result["data"] = "";
            return $result;
        }
        $title = $arr["program_title"];
//        $about1 = Db::table("small_program_label")->where("label_name", "like", "%" . $arr["program_title"] . "%")->select();
        $about1 = Db::query("select id,label_name from small_program_label where  LOCATE('$title', 'label_name')");//根据标签推荐 索引
        if ($about1) {
            foreach ($about1 as $v) {
                $about_arr[] = Info::index($v["id"], "special");
            }
        } else {
            $about2 = Db::query("select id,program_title from small_program where  LOCATE('$title', 'program_title')");//根据标题推荐 索引
//            $about2 = Db::table("small_program")->where("program_title", "like", "%" . $arr["program_title"])->select();
            if ($about2 && ($about2[0]["program_title"] != $arr["program_title"])) {
                foreach ($about2 as $v) {
                    $about_arr[] = Info::index($v["id"], "special");
                }
            } else {
                $about_arr = $this->getRandTable($arr["program_category_id"]);//随机推荐
            }
        }
        $result["msg"] = "成功！";
        $result["code"] = "1";
        $result["data"] = $arr;
        $result["data"]["comment"] = $comment;
        $result["data"]["score"] = $score;
        $result["data"]["about"] = $about_arr;

        return $result;
    }

    public function ProDetail()
    {
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:GET, POST, OPTIONS');
        $redis = new Redis();
        $id = $this->request->request("id");//小程序id
        $page = $this->request->request("page") ? $this->request->request("page") : "1";//页码
        $act = $this->request->request("act");//类型
        $num = $this->request->request("score"); //分数
        $source = $this->request->request("source"); //来源
        $ip = $this->request->request("ip"); //ip
        $openid = $this->request->request("user_program_id");
        $result["data"]["user_info"] = User::getUserInfo($openid);
        if (empty($id)) {
            $result["msg"] = "缺少id";
            $result["code"] = "-1";
            $result["data"] = "";
            return $result;
        }
        //浏览量加1
        Db::table('small_program')->where('id', $id)->setInc('program_see_num');
        $arr = $redis->hGetAll("wz_detail:" . $id);
        if (!empty($arr["id"])) {
            $arr = Info::ArticleDetail($id);
        }
        //评论内容
        $comment = Db::table('small_program_comment')->where("comment_id=:comment_id and comment_state!=:comment_state")->bind(['comment_id' => $arr["program_comment_id"], 'comment_state' => '1'])->order("publish_time desc")->page($page, 20)->select();
        foreach ($comment as $k => $v) {
            $comment[$k]["userinfo"][] = Db::table('small_program_user')->where("user_program_id", $v["comment_user_id"])->find();
        }
        //评分添加
        if (!empty($act) && $act == "add") {
            $data = array();
            $data["score_num"] = $num;
            $data["score_id"] = $id;
            $data["score_source"] = $source;
            $data["score_ip"] = $ip;
            Db::table("small_program_score")->insert($data);
        }
        $score["count"] = (string)Db::table("small_program_score")->where("score_id=$id")->count();
        $score["avg"] = (string)Db::table("small_program_score")->where("score_id=$id")->avg("score_num");
        $score["avg"] = sprintf("%.1f", $score["avg"]);
        //相关推荐
        if (empty($arr["program_title"])) {
            $result["msg"] = "缺少小程序标题";
            $result["code"] = "-1";
            $result["data"] = "";
            return $result;
        }
        $title = $arr["program_title"];
//        $about1 = Db::table("small_program_label")->where("label_name", "like", "%" . $arr["program_title"] . "%")->select();
        $about1 = Db::query("select id,label_name from small_program_label where  LOCATE('$title', 'label_name')");//根据标签推荐 索引
        if ($about1) {
            foreach ($about1 as $v) {
                $about_arr[] = Info::ArticleDetail($v["id"], "special");
            }
        } else {
            $about2 = Db::query("select id,program_title from small_program where  LOCATE('$title', 'program_title')");//根据标题推荐 索引
//            $about2 = Db::table("small_program")->where("program_title", "like", "%" . $arr["program_title"])->select();
            if ($about2 && ($about2[0]["program_title"] != $arr["program_title"])) {
                foreach ($about2 as $v) {
                    $about_arr[] = Info::ArticleDetail($v["id"], "special");
                }
            } else {
                //获取该分类id
                $catproinfo=Db::table('small_pro_cate')->where('program_id',$id)->find();
                $about_arr = $this->getRandTable($catproinfo['category_id']);//随机推荐
                if(empty($about_arr)){
                    $about_arr=[];
                }
            }
        }
        $result["msg"] = "成功！";
        $result["code"] = "1";
        $result["data"] = $arr;
        $result["data"]["comment"] = $comment;
        $result["data"]["score"] = $score;
        $result["data"]["about"] = $about_arr;

        return $result;
    }

    /*
     * 随机获取数据
     * */
    public function getRandTable($cate_id)
    {
        $num = 7;    //需要抽取的默认条数
        $table = 'pro_cate';    //需要抽取的数据表
        $cate_arr = explode(",", $cate_id);
        $countcus = "";
        for ($i = 0; $i < count($cate_arr); $i++) {
            $countcus .= Db::name($table)->where("category_id", $cate_arr[$i])->count();    //获取总记录数
        }
        $min = Db::name($table)->min('id');    //统计某个字段最小数据
        if ($countcus < $num) {
            $num = $countcus;
        }
        $i = 1;
        $flag = 0;
        $ary = array();

        while ($i <= $num) {
            $rundnum = rand($min, $countcus);//抽取随机数
            if ($flag != $rundnum) {
                //过滤重复
                if (!in_array($rundnum, $ary)) {
                    $ary[] = $rundnum;
                    $flag = $rundnum;
                } else {
                    $i--;
                }
                $i++;
            }
        }
        $list=[];
        for ($i = 0; $i < count($ary); $i++) {
//            $list[] = Db::table("small_program")->where('id', $ary[$i])->find();
            $list[] = Info::ArticleDetail($ary[$i], "special");
        }
//        print_r($list);die;
        return $list;
    }

    //发布&编辑
    public function insert()
    {
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:GET, POST, OPTIONS');
        $user_id = $this->request->request("openid");//是否登陆
        $data = $this->request->post("data/a");//修改 || 添加数据
        if (empty($user_id)) {
            $result["msg"] = "请登录";
            $result["code"] = "-1";
            return $result;
        }
        if (empty($data)) {
            $result["msg"] = "传送数据有误！";
            $result["code"] = "-1";
            return $result;
        }
        $newdata = array();
        foreach ($data as $v) {
            $newdata = $v;
        }
        $arr = json_decode($newdata, true);//解析数据
        $act = $arr["act"]; //修改标识
        $upd_id = $arr["id"];//文章id
        $redis = new Redis(); //连接本地的 Redis 服务
        if (empty($act) && empty($upd_id)) {
            //添加数据
            $arr["program_score_id"] = $redis->inc("program_score_id");//自增的评分总id
            $arr["program_comment_id"] = $redis->inc("program_comment_id");//自增的评论总id
            $arr["program_fabulous_id"] = $redis->inc("program_fabulous_id");//自增的点赞总id
            $info = Db::table("small_program")->where("program_title", $arr['program_title'])->select();//判定小程序是否存在
            if ($info) {
                $result["msg"] = "小程序已存在，请直接搜索查看";
                $result["code"] = "1";
                return $result;
            }
        }
        $arr["create_time"] = time();//创建时间
        $arr["release_time"] = time();//发布时间
        $arr["program_pic"] = trim($arr["program_pic"]);//图片上传
        $label = array();
        $label["label_time"] = time();//标签添加时间
        //token验证
        if (!empty($arr["program_appid"]) && !empty($arr["program_appsecret"])) {
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $arr["program_appid"] . "&secret=" . $arr["program_appsecret"];
            $access_token = $this->curl($url);
            $access_token_arr = json_decode($access_token, true);
            if (empty($access_token_arr["access_token"])) {
                $result["msg"] = "appid与appsecret认证失败";
                $result["code"] = "-1";
                return $result;
            }else{
                $arr["program_offical"]="1";
            }
        }
        $label["label_name"] = $arr["program_tab_name"];//标签内容
        if ($act == "upd" && !empty($upd_id)) {
            //标签修改
            if (Db::table("small_program_label")->where("label_id", $arr["program_tab_id"])->delete()) {
                Db::table("small_program_label")->where("label_id", $arr["program_tab_id"])->update($label);
                $label_id = $arr["program_tab_id"];
            };
        } else {
            //标签添加
            $label["label_id"] = $redis->inc("program_tab_id");//自增的标签总id
            $label_id = Db::table("small_program_label")->insertGetId($label);
        }

        if ($label_id) {
            unset($arr["program_tab_name"]);
            if ($act == "upd" && !empty($upd_id)) {
                unset($arr["act"]);
                unset($arr["id"]);
                $redis->hMset("wz_cover_detail:" . $upd_id, $arr);
                Db::table("small_program")->where("id", $upd_id)->update($arr);
                Db::table("small_pro_cate")->where("program_id", $upd_id)->delete();
                $id = $upd_id;
            } else {
                unset($arr["act"]);
                unset($arr["id"]);
                $arr["program_tab_id"] = $label["label_id"];
                $id = Db::table("small_program")->insertGetId($arr);
                $redis->hMset("wz_cover_detail:" . $id, $arr);
            }
            if ($id) {
                $program_category_id = $arr["program_category_id"];
                $cate_arr = explode(",", $program_category_id);
                for ($i = 0; $i < count($cate_arr); $i++) {
                    $cate["program_id"] = $id;
                    $cate["program_type"] = $arr["program_style"];
                    $cate["category_id"] = $cate_arr[$i];
                    $cate["publish_time"] = time();
                    Db::table("small_pro_cate")->insertGetId($cate);//分类添加
                }
                if ($i == count($cate_arr)) {
                    $result["msg"] = "成功";
                    $result["code"] = "1";
                    return $result;
                }

            } else {
                $result["msg"] = "失败";
                $result["code"] = "-1";
                return $result;
            }
        }
    }

    //图片上传
    public function imginsert()
    {
//        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:GET, POST, OPTIONS');
        error_reporting(E_ERROR | E_PARSE);
        $img1 = $_FILES["file1"];
        $img2 = $_FILES["file2"];
        $img3 = $_FILES["file3"];
        $img4 = $_FILES["file4"];
        $img5 = $_FILES["file5"];
        if (!empty($img1["tmp_name"])) {
            $image_info[] = $img1;
        }
        if (!empty($img2["tmp_name"])) {
            $image_info[] = $img2;
        }
        if (!empty($img3["tmp_name"])) {
            $image_info[] = $img3;
        }
        if (!empty($img4["tmp_name"])) {
            $image_info[] = $img4;
        }
        if (!empty($img5["tmp_name"])) {
            $image_info[] = $img5;
        }
        if (!isset($image_info)) {
            $result["msg"] = "失败";
            $result["code"] = "-1";
            $result["data"] = array();
            return $result;
        }
        $img_count = count($image_info);
        $img = "";
        $config = config("img_config");
        if (!empty($image_info)) {
            foreach ($image_info as $v) {
                $image = Image::open($v["tmp_name"]);
                // 返回图片的类型
                $type = $image->type();
                //添加水印
                //     $image->water('../public/luobo/images/logo.png', Image::WATER_SOUTHWEST, 100)->save('../public/upload/' . $new_img_name);
                $new_img_name =$this->random(18) . "." . $type;
                $image->save($config["__ROOT__"] . '/' . $new_img_name);
                $data["image_url"] = $config["src"] . "/" . $new_img_name;
                $data["type"] = "1";
                Db::connect("db_config1")->table("luobo_image")->insert($data);
                if ($img_count == 1) {
                    $img .= $data["image_url"];
                } else {
                    $img .= $data["image_url"] . ",";
                }
            }
        }
        $result["msg"] = "成功";
        $result["code"] = "1";
        $result["data"] = $img;
        return $result;
    }

    public function random($length)
    {
        $hash = '';
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
        $max = strlen($chars) - 1;
        PHP_VERSION < '4.2.0' && mt_srand((double)microtime() * 1000000);
        for ($i = 0; $i < $length; $i++) {
            $hash .= $chars[mt_rand(0, $max)];
        }
        return $hash;
    }

    //评论功能
    public function comment()
    {
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:GET, POST, OPTIONS');
        $openid = $this->request->request("openid");//openid
        if (empty($openid)) {
            $result["msg"] = "请登录";
            $result["code"] = "-1";
            $result["data"] = "";
            return $result;
        }
        $id = $this->request->request("id");//评论总id
        if (empty($id)) {
            $result["msg"] = "缺少评论id";
            $result["code"] = "-1";
            $result["data"] = "";
            return $result;
        }
        $data = array();
        $data["comment_user_id"] = $openid;//用户id
        $data["comment_content"] = $this->request->request("content");//评论内容
        $data["comment_id"] = $id;//评论总id
        $data["comment_source"] = $this->request->request("source");//评论来源
        $data["publish_time"] = time();//评论时间
        $comment_id = Db::table('small_program_comment')->insertGetId($data);
        if ($comment_id) {
            $result["msg"] = "成功";
        } else {
            $result["msg"] = "失败";
        };
        $result["code"] = "1";
        $result["data"] = "";
        return $result;
    }

    //个人中心
    public function user()
    {
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:GET, POST, OPTIONS');
        $user_id = $this->request->request("openid");
        if (empty($user_id)) {
            $result["msg"] = "缺少user_id";
            $result["code"] = "-1";
            return $result;
        }
        $user_info["user_info"] = User::getuser($user_id);
        $user_info["xcx"] = Db::table("small_program")->where("program_user_id", $user_info["user_info"]["user_program_id"])->select();
        foreach ($user_info["xcx"] as $k => $v) {
            $user_info["xcx"][$k] = Info::index($v["id"]);
        }
//        print_r($user_info);die;
        $result["msg"] = "成功";
        $result["code"] = "1";
        $result["data"] = $user_info;
        return $result;
    }

    //搜索展示
    public function search()
    {
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:GET, POST, OPTIONS');
        $title = $this->request->request("title");
        $openid = $this->request->request("openid");
        $result["data"]["user_info"] = User::getuser($openid);
        $arr["recommend"][] = Db::connect("db_config1")->table("luobo_content")->where("luobo_title", "like", "%" . $title . "%")->order("luobo_see_num desc")->find();
        $info = Db::table("small_program_label")->where("label_name", "like", "%" . $title . "%")->field("label_id")->select();
        if (empty($info)) {
            $detail = Db::table("small_program")->where("program_title", "like", "%" . $title . "%")->order("program_see_num desc")->select();
            foreach ($detail as $k => $v) {
                $arr["xcx"][$k] = Info::index($v["id"], "special");
            }
        } else {
            foreach ($info as $k => $v) {
                $new_info = Db::table("small_program")->where("program_tab_id", $v["label_id"])->field("id,program_title,program_icon")->find();
                $arr["xcx"][$k] = $new_info;
            }
        }
        $result["msg"] = "成功";
        $result["code"] = "1";
        $result["data"] = $arr;
        return $result;
    }


    /*
     * 申请认领
     * */
    public function claim()
    {
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:GET, POST, OPTIONS');
        $appid = $this->request->request("appid");
        $openid = $this->request->request("openid");
        $appsecret = $this->request->request("appsecret");
        $id = $this->request->request("id");
        $title = $this->request->request("title");
        $redis = new Redis();
        if (empty($openid) || empty($appsecret) || empty($appid)) {
            $result["msg"] = "缺少参数";
            $result["code"] = "-1";
            return $result;
        }
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $appid . "&secret=" . $appsecret;
        $access_token = $this->curl($url);
//           print_r($access_token);die;
        $access_token_arr = json_decode($access_token, true);
//        $access_token_arr["access_token"] = "11";
        $data["appid"] = "";
        $data["appsecret"] = "";
        if (!empty($access_token_arr["access_token"])) {
            $user_info = User::getuser($openid);
            if (empty($user_info)) {
                $result["msg"] = "无此用户信息";
                $result["code"] = "-1！";
                return $result;
            }
            if (empty($user_info["appid"])) {
                $data["appid"] .= $appid;
            } else {
                $data["appid"] .= $appid . "," . $user_info["appid"];
            }
            if (empty($user_info["appsecret"])) {
                $data["appsecret"] .= $appsecret;
            } else {
                $data["appsecret"] .= $appsecret . "," . $user_info["appsecret"];
            }
            $token_info = Db::table("small_program_user")->where("user_program_id", $openid)->find();
            if ((strpos($token_info["appid"], $appid) !== false) && (strpos($token_info["appsecret"], $appsecret) !== false)) {
                $result["msg"] = "appid与appsecret已验证！";
                $result["code"] = "-1";
                return $result;
            } else {
                if (Db::table("small_program_user")->where("user_program_id", $openid)->update($data)) {
                    $where["program_appid"] = $appid;
                    $where["program_appsecret"] = $appsecret;
                    $where["program_user_id"] = $openid;
                    $where["program_offical"] = "1";
                    $where["update_time"] = time();
                    $redis->hMset("wz_cover_detail:" . $id, $where);
                    if (Db::table("small_program")->where("id", $id)->update($where)) {
                        $result["msg"] = "认领成功";
                        $result["code"] = "1";
                        $result["data"] = array("id" => $id);
                        return $result;
                    };
                } else {
                    $result["msg"] = "appid与appsecret有误！";
                    $result["code"] = "-1";
                    return $result;
                }
            }
        } else {
            $result["msg"] = "appid与appsecret有误！";
            $result["code"] = "-1";
            return $result;
        }
    }


    public function curl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }

    /*
     * 清除redis
     * */
    public function clear()
    {
        $redis = new Redis();
        $id = $this->request->request("id");
        $redis->rm("wz_detail:" . $id);
        $redis->rm("wz_detail:" . $id);
    }

    //存储redis
    public function set()
    {
        $redis=new Redis();
        $id=$this->request->request("id");
        $arr = Db::table("small_program")->where('id', $id)->find();//查询文章信息
        $label_arr=Db::table("small_program_label")->where("label_id",$arr["program_tab_id"])->find();//查询标签信息
        $arr["program_tab_name"]=$label_arr["label_name"];//获取标签信息
        $redis->hMset("wz_detail:" . $id, $arr);//存储文章详情
        $redis->set("wz_detail_key", array_keys($arr));//存储文章详情key
        print_r($redis->get("wz_detail_key"));//获取key
        $redis->set("wz_cover_key", array("id", "program_title", "program_icon"));//存储简单信息
        die;
    }

    //============暂不需要===========
    //分类展示
    public function show()
    {
        $id = $this->request->get("id");
        $arr = Db::table('small_program')->where('program_category_id', $id)->select();
        $result["msg"] = "成功！";
        $result["code"] = "1";
        $result["data"] = $arr;
        return $result;
    }

    //相关推荐
    public function about()
    {
        //小程序标题
        $program_title = $this->request->get("program_title");
        if (empty($program_title)) {
            $result["msg"] = "缺少小程序标题";
            $result["code"] = "-1";
            $result["data"] = "";
            return $result;
        }
        $arr = Db::table("small_program_label")->where("label_name", "like", "%" . $program_title . "%")->select();
        if ($arr) {
            foreach ($arr as $v) {
                $new_arr[] = Db::table("small_program")->find($v["id"]);
            }
        } else {
            $new_arr = array();
        }
        $result["msg"] = "成功！";
        $result["code"] = "1";
        $result["data"] = $new_arr;
        return $result;

    }

    //评分展示
    public function score()
    {
        //评分id
        $id = $this->request->get("id");
        if (empty($id)) {
            $result["msg"] = "缺少id";
            $result["code"] = "-1";
            $result["data"] = "";
            return $result;
        }
        //类型
        $act = $this->request->get("act");
        //分数
        $score = $this->request->get("score");
        //来源
        $source = $this->request->get("source");
        //ip
        $ip = $this->request->get("ip");
        if (!empty($act) && $act == "add") {
            $data = array();
            $data["score_num"] = $score;
            $data["score_id"] = $id;
            $data["score_source"] = $source;
            $data["score_ip"] = $ip;
            Db::table("small_program_score")->insert($data);
        }

        $arr["count"] = (string)Db::table("small_program_score")->where("source_id=$id")->count();
        $arr["avg"] = (string)Db::table("small_program_score")->where("source_id=$id")->avg("score_num");
        $arr["avg"] = sprintf("%.1f", $arr["avg"]);
        $result["msg"] = "成功！";
        $result["code"] = "1";
        $result["data"] = $arr;
        return $result;
    }
}
