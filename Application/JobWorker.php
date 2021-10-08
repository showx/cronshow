<?php
/**
 * Cron运行
 * Author:show
 */

namespace cronshow;
use \Application\Library\Cron as cron;
use \Application\Library\Web as web;
use \Application\Library\WebView as webview;

class JobWorker extends CronBaseWorker
{
    public $name = 'JobWorker_cronTaskWorker';
    public $count = 30;
    public $config = [];
    public function __construct()
    {
        parent::__construct("Text://0.0.0.0:12345");
    }

    public function onMessage($connection, $task_data)
    {
        $task_result = '';
        if($task_data)
        {
            // 这里是按顺序的
            $command = $task_data;
            $filename = cron::hexname($command);
            $pid_file = $this->Lock_Dir.'/pid_'.$filename.".txt";
            $sync = Cron::issync($command);
            $startpid = cron::start($filename, $sync);
            if($startpid == 0)
            {
                // clearstatcache();
                // $pidstat = stat($lock_file);
                // $maxlongtime = $pidstat['atime'] + $this->maxruntime;
                if(file_exists($pid_file))
                {
                    $content = file_get_contents($pid_file);
                    $data = explode("|", $content);
                    $filetime = $data[1];
                    $pid = $data[0];
                    $maxlongtime = $filetime + $this->maxruntime;
                    $time = time();
                    // echo $command."|".$pidstat['atime']."|"."maxlongtime:".$maxlongtime."|{$time}\n";
                    // 因为还在锁定中，所以一定要写日志，有可能死锁的状态 ,所以要有时间的判断
                    if($time > $maxlongtime)
                    {
                        $this->LogEchoWrite('[error]【'.$command.'】-->maxtime!!');
                        // 关闭进程
                        $stopstatus = cron::stop($pid);
                        $this->LogEchoWrite('[warning]【'.$command.'_pid:'.$pid."status:".$stopstatus.'】-->close process');
                    }else{
                        $this->LogEchoWrite('[warning]【'.$command.'】-->already running');
                    }
                }else{
                    $this->LogEchoWrite('[error]'.$command.'-->pid文件不存在');
                }
                
            }elseif($startpid == -123)
            {
                $this->LogEchoWrite('[info]'.$command.'-->同步进程运行结束');
            }else{
                $this->LogEchoWrite('[info]【'.$command.'】-->运行的pid是'.$startpid);
            }
            $task_result = $task_data.'|task end';
        }
        // 发送结果
        $connection->send($task_result);
    }

    /**
     * 开始运行
     * @param $file
     */
    public function run()
    {
        $this->onMessage = array($this, 'onMessage');
        parent::run();
    }

}