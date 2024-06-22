<?php

namespace app\tool\library;



use GuzzleHttp\Exception\GuzzleException;

class ApiLib
{
    const TRONSCAN_KEY = "56a1fa2d-515e-4590-8c60-8d3804b3a544";

    //TRC20钱包地址是 TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t
    const TRC20_ADDRESS = "TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t";


    /*
     * 获取账号详情 - 根据钱包地址, 例如: 钱包TDF1qJjhrXX9bos9CGZBmBF2RRWvUPpT51
     *
     * 老版本地址: "https://apilist.tronscan.org/api/account?address={$address}";
     */
    public function getAccountDetail($address)
    {
        try {
            $client = new \GuzzleHttp\Client(['http_errors' => false]);
            $url = "https://apilist.tronscanapi.com/api/accountv2?address={$address}";
            $response = $client->request('GET', $url, [
                'headers' => [
                    'TRON-PRO-API-KEY' => self::TRONSCAN_KEY
                ],
            ]);

            if ($response->getStatusCode() != 200) {
                return false;
            }

            $body = $response->getBody();
            if (empty($body)) {
                return false;
            }

            return json_decode((string)$body, true);
        } catch (\Exception $e) {
            return false;
        }
    }




    //获取钱包余额
    public function getBalance($address)
    {
        $res = $this->getAccountDetail($address);

        $usdtBalance = 0;
        $trxBalance = 0;

        if(!empty($res)) {
            if(!empty($res['withPriceTokens'])) {
                $withPriceTokens = $res['withPriceTokens'];
                foreach ($withPriceTokens as $key=>$value) {
                    if($value['tokenId'] == self::TRC20_ADDRESS) {
                        if(!empty($value['balance'])) {
                            $usdtBalance = $value['balance'] * 0.000001;
                        }
                    }
                }

                $trxBalance = $withPriceTokens[0]['amount'];
                if(empty($trxBalance)) {
                    $trxBalance = 0;
                }
                if(!empty($withPriceTokens['totalFrozen'])) {
                    $trxBalance = $trxBalance + $withPriceTokens['totalFrozen'] * 0.000001;
                }
            }
        }
        $usdtBalance = decimalNotation($usdtBalance);
        if($usdtBalance == 0.000001) {
            $usdtBalance = number_format($usdtBalance, 6, ".", "");
        }
        
        if($trxBalance == 0.000001) {
            $trxBalance = number_format($trxBalance, 6, ".", "");
        }
        
        return [
            'usdtBalance' => $usdtBalance,
            'trxBalance'  => $trxBalance,
        ];
    }
}


