<?php
/**
 * Created by PhpStorm.
 * User: rwcym
 * Date: 2018/7/24
 * Time: 20:19
 */

namespace app\index\controller;

use app\index\model\User;
use think\cache\driver\Redis;
use think\Config;
use think\Controller;
use think\Db;

class Auth extends Controller
{
    //获取token，存入redis
    public function index()
    {
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:GET, POST, OPTIONS');
        $code = $this->request->get("code");
        $redis = new Redis();
        $config = Config::get("auth_config");
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=" . $config["appid"] . "&secret=" . $config["appsecret"] . "&code=" . $code . "&grant_type=authorization_code";
        $access_token_info = $this->curl($url);
        $access_token_arr = json_decode($access_token_info, true);
        if ($access_token_arr) {
            $redis->set("access_token_".$access_token_arr["openid"], $access_token_arr["access_token"], 7000);
            header('Location:https://www.loobo.top/index.html#/?openid=' . $access_token_arr["openid"]);
        } else {
            $result["msg"] = "失败";
            $result["code"] = "-1";
            return $result;
        }
    }

    public function getinfo()
    {
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:GET, POST, OPTIONS');
        $openid = $this->request->post("openid");
        $redis = new Redis();
        $info_url = "https://api.weixin.qq.com/sns/userinfo?access_token=" . $redis->get("access_token_$openid"). "&openid=" . $openid;
        $user_info = $this->curl($info_url);
        $user_info_arr = json_decode($user_info, true);
        if(empty($user_info_arr)){
            $result["msg"] = "请授权微信登陆";
            $result["code"] = "-1";
            return $result;
        }
        $data["user_name"] = $user_info_arr["nickname"];
        $data["user_head_img"] = $user_info_arr["headimgurl"];
        $data["user_program_id"] = $user_info_arr["openid"];
        $openid_info = User::getuser($user_info_arr["openid"]);
        if ($openid_info) {
            $result["msg"] = "成功";
            $result["code"] = "1";
            $result["data"] = $openid_info;
        } else {
            $user_id = Db::table("small_program_user")->insertGetId($data);
            if ($user_id) {
                $data["id"] = $user_id;
                $redis->set("openid_" . $user_info_arr["openid"], $data);
                $result["msg"] = "成功";
                $result["code"] = "1";
                $result["data"] = $data;
            };
        }
        return $result;
    }
  /*
     * 退出登录
     * */
    public function deltoken()
    {
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:GET, POST, OPTIONS');
        $redis = new Redis();
        $openid = $this->request->request("openid");
        $redis->rm("access_token_$openid");
        if (empty($redis->get("access_token_$openid"))) {
            $result["msg"] = "成功";
            $result["code"] = "1";
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
}