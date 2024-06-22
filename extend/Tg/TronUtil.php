<?php

namespace Tg;

use Web3\Contracts\Ethabi;
use Web3\Contracts\Types\Address;
use Web3\Contracts\Types\Boolean;
use Web3\Contracts\Types\Bytes;
use Web3\Contracts\Types\DynamicBytes;
use Web3\Contracts\Types\Integer;
use Web3\Contracts\Types\Str;
use Web3\Contracts\Types\Uinteger;
use IEXBase\TronAPI\Tron;
use GuzzleHttp\Client;
use Tron\Api;
use Tron\TRX;


/**
 * Http 请求类
 */
class TronUtil
{
    const URI = 'https://api.trongrid.io'; // shasta testnet
    public function decodeParameter($data){
        $Ethabi = new Ethabi([
            'address' => new Address,
            'bool' => new Boolean,
            'bytes' => new Bytes,
            'dynamicBytes' => new DynamicBytes,
            'int' => new Integer,
            'string' => new Str,
            'uint' => new Uinteger
        ]);

        $data = str_ireplace('0x', '', $data);
        $rawSignature = substr($data,0,8); //截取前面8位数
        $rawRecipient = substr($data,8,64); //获取中间的地址
        $rawAmount = substr($data,72);//获取最后的金额
        $rawRecipient = $Ethabi->decodeParameter('uint256',$rawRecipient)->toHex();
        $rawAmount = $Ethabi->decodeParameter('uint256',$rawAmount)->toString(); //解密金额
        if(substr($rawRecipient,0,2) != '41'){
            ///
            /// 位数不足左侧补0 
            /// 
            $lenRaw = strlen($rawRecipient);
            if($lenRaw < 40) {
                $rawRecipient = str_pad($rawRecipient, 40, "0", STR_PAD_LEFT);
            }
            ///
            /// 位数不足左侧补0 
            /// 
            
            $rawRecipient = '41' . $rawRecipient;
        }
        $Tron = new Tron();
        $res = $Tron->setAddress($rawRecipient);
        
        $result =  ['address'=>$Tron->getAddress()['base58'],'amount'=>$rawAmount];
        return $result;
    }

    public function getTRX()
    {
        $api = new Api(new Client(['base_uri' => self::URI]));
        $trxWallet = new TRX($api);
        return $trxWallet;
    }
}
