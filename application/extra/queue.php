<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------

use think\Env;

return [
    //Redis驱动
    'connector' => 'redis',
    "expire"=>60,//任务过期时间默认为秒，禁用为null
    "default"=>"default",//默认队列名称
    "host"=>Env::get("redis.hostname", "127.0.0.1"),//Redis主机IP地址
    "port"=>Env::get("redis.port", 6379),//Redis端口
    "password"=>Env::get("redis.password", ""),//Redis密码
    "select"=>2,//Redis数据库索引
    "timeout"=>0,//Redis连接超时时间
    "persistent"=>false,//是否长连接
];