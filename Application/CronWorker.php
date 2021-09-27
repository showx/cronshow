<?php
/**
 * Cron运行
 * Author:show
 */

namespace cronshow;

use Workerman\Lib\Timer;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Connection\TcpConnection;

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
        $this->LogEchoWrite("-----------------------------------------------");
        $this->LogEchoWrite('[alert]cron_start_time'.date('Ymd | H:i:s',time()));
        $asyncCallback = [];
        if(empty($runFile))
        {
            return '';
        }
        foreach($runFile as $command)
        {
            $command_before = $command;
	        $command = addslashes($command);
            $filename = md5($command);
            $lock_file = $this->Lock_Dir.'/'.$filename.".php";
            $timeoutfile = __DIR__.'/Log/timeoutrun.txt';
            $statusfile = $this->Status_Dir.'/'.$filename.".txt";
            $contents=<<<EOF
<?php
\$runstarttime = microtime(true);
// 这里阻塞一下没问题的
\$tmp = exec("{$command}", \$output);
\$runendtime = microtime(true);
// 计算出运行时间
\$runningtime = \$runendtime - \$runstarttime;
if(\$runningtime >= {$this->timeout})
{
    file_put_contents("{$timeoutfile}","very late:{$command}\r\n",FILE_APPEND|LOCK_EX);
}
if(\$tmp)
{
    \$data = json_encode(['startmicrotime' => \$runstarttime, 'endmicrotime' => \$runendtime ,'time' => time(), 'runtime'=>\$runningtime,'output' => \$output]);
    // 这里要记录一下状态的,每次更新最后状态
    file_put_contents("{$statusfile}",\$data);
}
?>
EOF;
            clearstatcache();
            // 仅当pid文件不存在的时候重新写
            // 根据命令生成log
            if(!file_exists($lock_file))
            {
                // 没有文件先生成文件先
                $this->LogEchoWrite('[info]【'.$command_before.'】-->start');
                file_put_contents($lock_file, $contents);
            }
            if($command)
            {
                // 队列运行和同步运行
                $task_connection = new AsyncTcpConnection('Text://127.0.0.1:12345');
                // $task_connection = new TcpConnection('Text://127.0.0.1:12345');
                // 发送数据
                $task_connection->send($command);
                // 异步获得结果
                $task_connection->onMessage = function($task_connection, $task_result)
                {
                    $this->LogEchoWrite($task_result);
                    // 获得结果后记得关闭异步连接
                    $task_connection->close();
                };
                // 执行异步连接
                $task_connection->connect();
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