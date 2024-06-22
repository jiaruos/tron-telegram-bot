<?php

namespace app\common\job;

use think\queue\Job;
use Tg\TronUtil;
use think\Cache;
use think\Queue;

/**
 * 消息队列处理类
 */
class GetNowBlock
{
    public function fire(Job $job,$data) {
        if ($job->attempts() > 3) {
            $job->delete();
            return false;
        }
        $transactions = $data['transactions']; //获取交易数据
        $blockID = $data['blockID'];
        if(!$transactions){
            return false;
        }
        $TronUtil = new TronUtil();
        $tron = new \IEXBase\TronAPI\Tron();

        foreach($transactions as $k => $v){
            $raw_data = $transactions_detail = null;
            if($v && isset($v['ret']) && isset($v['ret'][0]['contractRet']) && $v['ret'][0]['contractRet'] == 'SUCCESS'){
                //交易成功
                //拿出交易详情数据
                $_transactions_detail = $transactions_detail = $v['raw_data']['contract'][0]['parameter']['value'];
                if(isset($transactions_detail['asset_name'])){ //不是trx 或者usdt的 直接跳过
                    continue;
                }

                if(isset($v['raw_data']['timestamp'])) {
                    $transactions_detail['time'] = date("Y-m-d H:i:s", $v['raw_data']['timestamp']/1000);
                } else {
                    $transactions_detail['time'] = date("Y-m-d H:i:s", time());
                }

                if(isset($transactions_detail['data'])){
                    //如果是合约交易 并且不是usdt 直接跳过
                    if($transactions_detail['contract_address'] != '41a614f803b6fd780986a42c78ec9c7f77e6ded13c'){
                        continue;
                    }
                    if(substr($transactions_detail['data'],0,8) != 'a9059cbb'){
                        continue;
                    }
                    //只判断是usdt的
                    $raw_data = $TronUtil->decodeParameter($transactions_detail['data']);
                    $transactions_detail['amount'] = $raw_data['amount']; //收到的金额
                    $transactions_detail['to_address'] = $raw_data['address']; //收到的账户
                    $transactions_detail['coin'] = ' USDT';

                }else{
                    $transactions_detail['coin'] = ' TRX';
                    if(isset($transactions_detail['to_address'])){
                         $transactions_detail['to_address'] = $tron->hexString2Address(str_replace("0x","41",$transactions_detail['to_address']));
                    }
                }

                $transactions_detail['txID'] = $v['txID'];


                if(!isset($transactions_detail['amount']) || !isset($transactions_detail['to_address'])){
                    continue;
                }
                if($transactions_detail['amount'] < 100000){
                    continue;
                }
                $transactions_detail['amount'] = $transactions_detail['amount'] * 0.000001;
                $transactions_detail['conversion_amount'] = sprintf("%01.6f",floatval($transactions_detail['amount'])); //换算后的金额
                if($transactions_detail['conversion_amount'] < 0.1){
                    continue;
                }
                $transactions_detail['conversion_amount'] = floatval($transactions_detail['conversion_amount']);
                if(substr($transactions_detail['owner_address'],0,2) != '41'){
                    $transactions_detail['owner_address'] = '41' . $transactions_detail['owner_address'];
                }
                $transactions_detail['owner_address'] = $tron->hexString2Address($transactions_detail['owner_address']);
                
                //$transactions_detail['to_address']收到记录
                $this->filter($transactions_detail['to_address'],$transactions_detail['conversion_amount'],$transactions_detail['coin'],1,$blockID,$transactions_detail);//收到
                
                //$transactions_detail['owner_address']支出记录
                $this->filter($transactions_detail['owner_address'],$transactions_detail['conversion_amount'],$transactions_detail['coin'],2,$blockID,$transactions_detail);
            }

        }
        echo "区块".$data['block_number'] . "处理完毕 ".date('Y-m-d H:i:s') .' -- ';
        $job->delete();
    }

    /**
     * 查找key有没有监听
     */
    protected function filter($address,$amount,$coin,$type,$block_hash,$transactions_detail){
        //检测是有监听这个地址
        $is_exist = Cache::store('redis')->handler()->SISMEMBER('listens',$address);
        if(!$is_exist){
            return false;
        }
        $black = db("tg_black")->where("address", $address)->find();
        //如果这个地址被拉黑，则不通知
        if ($black) {
            // Cache::store('redis')->handler()->SREM('listens',$address);
            echo $transactions_detail['txID'].'-----'.$address."---地址被拉黑！\n";
            return false;
        }
        //查库这个地址有没有被监听
        $find = db('tg_address')->where(['address'=>$address])->find();
        if(!$find){
            Cache::store('redis')->handler()->SREM('listens',$address);
            echo $transactions_detail['txID'].'-----'.$address."---地址没有被监听！\n";
            return false;
        }
        echo $transactions_detail['txID']."---查询监听条件！\n";
        $_type = $type;
        $whereSql1 = '';
        $whereSql2 = '';
        if($_type == 1){
            $whereSql1 = '(direction = "IN" or direction = "All")';
        }elseif($_type == 2){
            $whereSql1 = '(direction = "OUT" or direction = "All")';
        }
        
        if($transactions_detail['coin'] == "USDT" || ' USDT' == $transactions_detail['coin']){
            $whereSql2 = ' (type = "USDT" or type = "All")';
        }elseif($transactions_detail['coin'] == "TRX" || ' TRX' == $transactions_detail['coin']){
            $whereSql2 = ' (type = "TRX" or type = "All")';
        }
        //查询所有与这个地址有关系的
        $allAddress = db('tg_address')
            ->where(['address'=>$address])
            ->where($whereSql1)
            ->where($whereSql2)
            ->select();
        if($type == 1){
            $type = '💰💰收到';
        }elseif($type == 2){
            $type = '🔴🔴支出';
        }
        echo $transactions_detail['txID']."---开始发送！\n";
        foreach ($allAddress as $kk=>$vv) {
            $find = $vv;
            $user = db('user')->alias('u')->field('u.id,u.tg_id,u.bot_id,u.chat_id,u.notice_count')
            ->join("__TG_ADDRESS__ a",'a.user_id = u.id')
            ->where('a.user_id',$vv['user_id'])
            ->where(['u.tg_id'=>$find['tg_id'],'u.bot_id'=>$find['bot_id']])
            ->where('a.address',$address)
            ->find();
            if(!$user){
                echo $transactions_detail['txID']."---找不到这个用户！\n";
                continue; //return false;
            }
            //
            $search_url = db('tg_bot')->where('id',$find['bot_id'])->value('search_url'); //官网地址
            //看看今日通知次数是否达到
            $notice_count = 1; // db('tg_notice')->where(['user_id'=>$user['id']])->whereTime('createtime','d')->count('id');//已经通知次数
            $sys_notice_count = 2; //$user['notice_count'] + config('site.notice_counts'); //系统通知次数

            $jobData = [
                'user_id'   => $user['id'],
                'bot_id'    => $user['bot_id'],
                'tg_id'     => $user['tg_id'],
                'chat_id'   => $user['chat_id'],
                'address'   => $address,
                'date'      => date('Y-m-d H:i:s'),
                'txID'      => $transactions_detail['txID']
            ];
            $bak = '';
            if($find['bak']){
                $bak = "【".$find['bak']."】";
            }
            $error_notcie = 0;
            $txID = $transactions_detail['txID'];
            if($address == $transactions_detail['owner_address']){
                $toAddress = $transactions_detail['to_address'];
            }else{
                $toAddress = $transactions_detail['owner_address'];
            }

            if($notice_count < $sys_notice_count){
                // $jobData['content'] =
                // '<a href="'.config('site.search_url').$address.'">'.$type."：<b>".$amount."</b>".$coin.'  '.$bak.$address."\n区块哈希".$block_hash."\n点击查看交易详情或者账户余额详情".'</a>';
                $search_url = $search_url .$address;
                if(strpos($type, "支出") !== false) {
                    $jobData['content'] = $type ."：-{$amount}{$coin}  {$bak} 
<code>{$address}</code>

交易对象：<code>{$toAddress}</code> 
⏰交易时间：<code>{$transactions_detail['time']}</code>
<a href='https://tronscan.org/#/transaction/".$txID."'></a>    <a href='{$search_url}'>点击查看交易详情或者账户余额详情</a>";

                } else {

                    $jobData['content'] = $type . "：{$amount}{$coin}  {$bak}
<code>{$address}</code>

交易对象：<code>{$toAddress}</code>
⏰交易时间：<code>{$transactions_detail['time']}</code>
<a href='https://tronscan.org/#/transaction/" . $txID . "'></a>    <a href='{$search_url}'>点击查看交易详情或者账户余额详情</a>";

                }

                $jobData['status'] = 'Success';
            }else{
                $error_notcie = db('tg_notice')->where(['user_id'=>$user['id'],'type'=>'Error'])->whereTime('createtime','d')->count('id');
                $jobData['content'] = config('site.notice_tip');
                $jobData['status'] = 'Error';
            }
            if($error_notcie < 1 || !$error_notcie){
                $isPushed = Queue::push( 'app\common\job\TgNotice' , $jobData , 'TgNotice' );
                if( $isPushed === false ){
                    echo $transactions_detail['txID']."通知写入失败！\n";
                    //return false;
                }else{
                    echo $transactions_detail['txID']."通知写入成功！\n";
                }
            }
        }

    }
}
