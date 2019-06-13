<?php
/**
 * Created by PhpStorm.
 * User: rwcym
 * Date: 2018/11/11
 * Time: 12:33
 */

namespace app\index\controller;

use think\Controller;
use think\cache\driver\Redis;
use think\Request;

class Ini extends Controller
{
//    获取redis
    public function index()
    {
        $redis = new Redis();
        print_r("program_score_id : " . $redis->get("program_score_id"));
        echo "<br>";
        print_r("program_comment_id : " . $redis->get("program_comment_id"));
        echo "<br>";
        print_r("program_fabulous_id : " . $redis->get("program_fabulous_id"));
        echo "<br>";
        print_r("program_tab_id : " . $redis->get("program_tab_id"));
    }

    //设置redis
    public function set(Request $request)
    {
        $id1 = $request->get("id1");//36314
        $id2 = $request->get("id2");//36372
        $id3 = $request->get("id3");//36314
        $id4 = $request->get("id4");//36360
        $redis = new Redis();
        $redis->set("program_score_id", $id1);
        $redis->set("program_comment_id", $id2);
        $redis->set("program_fabulous_id", $id3);
        $redis->set("program_tab_id", $id4);
        print_r("program_score_id : " . $redis->get("program_score_id"));
        echo "<br>";
        print_r("program_comment_id : " . $redis->get("program_comment_id"));
        echo "<br>";
        print_r("program_fabulous_id : " . $redis->get("program_fabulous_id"));
        echo "<br>";
        print_r("program_tab_id : " . $redis->get("program_tab_id"));
    }
}