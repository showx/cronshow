<?php
/**
 * Cron运行
 * Author:show
 */

namespace Application;

class JobWorker extends CronBaseWorker
{
    public $name = 'JobWorker_cronTaskWorker';
    public $count = 30;
    public $timeout = 2;
    public function onMessage($connection, $task_data)
    {
        $task_result = '';
        $task_data = json_decode($task_data,true);
        if($task_data)
        {
            // 这里是按顺序的
            $runFile = $task_data['runfile'];
            foreach($runFile as $command)
            {
                $command = addslashes($command);
                $filename = md5($command);
                $pid_file = $this->Lock_Dir.'/'.$filename.".php";
                
                // 这里要判断运营的进步有没结束
                $t2 = exec("ps -aux|grep {$pid_file}|grep -v 'grep' ");
                if(empty($t2))
                {
                    $runstarttime = microtime(true);
                    // 这里阻塞一下没问题的
                    $tmp = exec("php $pid_file",$output);
                    $runendtime = microtime(true);
                    // 计算出运行时间
                    $runningtime = $runendtime - $runstarttime;
                    if($runningtime >= $this->timeout)
                    {
                        file_put_contents(__DIR__.'/Log/timeoutrun.txt',"very late".$command."\r\n",FILE_APPEND|LOCK_EX);
                    }
                    $this->LogEchoWrite("[info]【{$command}】runtime:{$runningtime}-->cron_result:".var_export($output,true));
                    if($tmp)
                    {
                        unlink($pid_file);
                    }
                    // $this->LogEchoWrite('[warning]【'.$command_before.'】-->unlink running');
                    continue;
                }else{
                    // 因为还在锁定中，所以一定要写日志，有可能死锁的状态
                    $this->LogEchoWrite('[warning]【'.$command.'】-->already running');
                    continue;
                }
            }
            $task_result = $task_data['time'].'|task end';
        }
        // 发送结果
        $connection->send(json_encode($task_result));
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