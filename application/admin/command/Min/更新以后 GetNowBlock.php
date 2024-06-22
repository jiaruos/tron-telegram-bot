<?php

namespace app\admin\command;


use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Exception;
use Tg\TronUtil;
use think\Queue;
use think\Cache;



class GetNowBlock extends Command
{
    protected $jobHandlerClassName  = 'app\common\job\GetNowBlock';
    protected $jobQueueName  = "GetNowBlock";
    protected function configure()
    {
        $this->setName('getnowblock')->setDescription('查询当前最新区块');
    }

    protected function execute(Input $input, Output $output)
    {
        $TronUtil = new TronUtil();
        $Tron = $TronUtil->getTRX();
        while(true){
            $this->hand($Tron,$output);
            sleep(3);
        }
    }
    protected function hand($Tron,$output){
        //test
        // $result = $Tron->blockByNumber(	44853597);
        // file_put_contents('block.txt',json_encode($result));
        // $this->handResult($result,$output);die;

        $get_last_number = cache('get_last_number'); //上次获取的区块
        if($get_last_number){
            $result = $this->blockNumber();//获取最新的区块
            $new_block_number = $result['block_header']['raw_data']['number']; //最新的高度
            if($get_last_number < $new_block_number){ //如果当前区块小于最新
                $i=0;
                for($get_last_number; $get_last_number < $new_block_number; $i++){
                    $get_last_number++;
                    $result = $this->blockNumber($get_last_number);
                    $this->handResult($result,$output);
                }
            }
        }else{
            $result = $this->blockNumber(); //获取最新的区块
            $this->handResult($result,$output);
        }



        return true;
    }
    protected function handResult($result,$output){
        if($result && $result['block_header'] && isset($result['block_header']['raw_data']['number'])){
            $block_number = $result['block_header']['raw_data']['number']; //区块高度
            $transactions = $result['transactions']; //区块交易数据

            cache('get_last_number',$block_number,10);

            $jobData = ['block_number'=>$block_number,'blockID'=>$result['blockID'],'transactions'=>$transactions];
            $isPushed = Queue::push( $this->jobHandlerClassName , $jobData , $this->jobQueueName );
            if( $isPushed === false ){
                $output->info("区块" . $block_number .'写入队列失败');
                return false;
            }
            $output->info("采集区块".$block_number."----".date('Y-m-d H:i:s'));
        }else{
            $output->info("采集区块失败-----".date('Y-m-d H:i:s'));
        }
    }
    public function blockNumber($blockByNumber=0){
        $key = "674a8821-b97d-45e6-9bc9-1904061490a0";
        $client = new \GuzzleHttp\Client();

        if($blockByNumber > 0) {
            $url = "https://api.trongrid.io/wallet/getblockbynum";
            $response = $client->request('POST', $url, [
                'headers' => [
                    'TRON-PRO-API-KEY' => $key
                ],
                'json' => [
                    "num" => $blockByNumber
                ]
            ]);

        } else {
            $url = "https://api.trongrid.io/wallet/getnowblock";
            $response = $client->request('POST', $url, [
                'headers' => [
                    'TRON-PRO-API-KEY' => $key
                ],
            ]);
        }


        $body = $response->getBody();
        $res_total = (string)$body;

        $res = json_decode($res_total, true);
        return $res;
    }

}
