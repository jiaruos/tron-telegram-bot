<?php

namespace app\api\model;

use think\Db;
use think\Model;

/**
 * 会员模型
 */
class User extends Model
{
	public function checkUser($from,$chat_id,$bot_id){
		$user = $this->where(['tg_id'=>$from['id'],'chat_id'=>$chat_id,'bot_id'=>$bot_id])->find();
		
		return $user;
	}
	public function createUser($from,$bot_id,$chat_id){
		$time = time();
		return $this->insertGetId([
			'tg_id'	=> $from['id'],
			'bot_id'	=> $bot_id,
			'username'	=> isset($from['username']) ? $from['username'] : '',
			'nickname'	=> isset($from['first_name']) ? $from['first_name'] : '',
			'chat_id'	=> $chat_id,
			'jointime'	=> $time,
			'createtime' => $time,
			'updatetime' => $time
		]);
	}
}
