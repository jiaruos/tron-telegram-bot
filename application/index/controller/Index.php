<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use Tg\TronUtil;
use Web3\Contracts\Ethabi;
use Web3\Contracts\Types\Address;
use Web3\Contracts\Types\Boolean;
use Web3\Contracts\Types\Bytes;
use Web3\Contracts\Types\DynamicBytes;
use Web3\Contracts\Types\Integer;
use Web3\Contracts\Types\Str;
use Web3\Contracts\Types\Uinteger;
use Web3\Formatters\HexFormatter;

class Index extends Frontend
{

    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $layout = '';

    public function index()
    {
        return $this->view->fetch();
    }


    public function test()
    { 
        $tron = new \IEXBase\TronAPI\Tron();
        $Tron = new \IEXBase\TronAPI\Tron();
        $TronUtil = new TronUtil(); 
        $Ethabi = new Ethabi([
            'address' => new Address,
            'bool' => new Boolean,
            'bytes' => new Bytes,
            'dynamicBytes' => new DynamicBytes,
            'int' => new Integer,
            'string' => new Str,
            'uint' => new Uinteger
        ]);

        $rawRecipient = "00000000000000000000000000820c5777f90cb4cbcc11772493103630aa9001";
        $rawRecipient = $Ethabi->decodeParameter('uint256',$rawRecipient)->toHex();

        myecho($rawRecipient);
        
        $lenRaw = strlen($rawRecipient);
        
         $var=str_pad($rawRecipient, 39, "0", STR_PAD_LEFT);
        
        myecho($var); 
        
        myecho($lenRaw);
        
        $res = $Tron->setAddress("4100" . $rawRecipient);
        $result =  ['address'=>$Tron->getAddress()['base58']];
        

        myecho($result);
        die;
 




        $key = "e0190fb3-022c-4570-9980-cd3c7badc36f";
        $client = new \GuzzleHttp\Client();

        $url = "https://api.trongrid.io/wallet/getblockbynum";

        $blockByNumber = 56127680;

        $response = $client->request('POST', $url, [
            'headers' => [
                'TRON-PRO-API-KEY' => $key
            ],
            'json' => [
                "num" => $blockByNumber
            ]
        ]);


        $body = $response->getBody();
        $res_total = (string)$body;


        $res = json_decode($res_total, true);

        myecho($res);


        $addressRaw = "41a614f803b6fd780986a42c78ec9c7f77e6ded13c";
        $res = $tron->hexString2Address($addressRaw);
        myecho($res);


        $addressRaw2 = "41be3b192ca425eb1c200ef20d838aed1d65095e34";


        myecho("success");
    }
}
