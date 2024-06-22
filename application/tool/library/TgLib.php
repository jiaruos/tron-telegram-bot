<?php

namespace app\tool\library;

use app\api\model\User;
use Longman\TelegramBot\Request;
use think\Cache;
use Tron\Address;
use log\LogUtil;
use fast\Date;
use fast\Random;

class TgLib
{
    
    const MESSAGE_NEW_LINE = '
';
    const TI_XING_LISTS_LIMIT = 5;
    
    const SET_BAK_CHAT_ID = "set_bak_chat_id_";
    const SET_BAK_ADDRESS = "set_bak_address_";
    const TXLB_SET_BAK_ADDRESS_KEY = "txlb_set_bak_address_key_";

    public static function sendMessage($chat_id, $message_id, $msg){
        return Request::sendMessage([
            'chat_id'   => $chat_id,
            'text'      => $msg,
            'reply_to_message_id' => $message_id,
            'parse_mode'    =>"HTML"
        ]);
    }

    public static function start($from, $bot_id, $chat_id, $bot, $message){
        $keyboard = [];
        $list = db('tg_auto_reply')->where(['bot_id'=>$bot_id,'is_menu'=>1])->order('weight desc')->select();
        if($list){
            foreach($list as $v){
                $keyboard[] = ['text'=>$v['keyword']];
            };
        }
        
        $keyboard = array_chunk($keyboard,3);
        $User = new User();
        $find_user = $User->checkUser($from,$chat_id,$bot_id);
        if(!$find_user){
            $id = $User->createUser($from,$bot_id,$chat_id);
        }
        $replyMarkup =[
            'keyboard' =>$keyboard,
            'resize_keyboard'=>true,
            'one_time_keyboard'=>false,
        ];
        Request::sendMessage([
            'chat_id'   => $chat_id,
            'text'      => $bot['welcome_content'],
            'reply_to_message_id' => $message['message_id'],
            'reply_markup'  => $replyMarkup
        ]);
    }

    public static function duihuan($chat_id,$bot_id,$message)
    {
        $trx_num = 0;
        $usdt_num = 0;
        $fee_rate = db('tg_bot')->where('id',$bot_id)->value('fee_rate');
        $content = db('tg_auto_reply')->where('bot_id',$bot_id)->where('keyword','兑换TRX')->value('content');
        if(!$content){
            $content = "没有绑定关键词";
        }else{
            $avgPrice = file_get_contents('https://www.okx.com/api/v5/market/ticker?instId=TRX-USD-SWAP');
            $avgPrice = json_decode($avgPrice,1);
            if(!$avgPrice){
                $content = "查询汇率接口有问题";
            }else{
                //1TRX = 多少USDT
                $avgPrice = $avgPrice['data'][0]['last'];
                $avgPrice = bcadd($avgPrice,0,6);
                $fee = $avgPrice * $fee_rate * 0.01;
                $usdt_num = bcsub($avgPrice,$fee,6);
    
                //1USDT = 多少TRX
                $trx_num = 1/$avgPrice;
                $fee = $trx_num * $fee_rate * 0.01;
                $trx_num = bcsub($trx_num,$fee,6);
                $content = str_replace('{$usdt_to_trx}', $trx_num, $content);
                $content = str_replace('{$trx_to_usdt}', $usdt_num, $content);
            }
        }
        self::sendMessage($chat_id, $message['message_id'], $content);
    }
    public static function check_address($address, $chat_id, $bot_id,$message){
        $user = db('user')->field('id,tg_id,bot_id,listeners_counts')
            ->where('bot_id',$bot_id)
            ->where(['tg_id' => $chat_id])
            ->where('chat_id',$chat_id)
            ->find();
            
        $has = db('tg_address')
            ->where('address',$address)
            ->where("user_id", $user['id'])
            ->where('bot_id',$bot_id)
            ->count();
        if($has >0) {
            $res = self::sendMessage($chat_id,$message['message_id'],"地址已绑定，如有问题可联系客服");
            return false;
        }
        
        
        
            $key = $bot_id.'_'.Random::alnum(6);
            $kb = [
                [
                    
                    [
                        'text'=>'全部监听',
                        'callback_data'=>json_encode(['fun'=>"add_address",'key'=>$key,'type'=>"All"])
                    ]
                ],
                [
                    [
                        'text'=>'监听USDT',
                        'callback_data'=>json_encode(['fun'=>"add_address",'key'=>$key,'type'=>"USDT"])
                    ]
                    
                ],[
                    
                    [
                        'text'=>'监听TRX',
                        'callback_data'=>json_encode(['fun'=>"add_address",'key'=>$key,'type'=>"TRX"])
                    ]
                ],[
                    [
                        'text'=>'退出添加',
                        'callback_data'=>json_encode(['fun'=>"add_address",'key'=>$key,'type'=>"exit"])
                    ]
                ]
            ];

            $replyMarkup =[
                'inline_keyboard' =>$kb
            ];
            Cache::set($key, json_encode(['address'=>$address,'message_id'=>$message['message_id']]),86400);
            $result = Request::sendMessage([
                'chat_id'   => $chat_id,
                'text'      => "请选择你要监听的代币类型",
                // 'reply_to_message_id' => $message['message_id'],
                'reply_markup'  => $replyMarkup
            ]);
            if($result->isOk()){
                return true;
            }else{
                return false;
            }
    }
    /**
     *  选择交易类型
     */
    public static function select_direction($data, $chat_id, $bot_id,$message){
            $key = $bot_id.'_'.Random::alnum(6);
            $kb = [
                [
                    
                    [
                        'text'=>'全部',
                        'callback_data'=>json_encode(['fun'=>"select_direction",'key'=>$key,'type'=>"All"])
                    ]
                ],[
                    [
                        'text'=>'收入',
                        'callback_data'=>json_encode(['fun'=>"select_direction",'key'=>$key,'type'=>"IN"])
                    ]
                    
                ],[
                    
                    [
                        'text'=>'支出',
                        'callback_data'=>json_encode(['fun'=>"select_direction",'key'=>$key,'type'=>"OUT"])
                    ]
                ],[
                    [
                        'text'=>'返回上一步',
                        'callback_data'=>json_encode(['fun'=>"select_direction",'key'=>$key,'type'=>"back"])
                    ],[
                        'text'=>'退出',
                        'callback_data'=>json_encode(['fun'=>"select_direction",'key'=>$key,'type'=>"exit"])
                    ],
                ]
            ];

            $replyMarkup =[
                'inline_keyboard' =>$kb
            ];
            Cache::set($key, json_encode($data),86400);
            $res = Request::editMessageText([
                'chat_id'   => $chat_id,
                'message_id' => $message['message_id'],
                'text'      => "请选择你要监听的交易类型",
                'parse_mode'    =>"HTML",
                'reply_markup'  => $replyMarkup
            ]);
    }
    /**
     * 添加监听地址
     */
    public static function add_listeners($address, $chat_id, $bot_id,$message, $bak='',$ext_data=[])
    {
        ///
        /// 黑名单地址校验 - 开始
        ///
        try {
            if (!empty($address)) {
                $black = db("tg_black")->where("address", $address)->find();
                if (!empty($black)) {
                    self::sendMessage($chat_id, $message['message_id'], "不支持添加交易频繁的地址，请换个正常地址添加");
                    return false;
                }
            }
        } catch (\Exception $e) {
            //异常
        }
        ///
        /// 黑名单地址校验 - 结束
        ///
        $address = trim($address);
        if(!$address){
            self::sendMessage($chat_id,$message['message_id'],"你发送的格式不对");
            return false;
        }

        $Address = new Address($address);
        if(!$Address->isValid()){
            self::sendMessage($chat_id,$message['message_id'],"你发送的格式不对");
            return false;
        }
        
        
        ///
        ///  判断 是否群组消息
        ///
        if(TgLib::ifGroup($message,$bot_id)) {
            $user = db('user')->field('id,tg_id,bot_id,listeners_counts')
            ->where('bot_id',$bot_id)
            ->where(['tg_id' => $message['chat']['id']])
            ->where('chat_id',$chat_id)
            ->find();
        } else {
            $user = db('user')->field('id,tg_id,bot_id,listeners_counts')
            ->where('bot_id',$bot_id)
            ->where(['tg_id' => $message['from']['id']])
            ->where('chat_id',$chat_id)
            ->find();
        }
        ///$user = db('user')->field('id,tg_id,bot_id,listeners_counts')->where(['tg_id'=>$message['from']['id']])->find();
        if(!$user){
            self::sendMessage($chat_id,$message['message_id'],"找不到相关用户，请重新发送 /start 进行激活");
            return false;
        }
        
        ///
        /// 当前用户是否已绑定该地址
        ///
        $has = db('tg_address')
            ->where('address',$address)
            ->where("user_id", $user['id'])
            ->where('bot_id',$bot_id)
            ->find();
        if(!empty($has)) {
            $res = self::sendMessage($chat_id,$message['message_id'],"地址已绑定，如有问题可联系客服");
            
            return false;
        }
        
        
        ///
        /// 当前用户是否已绑定该地址
        ///
        
        $count = db('tg_address')
        ->where('bot_id',$bot_id)
        ->where('tg_id',$message['from']['id'])->count();
        $listeners_counts = config('site.free_listen_counts') + $user['listeners_counts'];
        if($count >= $listeners_counts){ //超出免费提醒次数
            self::sendMessage($chat_id,$message['message_id'],config('site.listen_tip'));
            return false;
        }
        $inc = [
            'user_id'   => $user['id'],
            'bot_id'    => $user['bot_id'],
            'tg_id' => $user['tg_id'],
            'address'   => $address,
            'createtime' => time(),
            'bak'       => $bak
        ];
        if($ext_data && isset($ext_data['type'])){
            $inc['type'] = $ext_data['type'];
        }
        if($ext_data && isset($ext_data['direction'])){
            $inc['direction'] = $ext_data['direction'];
        }

        $id = db('tg_address')->insertGetId($inc);

        if($id > 0){
            Cache::store('redis')->handler()->SADD('listens',$address);

            /*
             * @绑定成功
             */
            $kb = [
                [
                    [
                        'text'=>'设置备注:如(张三的钱包)',
                        'callback_data'=>'set_bak_btn'
                    ]
                ]
            ];

            $replyMarkup =[
                'inline_keyboard' =>$kb
            ];

            Request::sendMessage([
                'chat_id'   => $chat_id,
                'text'      => "✅添加成功，已自动为您过滤低于0.1以下的交易提醒",
                'reply_to_message_id' => $message['message_id'],
                'reply_markup'  => $replyMarkup
            ]);
            

            //@todo: 设置备注名用
            Cache::set(self::SET_BAK_ADDRESS . "{$chat_id}_{$bot_id}_{$address}", "yes");

            return true;
        }

        self::sendMessage($chat_id,$message['message_id'],"绑定失败，请重新绑定");
    }
    /**
     * 删除监听地址
     */
    public static function del_listeners($text,$chat_id,$bot_id,$message)
    {
        $user = db('user')->field('id,tg_id,bot_id,listeners_counts')
            ->where('bot_id',$bot_id)
            ->where(['tg_id' => $message['from']['id']])
            ->where('chat_id',$chat_id)
            ->find();
        $data = explode('=', $text);
        $address = $data[1]; //可监听的交易地址
        $address = trim($address);
        $find = db('tg_address')
        ->where('user_id',$user['id'])
        ->where('bot_id',$bot_id)
        ->where(['tg_id'=>$message['from']['id'],'address'=>$address])
        ->find();
        if(!$find){
            self::sendMessage($chat_id,$message['message_id'],"你没有绑定这个地址，无法删除");
            return false;
        }
        db('tg_address')->where(['id'=>$find['id']])->delete();
        
        $count = db('tg_address')->where('address', $address)->count();
        if(empty($count)) {
            Cache::store('redis')->handler()->SREM('listens',$address);
        }
        
        self::sendMessage($chat_id,$message['message_id'],"删除监听地址成功");
    }
    /**
     * 监听列表
     */
    public static function listeners($chat_id, $message){
        
        
        $list = db('tg_address')
            ->where(['tg_id'=>$message['from']['id']])
            ->order('createtime desc')
            ->select();

        if(empty($list)){
            self::sendMessage($chat_id,$message['message_id'],"你没有绑定过监听地址");
            return false;
        }

        $content = '';
        foreach ($list as $key => $value) {
            $value['address'] = "<code>".$value['address']."</code>";
            if($value['bak']){
                $value['address'] = $value['address'] .'='.$value['bak'];
            }
            $content .= $value['address'] . '
';
        }

        self::sendMessage($chat_id, $message['message_id'], $content);
        return false;

    }
    
    
   
   
   
   public function listenersWithPage($chat_id,$bot_id, $message, $page = 1)
    {
        
        LogUtil::DEBUG("进入提醒列表查询程序.............");
                    
                    
        $limit = self::TI_XING_LISTS_LIMIT;
        
        
        $tg_id = $message['chat']['id'];
        
        
        $user = db('user')->where('bot_id',$bot_id)->where(['tg_id'=>$tg_id,'chat_id'=>$chat_id])->find();
        if(!$user){
            self::sendMessage($chat_id,$message['message_id'],"请输入/start开始创建账号");
            return false;
        }
        
        $total = db('tg_address')
            ->where('user_id',$user['id'])
            ->where(['bot_id'=>$bot_id])
            ->where(['tg_id'=>$tg_id])
            ->order('createtime desc')
            ->count("id");
            
        LogUtil::DEBUG("进入提醒列表查询程序.........total={$total}....");
        
        
        if(empty($total)){
            self::sendMessage($chat_id,$message['message_id'],"你没有绑定过监听地址");
            return false;
        }

        $list = db('tg_address')
            ->where('user_id',$user['id'])
            ->where(['bot_id'=>$bot_id])
            ->where(['tg_id'=>$tg_id])
            ->order('createtime desc')
            ->limit($limit)
            ->page($page)
            ->select();

        LogUtil::DEBUG("进入提醒列表查询程序.........list=" . json_encode($list));
        
        

        $content = "<strong>已添加地址共{$total}个</strong>" . '
';

        $totalUSDT = 0;
        $totalTRX = 0;
        $listContent = "";
        $i = 1;
        $apiLib = new ApiLib(); 
        $noRow = [];
        
        foreach ($list as $key => $value) {
            $num = ($page-1) * $limit + $i;
            $title = $num;
            if(!empty($value['bak'])) {
                $title .= ". " . $value['bak'];
            }

            $balanceArr = $apiLib->getBalance($value['address']);

            $address = "<code>".$value['address']."</code>";
            $balance = "USDT余额:" . $balanceArr['usdtBalance'] . '           
' . "TRX余额:" . $balanceArr['trxBalance'];


            $listContent .= "<strong>{$title}</strong>" . '
' . $address . '
' . $balance . '
'. '
';

            ///
            /// 数字按钮
            ///
            $noRow[] = [
                'text' => $num,
                'callback_data'=>"txlb_address_{$value['address']}"
            ];
            ///
            /// 数字按钮
            ///
            
            
            $i ++;
            $totalUSDT  = $totalUSDT + $balanceArr['usdtBalance'];
            $totalUSDT  = decimalNotation($totalUSDT);
            $totalTRX   = $totalTRX + $balanceArr['trxBalance'];
            $totalTRX   = decimalNotation($totalTRX);
        }


        $foot = "本页总USDT合计  " . $totalUSDT . '
' . "本页总TRX合计  " . $totalTRX . '

' . "<strong>点击按钮可进行【设置】或【删除】</strong>" . '
';


        $content .= $listContent . $foot;
        
        
        $replyMarkup = [];
        $rowArr= [];
        $btnArr = [];
        
        if($total > $limit) {
            $lastPage = ceil($total / $limit); 
            
            if($page > 1) {
                $tmpPage = $page - 1;
                if($tmpPage < 1) {
                    $tmpPage = 1;
                }

                $rowArr[] = [
                    'text'=>'上一页',
                    'callback_data'=>"txlb_back_{$tmpPage}"
                ];
            }

            if($page < $lastPage) {
                $tmpPage = $page + 1;
                if($tmpPage > $lastPage) {
                    $tmpPage = $lastPage;
                }
                $rowArr[] = [
                    'text'=>'下一页',
                    'callback_data'=>"txlb_forward_{$tmpPage}"
                ];
            } 
        }
        
         if(!empty($rowArr)) {
            $btnArr[] = $rowArr;
        }
        if(!empty($noRow)) {
            $btnArr[] = $noRow;
        }

        if(!empty($btnArr)) {
            $replyMarkup = [
                'inline_keyboard' => $btnArr
            ];
        }
        
        
        
        LogUtil::DEBUG("进入提醒列表查询程序.........content=" . $content);
        

        if(!empty($replyMarkup)) {
            $res = Request::sendMessage([
                'chat_id' => $chat_id,
                'text' => $content,
                'reply_to_message_id' => $message['message_id'],
                'parse_mode'    =>"HTML",
                'reply_markup' => $replyMarkup
            ]);
        } else {
             $res = self::sendMessage($chat_id, $message['message_id'], $content);
        }
        
        
        return $res->getRawData();
    }
    
    
    
    public function listenersWithPageEdit($chat_id, $bot_id,$message, $page = 1, $callback_query=[])
    {
            
        LogUtil::DEBUG("listenersWithPageEdit=start");
        
        $limit = self::TI_XING_LISTS_LIMIT;
        $message_id = $message['message_id'];
        
        $tg_id = $message['chat']['id'];
        
        $user = db('user')->where('bot_id',$bot_id)->where(['tg_id'=>$tg_id,'chat_id'=>$chat_id])->find();
        if(!$user){
            self::sendMessage($chat_id,$message['message_id'],"请输入/start开始创建账号");
            return false;
        }
            
        $total = db('tg_address')
            ->where('user_id',$user['id'])
            ->where(['tg_id'=>$tg_id])
            ->where('bot_id',$bot_id)
            ->order('createtime desc')
            ->count("id");
        if(empty($total)){
            self::sendMessage($chat_id,$message['message_id'],"你没有绑定过监听地址");
            return false;
        }

        $list = db('tg_address')
            ->where('user_id',$user['id'])
            ->where(['tg_id'=>$tg_id])
            ->where('bot_id',$bot_id)
            ->order('createtime desc')
            ->limit($limit)
            ->page($page)
            ->select();

        LogUtil::DEBUG("list=" . json_encode($list));

        $content = "<strong>已添加地址共{$total}个</strong>" . '
';

        
        $totalUSDT = 0;
        $totalTRX = 0;
        $listContent = "";
        $i = 1;
        $apiLib = new ApiLib();
        $noRow = [];
        
        
        foreach ($list as $key => $value) {
            $num = ($page-1) * $limit + $i;
            $title = $num;
            if(!empty($value['bak'])) {
                $title .= ". " . $value['bak'];
            } 

            $balanceArr = $apiLib->getBalance($value['address']);

            $address = "<code>".$value['address']."</code>";
            $balance = "USDT余额:" . $balanceArr['usdtBalance'] . '           
' . "TRX余额:" . $balanceArr['trxBalance'];


            $listContent .= "<strong>{$title}</strong>" . '
' . $address . '
' . $balance . '
'. '
';

            

            ///
            /// 数字按钮
            ///
            $noRow[] = [
                'text' => $num,
                'callback_data'=>"txlb_address_{$value['address']}"
            ];
            ///
            /// 数字按钮
            ///
            
            
            $i ++;
            
            $totalUSDT = $totalUSDT + $balanceArr['usdtBalance'];
            $totalTRX = $totalTRX + $balanceArr['trxBalance'];
        }



        $foot = "本页总USDT:" . $totalUSDT . '
' . "本页总TRX:" . $totalTRX . '
' . "<strong>点击按钮可对地址进行操作</strong>" . '
';


        
        $content .= $listContent . $foot;


        $replyMarkup = [];
        $rowArr= [];
        $btnArr = [];
        
        if($total > $limit) {
            $lastPage = ceil($total / $limit);

            $rowArr= [];
            if($page > 1) {
                $tmpPage = $page - 1;
                if($tmpPage < 1) {
                    $tmpPage = 1;
                }

                $rowArr[] = [
                    'text'=>'上一页',
                    'callback_data'=>"txlb_back_{$tmpPage}"
                ];
            }

            if($page < $lastPage) {
                $tmpPage = $page + 1;
                if($tmpPage > $lastPage) {
                    $tmpPage = $lastPage;
                }
                $rowArr[] = [
                    'text'=>'下一页',
                    'callback_data'=>"txlb_forward_{$tmpPage}"
                ];
            }
            
            
            LogUtil::DEBUG("rowArr=" . json_encode($rowArr)); 
        }
        
        if(!empty($rowArr)) {
            $btnArr[] = $rowArr;
        }
        if(!empty($noRow)) {
            $btnArr[] = $noRow;
        }

        if(!empty($btnArr)) {
            $replyMarkup = [
                'inline_keyboard' => $btnArr
            ];
        }

       Request::editMessageText([
            'chat_id'   => $chat_id,
            'message_id' => $message_id,
            'text'      => $content,
            'parse_mode'    =>"HTML"
        ]);

        if(!empty($replyMarkup)) {
            Request::editMessageReplyMarkup([
                'chat_id' => $chat_id,
                'message_id' => $message_id,
                'reply_markup' => $replyMarkup
            ]);
        }
    }
    
    
    public function seeAddressDetail($chat_id, $bot_id,$message, $addressWhich)
    {
        $message_id = $message['message_id'];

        $cnt = "请对地址" . '
' . $addressWhich . '
' . "进行操作";

        Request::editMessageText([
            'chat_id'   => $chat_id,
            'message_id' => $message_id,
            'text'      => $cnt,
            'parse_mode'    =>"HTML"
        ]);

        $btnArr = [];
        $rowArr = [];
        
        $key = $bot_id.'_'.Random::alnum(6);
        Cache::set($key, json_encode(['address'=>$addressWhich,'message_id'=>$message['message_id']]),86400);
        $rb[] = [            
            'text'=>'收支统计',
            'callback_data'=>"txlb_dailyreport_{$addressWhich}"
        ];
        
        
        $btnArr[] = $rowArr;
        // $btnArr[] = $rb;

        $replyMarkup = [
            'inline_keyboard' => [
                    [
                        [
                            'text'=>'修改设置',
                            'callback_data'=>json_encode(['fun'=>"get_setting_address",'key'=>$key])
                        ]
                    ],
                    [
                        [
                            'text'=>'删除监控',
                            'callback_data'=>"txlb_dellistener_{$addressWhich}"
                        ]
                    ]

            ]
        ];

        Request::editMessageReplyMarkup([
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'reply_markup' => $replyMarkup
        ]);
    }
    
    public static function getSetting($address,$chat_id, $bot_id,$message){
        $user = db('user')->where(['bot_id'=>$bot_id,'tg_id' => $message['reply_to_message']['from']['id'],'chat_id'=>$chat_id])->find();
        $address_data = db('tg_address')->where(['user_id'=>$user['id'],'bot_id'=>$bot_id,'tg_id' => $message['reply_to_message']['from']['id'],'address'=>$address])->find();
        $in = $out = $usdt = $trx = false;
        if($address_data['type'] == 'All'){
            $trx = true;
            $usdt = true;
        }
        if($address_data['type'] == 'USDT'){
            $usdt = true;
        }
        if($address_data['type'] == 'TRX'){
            $trx = true;
        }

        if($address_data['direction'] == 'All'){
            $out = true;
            $in = true;
        }
        if($address_data['direction'] == 'OUT'){
            $out = true;
        }
        if($address_data['direction'] == 'IN'){
            $in = true;
        }
        $open = "✅";
        $close = "❌";
        $in_text = $in ? $open : $close;
        $out_text = $out ? $open : $close;
        $trx_text = $trx ? $open : $close;
        $usdt_text = $usdt ? $open : $close;

        $key = $bot_id.'_'.Random::alnum(6);
        $kb = [
                [
                    [
                        'text'=>'收入提醒' . $in_text,
                        'callback_data'=>json_encode(['fun'=>"setting_address",'key'=>$key,'type'=>"in"])
                    ],
                    [
                        'text'=>'支出提醒' . $out_text,
                        'callback_data'=>json_encode(['fun'=>"setting_address",'key'=>$key,'type'=>"out"])
                    ]
                    
                ],[
                    
                    [
                        'text'=>'TRX提醒' .$trx_text,
                        'callback_data'=>json_encode(['fun'=>"setting_address",'key'=>$key,'type'=>"trx"])
                    ],
                    [
                        'text'=>'USDT提醒' .$usdt_text,
                        'callback_data'=>json_encode(['fun'=>"setting_address",'key'=>$key,'type'=>"usdt"])
                    ]
                ],[
                    [
                        'text'=>'📝设置备注',
                        // 'callback_data'=>json_encode(['fun'=>"setting_address",'key'=>$key,'type'=>"set_bak"])
                        'callback_data' => "txlb_setbake_".$address
                    ]
                ],[
                    [
                        'text'=>'<<返回钱包列表',
                        'callback_data'=>json_encode(['fun'=>"setting_address",'key'=>$key,'type'=>"back"])
                    ]
                ]
        ];
        $replyMarkup =[
            'inline_keyboard' =>$kb
        ];
        Cache::set($key, json_encode($address_data),86400);
        $res = Request::editMessageText([
           'chat_id'   => $chat_id,
            'message_id' => $message['message_id'],
            'text'      => "正在设置地址: ".$address."

            请选择以下功能进行下一步",
            'parse_mode'    =>"HTML",
            'reply_markup'  => $replyMarkup
        ]);
    }

    public static function setSetting($address_data,$chat_id, $bot_id,$message,$type){
        $up = [];
        if($type == 'in'){ //处理交易类型：转入
            if($address_data['direction'] == 'OUT'){ //如果监听的是转出，那说明in是关闭的状态，需要开启，也就是监听全部
                $up['direction'] = "All";
            }
            if($address_data['direction'] == 'IN'){ //如果监听的是转入，那说明只监听了in，需要关闭监听
                $up['direction'] = "Close";
            }
            if($address_data['direction'] == 'Close'){ //如果全部关闭，那么只开启IN
                $up['direction'] = "IN";
            }
            if($address_data['direction'] == 'All'){ //如果全部开启，那么只开启OUT
                $up['direction'] = "OUT";
            }
        }
        if($type == 'out'){ //处理交易类型：转入
            if($address_data['direction'] == 'OUT'){ //如果监听的是转出，那说明只监听了out，需要关闭监听
                $up['direction'] = "Close";
            }
            if($address_data['direction'] == 'IN'){ //如果监听的是转入，那说明只监听了in，需要开启所有
                $up['direction'] = "All";
            }
            if($address_data['direction'] == 'Close'){ //如果全部关闭，那么只开启out
                $up['direction'] = "OUT";
            }
            if($address_data['direction'] == 'All'){ //如果全部开启，那么只开启IN
                $up['direction'] = "IN";
            }
        }
        if($type == 'trx'){ //处理交易类型：转入
            if($address_data['type'] == 'TRX'){ //如果只监听了 TRX 那么关闭全部监听
                $up['type'] = "Close";
            }
            if($address_data['type'] == 'USDT'){ //如果只监听了USDT 那么开启trx 开启全部监听
                $up['type'] = "All";
            }
            if($address_data['type'] == 'Close'){ //如果全部关闭，那么只开启TRX
                $up['type'] = "TRX";
            }
            if($address_data['type'] == 'All'){ //如果全部开启，那么只开启USDT
                $up['type'] = "USDT";
            }
        }

        if($type == 'usdt'){ //处理交易类型：转入
            if($address_data['type'] == 'TRX'){ //如果只监听了 TRX 那么全部监听
                $up['type'] = "All";
            }
            if($address_data['type'] == 'USDT'){ //如果只监听了USDT 那么全部关闭
                $up['type'] = "Close";
            }
            if($address_data['type'] == 'Close'){ //如果全部关闭，那么只开启USDT
                $up['type'] = "USDT";
            }
            if($address_data['type'] == 'All'){ //如果全部开启，那么只开启TRX
                $up['type'] = "TRX";
            }
        }

        db('tg_address')->where(['id'=>$address_data['id']])->update($up);
        self::getSetting($address_data['address'],$chat_id,$bot_id,$message);
    }
    

    public function setAddressBak($chat_id, $bot_id,$message, $addressWhich)
    {
        $message_id = $message['message_id'];

        $cnt = "请回复 备注名 (不要输入太长)". '
';

        Request::editMessageText([
            'chat_id'   => $chat_id,
            'message_id' => $message_id,
            'text'      => $cnt,
            'parse_mode'    =>"HTML"
        ]);
        
        $rowArr[] = [
            'text'=>'取消',
            'callback_data'=>"txlb_cancelsetbake_{$addressWhich}"
        ];
        $btnArr[] = $rowArr;

        $replyMarkup = [
            'inline_keyboard' => $btnArr
        ];

        Request::editMessageReplyMarkup([
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'reply_markup' => $replyMarkup
        ]);

        Cache::set(self::TXLB_SET_BAK_ADDRESS_KEY . $chat_id.'_'.$bot_id, $addressWhich);
    }
    


    public function cancelSetAddressBak($chat_id, $bot_id,$message, $bot)
    {
        $message_id = $message['message_id'];


        Request::editMessageText([
            'chat_id'   => $chat_id,
            'message_id' => $message_id,
            'text'      => $bot['welcome_content'],
            'parse_mode'    =>"HTML"
        ]);
        
        Cache::rm(self::TXLB_SET_BAK_ADDRESS_KEY . $chat_id.'_'.$bot_id);
    }


    
    /**
     * 删除监听地址
     */
    public function delTxlbAddressListeners($chat_id, $bot_id,$message, $addressWhich, $callback_query)
    {
        $message_id = $message['message_id'];

        $find = db('tg_address')->where(['tg_id'=>$chat_id,'bot_id'=>$bot_id,'address'=>$addressWhich])->find();
        if(!$find){
            self::sendMessage($chat_id,$message['message_id'],"你没有绑定这个地址，无法删除");
            return false;
        }
        db('tg_address')->where(['id'=>$find['id']])->delete();
        $count = db('tg_address')->where('address', $addressWhich)->count();
        if(empty($count)) {
            Cache::store('redis')->handler()->SREM('listens', $addressWhich);
        }

        Request::editMessageText([
            'chat_id'   => $chat_id,
            'message_id' => $message_id,
            'text'      => "{$addressWhich}监控钱包已删除",
            'parse_mode'    =>"HTML"
        ]);
    }


    /**
     * @param $address
     * @param $which today yestory (今天昨天)
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function dayReport($address, $which = "today", $chatId = 0)
    {
        //trx收入/支出 笔数 金额
        $trxTodayIn = 0;
        $trxTodayInNum = 0;
        $trxTodayOut = 0;
        $trxTodayOutNum = 0;

        //usdt收入/支出 笔数 金额
        $usdtTodayIn = 0;
        $usdtTodayInNum = 0;
        $usdtTodayOut = 0;
        $usdtTodayOutNum = 0;


        if(!empty($chatId)) {
            $todayLists = db("tg_notice")
                ->where("content", "like", "%" . $address . "%")
                ->where('chat_id', $chatId)
                ->whereTime('createtime', $which)
                ->select();
        } else {
            $todayLists = db("tg_notice")
                ->where("content", "like", "%" . $address . "%")
                ->whereTime('createtime', $which)
                ->select();
        }
        
        /*$todayLists = db("tg_notice")
            ->where("content", "like", "%" . $address . "%")
            ->whereTime('createtime', $which)
            ->select();*/

        if(!empty($todayLists)) {
            foreach ($todayLists as $key=>$value) {
                $tmp = strip_tags($value['content']);
                $tmp2 = explode("\n", $tmp);
                if(!empty($tmp2[0]) && !empty($tmp2[1]) && $tmp2[1] == $address) {
                    $tmpStr = $tmp2[0];
                    $pattern = "/\d+(?:\.\d+)?/"; //提取字符串中的数字
                    preg_match_all($pattern, $tmpStr, $matches);
                    if(empty($matches[0][0])) {
                        continue;
                    }

                    $money = $matches[0][0];

                    if(strpos($tmpStr, "USDT") !== false) {
                        if(strpos($tmpStr, "支出") !== false) {
                            $usdtTodayOut = $usdtTodayOut + $money;
                            $usdtTodayOutNum = $usdtTodayOutNum + 1;

                        }elseif(strpos($tmpStr, "收到") !== false) {
                            $usdtTodayIn = $usdtTodayIn + $money;
                            $usdtTodayInNum = $usdtTodayInNum + 1;
                        }
                    } elseif(strpos($tmpStr, "TRX") !== false) {
                        if(strpos($tmpStr, "支出") !== false) {
                            $trxTodayOut = $trxTodayOut + $money;
                            $trxTodayOutNum = $trxTodayOutNum + 1;
                        }elseif(strpos($tmpStr, "收到") !== false) {
                            $trxTodayIn = $trxTodayIn + $money;
                            $trxTodayInNum = $trxTodayInNum + 1;
                        }
                    }
                }
            }
        }

        //盈余
        $usdtTodayProfit = $usdtTodayIn - $usdtTodayOut;
        $trxTodayProfit = $trxTodayIn - $trxTodayOut;

        if($which == "today") {
            $ymd = date("Y-m-d");
        } else {
            $ymd = date("Y-m-d", Date::unixtime("day", -1, 'start'));
        }

        return [
            'ymd' => $ymd,
            'trx_in' => $trxTodayIn,
            'trx_in_num' => $trxTodayInNum,
            'trx_out' => $trxTodayOut,
            'trx_out_num' => $trxTodayOutNum,
            'trx_profit' => $trxTodayProfit,

            'usdt_in' => $usdtTodayIn,
            'usdt_in_num' => $usdtTodayInNum,
            'usdt_out' => $usdtTodayOut,
            'usdt_out_num' => $usdtTodayOutNum,
            'usdt_profit' => $usdtTodayProfit,
        ];
    }




    
    public function getAddressDayReport($chat_id, $message, $addressWhich, $callback_query)
    {
        $today = $this->dayReport($addressWhich, "today", $chat_id);
        $yesterday = $this->dayReport($addressWhich, "yesterday", $chat_id);

        $addressDetail = db('tg_address')->where(['address' => $addressWhich])->find();


         $title = "地址: ";
        if(!empty($addressDetail['bak'])) {
            $title .= "(" . $addressDetail['bak'] .")";
        }
        
        $title .= '
' . $addressWhich . '
'. '
';

         $cnt = "🗓今日 {$today['ymd']} 报表" . '
'. '
';
        $cnt .= "收入:" . '
';
        $cnt .= "TRX: {$today['trx_in']}({$today['trx_in_num']}笔)" . '
';
        $cnt .= "USDT: {$today['usdt_in']}({$today['usdt_in_num']}笔)" . '
';

        $cnt .= "支出:" . '
';
        $cnt .= "TRX: {$today['trx_out']}({$today['trx_out_num']}笔)" . '
';
        $cnt .= "USDT: {$today['usdt_out']}({$today['usdt_out_num']}笔)" . '
'. '
';


        $cnt .= "盈余:" . '
';
        $cnt .= "TRX: {$today['trx_profit']}" . '
';
        $cnt .= "USDT: {$today['usdt_profit']}" . '
'. '
';

        $cnt .= "🗓昨日 {$yesterday['ymd']} 报表" . '
'. '
';
        $cnt .= "收入:" . '
';
        $cnt .= "TRX: {$yesterday['trx_in']}({$yesterday['trx_in_num']}笔)" . '
';
        $cnt .= "USDT: {$yesterday['usdt_in']}({$yesterday['usdt_in_num']}笔)" . '
';
        $cnt .= "支出:" . '
';
        $cnt .= "TRX: {$yesterday['trx_out']}({$yesterday['trx_out_num']}笔)" . '
';
        $cnt .= "USDT: {$yesterday['usdt_out']}({$yesterday['usdt_out_num']}笔)" . '
'. '
';

        $cnt .= "盈余:" . '
';
        $cnt .= "TRX: {$yesterday['trx_profit']}" . '
';
        $cnt .= "USDT: {$yesterday['usdt_profit']}" . '
';



        $content = $title . $cnt;

        $message_id = $message['message_id'];

        Request::editMessageText([
            'chat_id'   => $chat_id,
            'message_id' => $message_id,
            'text'      => $content,
            'parse_mode'    =>"HTML"
        ]);


        $btnArr = [];
        $rowArr = [];
        $rowArr[] = [
            'text'=>'设置备注名',
            'callback_data'=>"txlb_setbake_{$addressWhich}"
        ];
        $rowArr[] = [
            'text'=>'删除监控',
            'callback_data'=>"txlb_dellistener_{$addressWhich}"
        ];
        $rb[] = [
            'text'=>'收支统计',
            'callback_data'=>"txlb_dailyreport_{$addressWhich}"
        ];

        $btnArr[] = $rowArr;
        $btnArr[] = $rb;

        $replyMarkup = [
            'inline_keyboard' => $btnArr
        ];

        Request::editMessageReplyMarkup([
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'reply_markup' => $replyMarkup
        ]);
    }




    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    public static function ifGroup($message,$bot_id)
    {
        $chat_id = $message['chat']['id'];

        $chatType = $message['chat']['type'];

        if(strpos($chatType, "group") !== false) {
            //群组
            $user = db('user')
                ->where(['tg_id'=>$chat_id])
                ->find();

            $groupName = isset($message['chat']['title']) ? $message['chat']['title'] : '';
            $time = time();
            if(empty($user)) {
                db('user')->insertGetId([
                    'tg_id'	=> $chat_id,
                    'bot_id'	=> $bot_id,
                    'username'	=> $groupName,
                    'nickname'	=> $groupName,
                    'chat_id'	=> $chat_id,
                    'jointime'	=> $time,
                    'createtime' => $time,
                    'updatetime' => $time
                ]);
            }

            return true;
        }

        return false;
    }
}
