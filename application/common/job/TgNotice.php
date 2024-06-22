<?php

namespace app\common\job;

use think\queue\Job;
use Tg\TronUtil;
use think\Cache;
use Longman\TelegramBot\Request;
use Tg\TgBot;
/**
 * 消息队列处理类
 * php think queue:work --queue TgNotice --daemon
 */
class TgNotice
{
	public function fire(Job $job,$data) {
        if ($job->attempts() > 3) {
            $job->delete();
            return false;
        }
        $job->delete();
        $this->sendMessage($data);
        echo $data['txID'] . "通知完毕 ".date('Y-m-d H:i:s') .' -- ';
        
    }

    protected function sendMessage($data){
    	$TgBot = new TgBot($data['bot_id']);
    	$res = Request::sendMessage([
			'chat_id'	=> $data['chat_id'],
			'text'		=> $data['content'],
			'disable_web_page_preview'=>true,
			'parse_mode' => "HTML"
		]); 
		
		$return = $res->getRawData();
        if(isset($return['error_code'])) {
            echo $data['txID'] . '---' .$data['tg_id'] ."通知失败，原因：".$return['description'].date('Y-m-d H:i:s') .' -- ';
            if(strpos($return['description'], "bot was blocked") !== false || strpos($return['description'], "user is deactivated") !== false) {
                $user = db("user")->where("chat_id", $data['chat_id'])->find();

                $addressArr = db('tg_address')->where("tg_id", $user['tg_id'])->where('id',$user['id'])->select();
                if(!empty($addressArr)) {
                    foreach ($addressArr as $key=>$value) {
                        $count = db('tg_address')->where('address', $value['address'])->count();
                        if(empty($count)) {
                            Cache::store('redis')->handler()->SREM('listens', $value['address']);
                        }
                    }
                }

                //删除用户及地址
                db('tg_address')->where('bot_id',$data['bot_id'])->where("user_id", $user['id'])->delete();
                db('tg_notice')->where('bot_id',$data['bot_id'])->where("user_id", $user['id'])->delete();
                db('user')->where('bot_id',$data['bot_id'])->where("id", $user['id'])->delete();
            }
        }
        
		if($res->ok == true){
			//存入消息通知
			db('tg_notice')->insert([
				'user_id'	=> $data['user_id'],
				'tg_id'		=> $data['tg_id'],
				'bot_id'    => $data['bot_id'],
				'chat_id'	=> $data['chat_id'],
				'content'	=> $data['content'],
				'createtime'=> time(),
				'type'		=> $data['status']
			]);
            echo $data['txID'] . '---' .$data['tg_id'] ."通知成功 ".date('Y-m-d H:i:s') .' -- ';
		}
    }
}
