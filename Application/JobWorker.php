<?php
/**
 * Cron运行
 * Author:show
 */

namespace cronshow;

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
            $command = addslashes($command);
            $filename = md5($command);
            $lock_file = $this->Lock_Dir.'/'.$filename.".php";
            $pid_file = $this->Lock_Dir.'/pid_'.$filename.".txt";
            // 这里要判断运行的进程有没结束
            $t2 = exec("ps -aux|grep {$lock_file}|grep -v 'grep' ");
            if(empty($t2)) //或者判断lock是否在运行
            {
                exec("nohup php $lock_file > /dev/null 2>&1 & echo $!", $output);
                $lockpid = (int)$output[0]."|".time();
                file_put_contents($pid_file, $lockpid);
                // $this->LogEchoWrite('[warning]【'.$command_before.'】-->unlink running');
            }else{
                clearstatcache();
                // $pidstat = stat($lock_file);
                // $maxlongtime = $pidstat['atime'] + $this->maxruntime;
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
                    $command = 'kill '.$pid;
                    exec($command);
                    $this->LogEchoWrite('[warning]【'.$command.'_pid:'.$pid.'】-->close process');
                }else{
                    $this->LogEchoWrite('[warning]【'.$command.'】-->already running');
                }
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