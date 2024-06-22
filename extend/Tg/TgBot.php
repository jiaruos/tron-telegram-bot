<?php

namespace Tg;
use Longman\TelegramBot\Telegram;

/**
 * Http 请求类
 */
class TgBot
{
    public  function __construct($id){
        $this->bot = db('tg_bot')->find($id);
        if(!$this->bot){
            return false;
        }
        $Tg = new Telegram($this->bot['bot_token'],$this->bot['bot_username']);
    }
    public function getBot(){
        return $this->bot;
    }
}
