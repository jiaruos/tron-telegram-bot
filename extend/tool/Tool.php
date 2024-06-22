<?php
namespace tool;

use fast\Date;
use think\exception\HttpResponseException;
use think\Response;

class Tool
{
    public static function getMillisecond13Bit() {
        list($t1, $t2) = explode(' ', microtime());
        $microtime = (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
        return $microtime;
    }


    /**
     * 返回封装后的 API 数据到客户端
     * @access protected
     * @param mixed  $msg    提示信息
     * @param mixed  $data   要返回的数据
     * @param int    $code   错误码，默认为0
     * @param string $type   输出类型，支持json/xml/jsonp
     * @param array  $header 发送的 Header 信息
     * @return void
     * @throws HttpResponseException
     */
    public static function jsonReturn($data = null, $type = null, array $header = [])
    {
        // 如果未设置类型则自动判断
        $type = $type ? $type : (request()->param(config('var_jsonp_handler')) ? 'jsonp' : 'json');

        if (isset($header['statuscode'])) {
            $code = $header['statuscode'];
            unset($header['statuscode']);
        } else {
            $code = 200;
        }

        $response = Response::create($data, $type, $code)->header($header);
        throw new HttpResponseException($response);
    }

    public function getFileInfo($file)
    {
        require_once  EXTEND_PATH . '/getID3-master/getid3/getid3.php';
        if(file_exists($file)) {
            $getID3 = new \getID3();
            return $getID3->analyze($file);
        }

        return [];
    }

    ///
    /// 获取视频时长
    ///
    public function getVideoSeconds($file)
    {
        if(file_exists($file)) {
            $fileInfo = $this->getFileInfo($file);
            if(!empty($fileInfo['playtime_seconds'])) {
                $second = $fileInfo['playtime_seconds'];
                return floor($second);
            }

            return 0;
        }

        return false;
    }

    /**
     * @throws \ReflectionException
     */
    public static function getClassNameWithoutNamespace($className)
    {
        try {
            return (new \ReflectionClass($className))->getShortName();
        } catch (\ReflectionException $e) {
            return false;
        }
    }


    public static function create_random_number($len)
    {
        $num = [1, 2, 3, 4, 5, 6, 7, 8, 9];

        $str = "";
        for($i=0; $i<$len; $i++ ) {
            $str = $str . $num[mt_rand(0,8)];
        }

        return $str;
    }




    //月 - 第一天 00:00:00
    public static function getFirstDayOfMonth($offset = 0)
    {
        return date("Y-m-d H:i:s", Date::unixtime("month", $offset, 'begin'));
    }

    //月 - 最后一天 23:59:59
    public static function getLastDayOfMonth($offset = 0)
    {
        return date("Y-m-d H:i:s", Date::unixtime("month", $offset, 'end'));
    }

    //天 - 开始 00:00:00
    public static function getStartOfDay($offset = 0)
    {
        return date("Y-m-d H:i:s", Date::unixtime("day", $offset, 'begin'));
    }

    //天 - 结束 23:59:59
    public static function getEndOfDay($offset = 0)
    {
        return date("Y-m-d H:i:s", Date::unixtime("day", $offset, 'end'));
    }



    /**
     * 删除空白字符
     *
     * @param $str
     * @return array|string|string[]|null
     *
     * \s - 匹配任何空白字符，包括空格、制表符、换页符等等。等价于 [ \f\n\r\t\v]。
     *      \f    换页符
     *      \n    换行符
     *      \r    回车符
     *      \t    Tab字符
     *      \v    垂直制表符
     *
     * PHP 中换行可以用 PHP_EOL 来替代，以提高代码的源代码级可移植性：
     *    unix系列用 \n
     *    windows系列用 \r\n
     *    mac用 \r
     *
     */
    public static function deleteWhiteSpaceChar($str)
    {
        $str_tmp = trim($str);
        $str_tmp = str_replace(PHP_EOL, "", $str_tmp);
        return preg_replace("/\s+/", "", $str_tmp);
    }

    /**
     * 返回结果
     *
     * @param $msg
     * @param int $code
     * @param array $data
     * @return array
     */
    public static function getReturn($msg, int $code = 0, $data = [])
    {
        if (is_array($msg)) {
            if (isset($msg['code'])) {
                $code = $msg['code'];
            }
            if (isset($msg['msg'])) {
                $msg = $msg['msg'];
            }
            if (isset($msg['data'])) {
                $data = $msg['data'];
            }
        }

        return [
            'code' => $code,
            'msg' => $msg,
            'data' => $data
        ];
    }

    public static function success($msg, $data = [])
    {
        return self::getReturn($msg, 1, $data);
    }

    public static function error($msg, $data = [])
    {
        return self::getReturn($msg, 0, $data);
    }




    #拼凑image/video地址
    public static function get_full_url($target_url, $server_url = "", $canNull = true)
    {
        if(empty($target_url)) {
            if($canNull) {
                return "";
            } else {
                $target_url = "/assets/img/default.png";
            }
        }

        $regex = "/(http:\/\/)|(https:\/\/)/i";
        if (preg_match($regex, $target_url)) {
            return $target_url;
        }

        $regex = "/^((?:[a-z]+:)?\/\/|data:image\/)(.*)/i";
        if (preg_match($regex, $target_url)) {
            return $target_url;
        }

        if(!empty($server_url)) {
            return $server_url . $target_url;
        }

        return cdnurl($target_url, true);
    }





    public static function changeToPercent($num)
    {
        if (empty($num)) {
            return "-";
        }

        $num = bcmul($num, 100, 2);
        $num = floatval($num);

        return strval($num) . "%";
    }


    public static function deleteNumberEndZero($number)
    {
        return rtrim(rtrim($number, 0), '.');
    }


    public static function handleArrayItemToString($arr)
    {
        if (empty($arr)) {
            return [];
        }
        $tmp = [];
        foreach ($arr as $key => $value) {
            if (is_array($value)) {
                $tmp[$key] = self::handleArrayItemToString($value);
            } else {
                $tmp[$key] = strval($value);
            }
        }
        return $tmp;
    }



    /***
     * 手机格式校验
     *
     * @param $mobile
     * @return bool
     */
    public static function is_mobile($mobile)
    {
        $g = "/^1\d{10}$/";

        if(preg_match($g, $mobile)) {
            return true;
        }

        return false;
    }


    public static function xmlToArray($xml)
    {
        $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $array_data;
    }


    /**
     * 二位数组排序
     *
     * @param $array
     * @param $field
     * @param $sort
     * @return mixed
     */
    public static function array_sort($array, $field = "", $sort = SORT_ASC)
    {
        //取出列
        foreach ($array as $key => $row) {
            $column[$key] = $row[$field];
        }

        //按列排序
        array_multisort($column, $sort, $array);

        return $array;
    }


    public static function msubstr_local($str, $start = 0, $length = 1, $charset = "utf-8")
    {
        if (function_exists("mb_substr"))
            $slice = mb_substr($str, $start, $length, $charset);
        elseif (function_exists('iconv_substr')) {
            $slice = iconv_substr($str, $start, $length, $charset);
            if (false === $slice) {
                $slice = '';
            }
        } else {
            $re ['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
            $re ['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
            $re ['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
            $re ['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
            preg_match_all($re [$charset], $str, $match);

            $slice = join("", array_slice($match [0], $start, $length));
        }
        return $slice;
    }


    public static function desensitizationString($str, $start, $end)
    {
        $ph1 = self::msubstr_local($str, 0, $start);
        $ph2 = self::msubstr_local($str, -1, $end);
        return $ph1 . "***" . $ph2;
    }


    /*
     @Author Sea <34422611@qq.com> v1.01
     @Param string $str
     @Param int $start 起始位置 从0开始计数  负数倒转替换
     @Param (int or string) $length 当 $length=string 替换 $key
     @Param (int or string) $key 填充的隐藏的字符 默认 *
     @Param string $charset 可废弃 删除 ($key && $charset = $key) 和 ($charset='utf8') 语句;

     @Param int $split 可拓展
     $split_card = implode(' ',str_split($str_hide,4));  // 分割卡号
     */
    public static function str_hide($str, $start, $length = 0, $key = '', $charset = 'utf8')
    {
        // 使用支持补0，当 $length=string 替换 $key
        if (strlen($length) && gettype($length) != "integer") {
            $key && $charset = $key;
            $key = $length;
            $length = 0;
        }
        $Par = $length ? [$start, $length] : [$start]; //array_filter([$start,$length]);
        // use $charset;
        //$e or $e = mb_strlen($str);
        //$Par     = [$start,$length,$charset];
        $rep_str = mb_substr($str, ...$Par);
        strlen($key) or $key = '*';
        strlen($key) == 1 && $key = str_pad('', mb_strlen($rep_str), $key);
        $start = strlen(mb_substr($str, 0, $start));
        $count = strlen($rep_str);
        $result = substr_replace($str, $key, $start, $count);
        return $result;
    }


    /**
     * 从前
     * @param $posttime
     * @return string
     */
    public static function timeAgo($posttime)
    {
        //当前时间的时间戳
        $nowtimes = strtotime(date('Y-m-d H:i:s'), time());
        //之前时间参数的时间戳
        if (!is_numeric($posttime)) {
            $posttimes = strtotime($posttime);
        } else {
            $posttimes = $posttime;
        }

        //相差时间戳
        $counttime = $nowtimes - $posttimes;
        //进行时间转换
        if ($counttime <= 10) {
            return '刚刚';
        } else if ($counttime > 10 && $counttime <= 30) {
            return '刚才';
        } else if ($counttime > 30 && $counttime <= 120) {
            return '1分钟前';
        } else if ($counttime > 120 && $counttime <= 180) {
            return '2分钟前';
        } else if ($counttime > 180 && $counttime < 3600) {
            return intval(($counttime / 60)) . '分钟前';
        } else if ($counttime >= 3600 && $counttime < 3600 * 24) {
            return intval(($counttime / 3600)) . '小时前';
        } else if ($counttime >= 3600 * 24 && $counttime < 3600 * 24 * 2) {
            return '昨天';
        } else if ($counttime >= 3600 * 24 * 2 && $counttime < 3600 * 24 * 3) {
            return '前天';
        } else if ($counttime >= 3600 * 24 * 3 && $counttime <= 3600 * 24 * 20) {
            return intval(($counttime / (3600 * 24))) . '天前';
        } else {
            return $posttime;
        }
    }


    /**
     * 从前
     * @param $posttime
     * @return string
     */
    public static function timeFuture($posttime)
    {
        //当前时间的时间戳
        $nowtimes = strtotime(date('Y-m-d H:i:s'), time());
        //之前时间参数的时间戳
        if (!is_numeric($posttime)) {
            $posttimes = strtotime($posttime);
        } else {
            $posttimes = $posttime;
        }

        //相差时间戳
        $counttime = $posttimes - $nowtimes;


        if ($counttime > 0 && $counttime < 60) {
            return intval(($counttime / 3600)) . '秒';
        } elseif ($counttime >= 60 && $counttime <= 3600) {
            return intval(($counttime / 3600)) . '分钟';
        } elseif ($counttime >= 3600) {
            return intval(($counttime / 3600)) . '小时';
        }

        return date("Y-m-d H:i", $posttimes);
    }


    public static function second2DHMS($second)
    {
        $d = floor($second / (3600 * 24));
        $h = floor(($second % (3600 * 24)) / 3600);
        $m = floor((($second % (3600 * 24)) % 3600) / 60);
        $s = $second - ($d * 24 * 3600) - ($h * 3600) - ($m * 60);

        if($d > 0) $d=sprintf ( "%02d", $d);
        if($h > 0) $h=sprintf ( "%02d", $h);
        if($m > 0) $m=sprintf ( "%02d", $m);
        if($s > 0) $s=sprintf ( "%02d", $s);


        if(!empty($d)) {
            return $d . ":" . $h . ":" . $m . ":" . $s;
        }

        if(!empty($h)) {
            return  $h . ":" . $m . ":" . $s;
        }
        if(!empty($m)) {
            return  $m . ":" . $s;
        }
        if(!empty($s)) {
            return   "00:" . $s;
        }

        return 0;
    }


    // 报名入口二维码
    public static function createOrgInviteErweima($org)
    {
        $p_image = "";
        try {
            $invite_ewm = $org['erweima_image'];
            if(!empty($invite_ewm)) {
                $fileName = $org['id'] . "_" . $org['invite_code'] . ".png";
                $fileDir1 = ROOT_PATH . "public/uploads/org_invite_code";
                $file1 = $fileDir1 . "/" . $fileName;

                $fileNameInvite = $org['id'] . "_" . $org['invite_code'] . "_invite.jpg";
                $fileDir2 = ROOT_PATH . "public/uploads/org_invite_code_new";
                $file2 = $fileDir2 . "/" . $fileNameInvite;
                /*if (file_exists($file2)) {
                    return ['code'=>1, 'msg'=>'ok', 'data'=>'/uploads/org_invite_code_new/' . $fileNameInvite];
                }*/

                if(!file_exists($file1)) {
                    return ['code'=>0, 'msg'=>'二维码暂未生成', 'data'=>[]];
                }

                //二维码图片
                $ewm = imagecreatefromstring(file_get_contents($file1));
                if (!$ewm) {
                    return ['code'=>0, 'msg'=>'处理条形码图片失败001', 'data'=>[]];
                }
                $q_width = imagesx($ewm);
                $q_height = imagesy($ewm);


                //设置背景图片
                $background = imagecreatefromstring(file_get_contents('./static/bg_erweima.jpg'));
                $background_width = 600;
                $background_height = 760;

                $fonttype = ROOT_PATH . 'public/static/wqy-microhei.ttc';

                $fontcolor = imagecolorallocate($background, 0, 0, 0);
                $textcolor = imagecolorallocate($background, 0, 0, 0);
                $textcolor2 = imagecolorallocate($background, 128, 128, 128);

                $tit1 = $org['name'];
                $len1 = mb_strlen($tit1,"utf-8");
                $x1 = 300 - ($len1/2 * 25);


                imagettftext($background, 20, 0, $x1, 680, $textcolor, $fonttype, $tit1);

                $tit2 =  "推广码: " . $org['invite_code'];
                $len2 = mb_strlen($tit2,"utf-8");
                imagettftext($background, 20, 0, 205, 730, $textcolor2, $fonttype, $tit2);



                imagecopyresampled($background, $ewm, 0, 0, 0, 0, 600, 600, $q_width, $q_height);
                imagejpeg($background, $file2);

                return ['code'=>1, 'msg'=>'ok', 'data'=>'/uploads/org_invite_code_new/' . $fileNameInvite];
            }
        } catch (Exception $e) {
            return ['code'=>0, 'msg'=>$e->getMessage(), 'data'=>[]];
        }


        return ['code'=>0, 'msg'=>"fail", 'data'=>[]];
    }

    public static function createMysqlNullCondition($fieldName)
    {
        return "{$fieldName} is null";
    }

    public static function createMysqlNotNullCondition($fieldName)
    {
        return "{$fieldName} is not null";
    }




    /**
     * 身份证号码验证（真正要调用的方法）
     * @param $id_card   身份证号码
     */
    public static function validation_filter_id_card($id_card)
    {
        return true;

        $value = $id_card;

        if(empty($value)) {
            return false;
        }

        if(strlen($value) == 18) {
            $num = substr($value, 0, 17);

            if(is_numeric($num) == false) {
                return false;
            }

            $arrInt   = str_split($num);
            $arrCh    = ["1","0","X","9","8","7","6","5","4","3","2"];
            $arrCov   = [
                "7","9","10","5","8","4","2","1","6","3","7","9","10","5","8","4","2"
            ];
            $result   = 0;

            for($i = 0; $i < 17; $i++)
            {
                $result += $arrInt[$i] * $arrCov[$i];
            }

            $idx      = $result % 11;
            $lastChar = strtoupper(substr($value, 17, 1));

            if($arrCh[$idx] != $lastChar)
            {
                return false;
            }

            return true;
        } else if(strlen($value) == 15) {
            if (is_numeric($value) == false){
                return false;
            }

            $arrInt   = str_split($value);
            $year     = "19" . $arrInt[6] . $arrInt[7];
            $month    = $arrInt[8] . $arrInt[9];
            $day      = $arrInt[10] . $arrInt[11];

            if(!checkdate($month, $day, $year)){
                return false;
            } else {
                return true;
            }
        } else {
            return false;
        }
    }
}
