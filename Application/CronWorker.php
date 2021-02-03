<?php
/**
 * Cron运行
 * Author:show
 */

namespace Application;

use Workerman\Lib\Timer;
use Workerman\Connection\AsyncTcpConnection;

class CronWorker extends CronBaseWorker
{
    public $name = 'CronWorker';
    public $count = 1;
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
        $this->LogEchoWrite("-----------------------------------------------");
        $this->LogEchoWrite('[alert]cron_start_time'.date('Ymd | H:i:s',time()));
        foreach($runFile as $command)
        {
            $command_before = $command;
	        $command = addslashes($command);
            $contents = "<?php \$tmp=exec('{$command}');echo \$tmp;?>";
            $filename = md5($command);
            $pid_file = $this->Lock_Dir.'/'.$filename.".php";
            clearstatcache();
            // 仅当pid文件不存在的时候重新写
            // 根据命令生成log
            if(!file_exists($pid_file))
            {
                // 没有文件先生成文件先
                $this->LogEchoWrite('[info]【'.$command_before.'】-->start');
                file_put_contents($pid_file, $contents);
                // 这里会阻塞一下, 避免阻塞的处理 , 这里变成异步之后，要判断有没删除文件
                // $tmp = exec("php $pid_file >/dev/null  &",$output);
            }
        }
        $task_connection = new AsyncTcpConnection('Text://127.0.0.1:12345');
        // 任务及参数数据
        $task_data = array(
            'runfile' => $runFile,
            'time' => time(),
        );
        // 发送数据
        $task_connection->send(json_encode($task_data));
        // 异步获得结果
        $task_connection->onMessage = function($task_connection, $task_result)
        {
            $this->LogEchoWrite($task_result);
            // 获得结果后记得关闭异步连接
            $task_connection->close();
        };
        // 执行异步连接
        $task_connection->connect();
        $this->LogEchoWrite('[alert]cron_end_time'.date('Ymd | H:i:s',time()));
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