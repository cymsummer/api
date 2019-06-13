<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\Route;
//==========萝卜日荐================
Route::rule('recommend','index/Recommend/index');
Route::rule('recommendinfo','index/Recommend/info');
Route::rule('recommendcomment','index/Recommend/comment');


//==========小程序、小游戏==========
Route::rule('program','index/Program/index');
Route::rule('programinfo','index/Program/detail');
Route::rule('about','index/Program/about');
Route::rule('score','index/Program/score');
Route::rule('insert','index/Program/insert');
Route::rule('category','index/Program/show');
Route::post('comment','index/Program/comment');
Route::rule('upload','index/Program/imginsert');
Route::rule('user','index/Program/user');
Route::rule('search','index/Program/search');
Route::rule('claim','index/Program/claim');
Route::rule('set','index/Program/set');

//============微信授权=========
Route::get('token','index/Auth/index');
Route::rule('userinfo','index/Auth/getinfo');
Route::rule('rmtoken','index/Auth/deltoken');

//=============清除缓存===========
Route::get('clear','index/Clear/index');

//爬取请求数据添加
Route::rule('zx_spirde','index/Spirde/zhixiao');

//=====获取redis
Route::rule('redis_get','index/Ini/index');
Route::rule('redis_set','index/Ini/set');

