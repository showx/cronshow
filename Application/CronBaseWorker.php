<?php
/**
 * Cron运行
 * Author:show
 */

namespace cronshow;

use Workerman\Lib\Timer;
use Workerman\Worker;
use Workerman\Connection\AsyncTcpConnection;

class CronBaseWorker extends Worker
{
    // 版本
    const VERSION = "1.0.0";
    // 运行用户
    public $user = 'www-data';
    // 运行最少单位，没微秒的概念
    public static $INTERVAL = 1;
    public $Log_Dir = __DIR__.'/Log';
    public $Lock_Dir = __DIR__.'/Lock';
    public $Status_Dir = __DIR__.'/Status';

    public function __construct($socket_name = '', $context_option = array())
    {
        parent::__construct($socket_name, $context_option);
    }

    /**
     * 记录日志
     */
    public function LogEchoWrite($Line)
    {
        echo $Line.PHP_EOL;
        $date = date('Ymd');
        $hour = date('H');
        if(!file_exists($this->Log_Dir.'/'.$date."/"))
        {
            // www-data能查看日志
            mkdir($this->Log_Dir.'/'.$date."/",0755);
        }
        file_put_contents($this->Log_Dir.'/'.$date."/".$hour.'.log',$Line."\r\n",FILE_APPEND|LOCK_EX);
    }

}