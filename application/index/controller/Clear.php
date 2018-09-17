<?php
/**
 * Created by PhpStorm.
 * User: rwcym
 * Date: 2018/7/14
 * Time: 15:39
 */
namespace app\index\controller;

use think\Controller;
use think\Cache;

class Clear extends Controller{
    public function index(){
        Cache::clear();
    }
}