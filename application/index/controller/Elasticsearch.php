<?php
/**
 * Created by PhpStorm.
 * User: rwcym
 * Date: 2018/9/11
 * Time: 16:31
 */

namespace app\index\controller;

use think\Controller;
use think\Db;

class Elasticsearch extends Controller
{
    public $es;

    public function _initialize()
    {
        //host数组可配置多个节点
        $params = array(
            '39.106.20.103:9200'
        );
        $this->es = \Elasticsearch\ClientBuilder::create()->setHosts($params)->build();
    }

    //创建index
    public function create()
    {
        $params = [
            'index' => 'program', //索引名称
            'body' => [
                'settings' => [ //配置
                    'number_of_shards' => 3,//主分片数
                    'number_of_replicas' => 1 //主分片的副本数
                ],
            ]
        ];

        $this->es->indices()->create($params);
    }

    //插入数据
    public function insert()
    {
        $arr = Db::table("small_program")->limit(50)->select();
        $count = count($arr);
        $params = ['body' => []];
        foreach ($arr as $k => $v) {
            //print_r($v);die;
            $params['body'][] = [
                'index' => [
                    '_index' => 'program',
                    '_type' => "program_index",
                    '_id' => $k
                ]
            ];
            $params ['body'][] = $v;
//            if ($k % 1000 == 0) {
                $responses = $this->es->bulk($params);
//                $params = ['body' => []];
//                unset($responses);
//            }
        }
//        for ($i = 1; $i < $count; $i++) {
//            $params['body'][] = [
//                'index' => [
//                    '_index' => 'program',
//                    '_type' => "program_index",
//                    '_id' => $i
//                ]
//            ];
//            $params ['body'][] = [
//                'id' => $arr[$i]['id'],//id
//                'program_style' => $arr[$i]['program_style'],//类型
//                'title' => $arr[$i]['program_title'],//标题
//                'program_category_id' => $arr[$i]['program_category_id'],//类型
//                'program_audit_status' => $arr[$i]["program_audit_status"],//是否审核通过
//                'program_subtitle' => $arr[$i]['program_subtitle'],//小标题
//                'program_icon' => $arr[$i]['program_icon'],//图标链接
//                'program_see_num' => $arr[$i]['program_see_num'],//图标链接
//                'release_time' => $arr[$i]['release_time'],//图标链接
//            ];
//            if ($i % 1000 == 0) {
//                $responses = $this->es->bulk($params);
//                $params = ['body' => []];
//                unset($responses);
//            }
//        }
//        if (!empty($params['body'])) {
//            $this->es->bulk($params);
//        }
    }

    //查询数据
    public function select($program_style, $cate, $page_size, $page)
    {
        $params = [
            'index' => 'program',
            'type' => 'program_index',
            'body' => [
                'query' => [
                    "bool" => [
                        'filter' => [
                            ["term" => ['program_style' => $program_style]],
                            ["term" => ['program_audit_status' => "2"]],
                            ["term" => ['program_category_id' => $cate]]
                        ]
                    ]
                ]
            ],
            "size" => $page_size,
            "from" => $page,
            'sort' => [
                'release_time' => ["order"=>'desc']
            ]
        ];
        $res = $this->es->search($params)['hits'];
        $lists = array_column($res['hits'], '_source');
//        print_r($lists);
//        die;
        return $lists;
    }

    //删除index
    public function delete()
    {
        $params = [
            'index' => 'program'
        ];
        $this->es->indices()->delete($params);
    }
}