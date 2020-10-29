<?php
/**
 * Cron运行
 * Author:show
 */

namespace Application;

use Workerman\Lib\Timer;
use Workerman\Worker;

class CronWorker extends Worker
{
    // 版本
    const VERSION = "1.0.0";
    // 运行用户
    public $USER = '';
    // 运行最少单位，没微秒的概念
    public static $INTERVAL = 1;
    public $Log_Dir = __DIR__.'/Log';
    public $Lock_Dir = __DIR__.'/Lock';

    public function __construct($socket_name = '', $context_option = array())
    {
        parent::__construct($socket_name, $context_option);
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
        echo '[alert]cron_start_time'.date('Ymd | H:i:s',time()).PHP_EOL;
        foreach($runFile as $command)
        {
            $this->LogEchoWrite("-----------------------------------------------");
            $command_before = $command;
	        $command = addslashes($command);
            $contents = "<?php \$tmp=exec('{$command}');echo \$tmp;?>";
            $filename = md5($command);
            $pid_file = $this->Lock_Dir.'/'.$filename.".php";
            clearstatcache();
            //仅当pid文件不存在的时候重新写
            //根据命令生成log
            if(!file_exists($pid_file))
            {
                $this->LogEchoWrite('[info]【'.$command_before.'】-->start');
                file_put_contents($pid_file, $contents);
                $tmp = exec("php $pid_file");
                $this->LogEchoWrite("[info]【{$command_before}】-->cron_result:".$tmp);
                if($tmp)
                {
                    // 执行完之后删除运行文件
                    unlink($pid_file);
                }
            }else{
                // 因为还在锁定中，所以一定要写日志，有可能死锁的状态
                $this->LogEchoWrite('[warning]【'.$command_before.'】-->already running');
                continue;
            }
        }
        $this->LogEchoWrite('[alert]cron_end_time'.date('Ymd | H:i:s',time()));
    }

    /**
     * 记录日志
     */
    public function LogEchoWrite($Line)
    {
        echo $Line.PHP_EOL;
        $date = date('Ymd');
        file_put_contents($this->Log_Dir.'/'.$date.'.log',$Line."\r\n",FILE_APPEND|LOCK_EX);
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