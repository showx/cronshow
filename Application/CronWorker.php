<?php
/**
 * Cron运行
 * Author:show
 */

namespace cronshow;

use Workerman\Lib\Timer;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Connection\TcpConnection;
use \Application\Library\Web as web;
use \Application\Library\WebView as webview;
use \Application\Library\Cron as cron;

class CronWorker extends CronBaseWorker
{
    public $name = 'CronWorker';
    public $count = 1;

    public function __construct()
    {
        parent::__construct("tcp://0.0.0.0:7788");
    }

    public function onWorkerStart()
    {
        // 定时器
        Timer::add(self::$INTERVAL, array($this, 'cron'));
    }

    /**
     * 定时运行
     */
    public function cron()
    {
        // chr(27) . "[42m".
        $Config = new Config();
        $runFile = $Config->get();
        $this->LogEchoWrite("-----------------------------------------------".date('Ymd | H:i:s',time()));
        // $this->LogEchoWrite('[alert]cron_start_time'.date('Ymd | H:i:s',time()));
        if(empty($runFile))
        {
            return '';
        }
        foreach($runFile as $command)
        {
            $command = trim($command);
            $sync = Cron::issync($command);
            // 生成lock文件
            cron::locktpl($command, $this->timeout);
            if($command)
            {
                // 这里要判断是否使用队列
                if($sync)
                {
                    // echo "同步开始\n";
                    $fp = $this->_socket = \stream_socket_client("tcp://127.0.0.1:12345", $errno, $errstr, 0);
                    if (!$fp) {
                        echo "!!!$errstr ($errno)<br />\n";
                    } else {
                        fwrite($fp, "{$command}\r\n");
                        echo fgets($fp, 1024);
                        fclose($fp);
                    }
                }else{
                    // 队列运行和同步运行
                    $task_connection = new AsyncTcpConnection('Text://127.0.0.1:12345');
                    // 发送数据
                    $task_connection->send($command);
                    // 异步获得结果
                    $task_connection->onMessage = function($task_connection, $task_result)
                    {
                        // $this->LogEchoWrite($task_result);
                        // 获得结果后记得关闭异步连接
                        $task_connection->close();
                    };
                    // 执行异步连接
                    $task_connection->connect();
                }
            }
        }
        // $this->LogEchoWrite('[alert]cron_end_time'.date('Ymd | H:i:s',time()));
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

    /**
     * 开始运行
     * @param $file
     */
    public function run()
    {
        $this->onWorkerStart = array($this, 'onWorkerStart');
        parent::run();
    }
}