<?php
/**
 * Cron运行
 * Author:show
 */

namespace Application;

use Workerman\Lib\Timer;
use Workerman\Connection\AsyncTcpConnection;

class JobWorker extends CronBaseWorker
{
    public $name = 'JobWorker_cronTaskWorker';
    public $count = 30;

    public function onMessage($connection, $task_data)
    {
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
                    // 这里阻塞一下没问题的
                    $tmp = exec("php $pid_file",$output);
                    $this->LogEchoWrite("[info]【{$command}】-->cron_result:".var_export($output,true));
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
        }
        $task_result = 'test end';
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