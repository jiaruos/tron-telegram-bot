<?php
namespace log;


/**
 * 自定义日志
 */
class LogUtil
{
    public $log;

    public function __construct()
    {

    }

    public static function Init($level, $channel = "common")
    {
        $logFile = __DIR__ . "data" . DS . $channel . DS . date('Y-m-d') .  "_" . date('H') . '.log';
        $path = dirname($logFile);
        !is_dir($path) && mkdir($path, 0777, true);
        @chmod($path, 0777);
        $logHandler = new CLogFileHandler($logFile);

        $res = Log::Init($logHandler, $level, $channel);
        return $res;
    }


    public static function DEBUG($msg)
    {
        $debugInfo = debug_backtrace(0,1);
        $stack = "[";
        $last_location = strrpos($debugInfo[0]["file"],DIRECTORY_SEPARATOR);
        $file_name=substr($debugInfo[0]["file"],$last_location+1);
        $stack .= $file_name." ".$debugInfo[0]["line"];
        $stack .= "] ";

        Log::DEBUG($stack . $msg);
    }

    public static function INFO($msg)
    {
        $debugInfo = debug_backtrace(0,1);
        $stack = "[";
        $last_location = strrpos($debugInfo[0]["file"],DIRECTORY_SEPARATOR);
        $file_name=substr($debugInfo[0]["file"],$last_location+1);
        $stack .= $file_name." ".$debugInfo[0]["line"];
        $stack .= "] ";
        Log::INFO($stack . $msg);
    }

    public static function WARN($msg)
    {
        $debugInfo = debug_backtrace(0,1);
        $stack = "[";
        $last_location = strrpos($debugInfo[0]["file"],DIRECTORY_SEPARATOR);
        $file_name=substr($debugInfo[0]["file"],$last_location+1);
        $stack .= $file_name." ".$debugInfo[0]["line"];
        $stack .= "] ";

        Log::WARN($stack . $msg);
    }

    public static function ERROR($msg)
    {
        $debugInfo = debug_backtrace(0,1);
        $stack = "[";
        foreach($debugInfo as $key => $val){
            if(array_key_exists("file", $val)){
                $stack .= ",file:" . $val["file"];
            }
            if(array_key_exists("line", $val)){
                $stack .= ",line:" . $val["line"];
            }
            if(array_key_exists("function", $val)){
                $stack .= ",function:" . $val["function"];
            }
        }
        $stack .= "] ";
        Log::ERROR($stack . $msg);
    }

}

