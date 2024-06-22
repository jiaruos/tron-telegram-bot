<?php
namespace log;

class Log
{
    private static $instance = null;
    private static $instances = array();
    private $handler = null;
    private $level = 15;

    private function __construct()
    {
    }

    public static function Init($handler = null, $level = 15, $channel = "common")
    {
        if(empty($channel)) {
            $channel = "other";
        }

        if (!isset(self::$instances[$channel])) {
            self::$instances[$channel] = new self();
            self::$instances[$channel]->__setHandle($handler);
            self::$instances[$channel]->__setLevel($level);
        }

        /*if(!self::$instance instanceof self)
        {
            self::$instance = new self();
            self::$instance->__setHandle($handler);
            self::$instance->__setLevel($level);
        }*/

        self::$instance = self::$instances[$channel];
        return self::$instance;
    }

    public static function DEBUG($msg)
    {
        self::$instance->write(1, $msg);
    }

    public static function INFO($msg)
    {
        self::$instance->write(2, $msg);
    }

    public static function WARN($msg)
    {
        self::$instance->write(4, $msg);
    }

    public static function ERROR($msg)
    {
        self::$instance->write(8, $msg);
    }


    protected function write($level, $msg)
    {
        if (($level & $this->level) == $level) {
            $msg = '[' . date('Y-m-d H:i:s') . '][' . $this->getLevelStr($level) . ']' . $msg . "\n";
            $this->handler->write($msg);
        }
    }

    private function getLevelStr($level)
    {
        switch ($level) {
            case 1:
                return 'debug';
                break;
            case 2:
                return 'info';
                break;
            case 4:
                return 'warn';
                break;
            case 8:
                return 'error';
                break;
            default:

        }
    }

    private function __clone()
    {
    }

    private function __setHandle($handler)
    {
        $this->handler = $handler;
    }

    private function __setLevel($level)
    {
        $this->level = $level;
    }
}
