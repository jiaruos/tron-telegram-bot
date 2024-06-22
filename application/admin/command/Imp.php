<?php

namespace app\admin\command;


use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Exception;



class Imp extends Command
{
    protected function configure()
    {
        $this->setName('imp')->setDescription('倒入');
    }

    protected function execute(Input $input, Output $output)
    { 
        $this->hand();
    }
    protected function hand(){
        $bot_id = 7;
        db('user_7')->chunk(500,function($users) use ($bot_id){
            foreach ($users as $user){
                
                $user_id = $user['id'];
                unset($user['id']);
                $user['bot_id'] = $bot_id;
                $new_user_id = db('user')->insertGetId($user);
                
                $list = db('tg_address_7')->where('user_id',$user_id)->select();
                if($list){
                    foreach ($list as $k => $v){
                        unset($list[$k]['id']);
                        $list[$k]['user_id'] = $new_user_id;
                        $list[$k]['bot_id'] = $bot_id;
                    }
                    db('tg_address')->insertAll($list);
                }
                
            }
        });
        echo "结束";

    }


    
}
