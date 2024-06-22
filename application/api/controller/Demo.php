<?php

namespace app\api\controller;

use app\common\controller\Api;
use Tg\TronUtil;
use think\Queue;
use think\Cache;


/**
 * 示例接口
 */
class Demo extends Api
{

    //如果$noNeedLogin为空表示所有接口都需要登录才能请求
    //如果$noNeedRight为空表示所有接口都需要验证权限才能请求
    //如果接口已经设置无需登录,那也就无需鉴权了
    //
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['test', 'test1'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['test2'];

    /**
     * 测试方法
     *
     * @ApiTitle    (测试名称)
     * @ApiSummary  (测试描述信息)
     * @ApiMethod   (POST)
     * @ApiRoute    (/api/demo/test/id/{id}/name/{name})
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @ApiParams   (name="id", type="integer", required=true, description="会员ID")
     * @ApiParams   (name="name", type="string", required=true, description="用户名")
     * @ApiParams   (name="data", type="object", sample="{'user_id':'int','user_name':'string','profile':{'email':'string','age':'integer'}}", description="扩展数据")
     * @ApiReturnParams   (name="code", type="integer", required=true, sample="0")
     * @ApiReturnParams   (name="msg", type="string", required=true, sample="返回成功")
     * @ApiReturnParams   (name="data", type="object", sample="{'user_id':'int','user_name':'string','profile':{'email':'string','age':'integer'}}", description="扩展数据返回")
     * @ApiReturn   ({
         'code':'1',
         'msg':'返回成功'
        })
     */
    public function test()
    {
        $result = $this->blockNumber(62256282);
        $this->handResult($result);
    }

    /**
     * 无需登录的接口
     *
     */
    public function test1()
    {
        $this->success('返回成功', $result);
    }

    /**
     * 需要登录的接口
     *
     */
    public function test2()
    {
        $this->success('返回成功', ['action' => 'test2']);
    }

    /**
     * 需要登录且需要验证有相应组的权限
     *
     */
    public function test3()
    {
        $this->success('返回成功', ['action' => 'test3']);
    }
    
    
    public function blockNumber($blockByNumber=0){
        $key = "852ac24c-5ea4-401b-a8cf-00f759d3ca55";
      ###  $client = new \GuzzleHttp\Client();
        
        
        $client = new \GuzzleHttp\Client(['http_errors' => false]);

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
    
    protected function handResult($result){
        if(!isset($result['block_header'])){
            echo "------------接口异常-------------\n";
            echo json_encode($result);
            echo "---------------------------------\n";
            return false;
        }
        if($result && $result['block_header'] && isset($result['block_header']['raw_data']['number'])){
            $block_number = $result['block_header']['raw_data']['number']; //区块高度

            if( isset($result['transactions'])) {

                $transactions = $result['transactions']; //区块交易数据
                $jobData = ['block_number'=>$block_number,'blockID'=>$result['blockID'],'transactions'=>$transactions];
                $isPushed = Queue::push( 'app\common\job\GetNowBlockTest' , $jobData , 'GetNowBlockTest' );
                if( $isPushed === false ){
                    $output->info("区块" . $block_number .'写入队列失败');
                    return false;
                }
                echo "采集区块".$block_number."----".date('Y-m-d H:i:s');


            } else {
                echo "采集区块".$block_number."----".date('Y-m-d H:i:s');
            }



        }else{
            echo "采集区块".$block_number."----".date('Y-m-d H:i:s');
        }
    }
}
