<?php
/**
 * Cron设置
 * Author:show
 */

namespace Application\Library;

Class Cron
{
    public static $Status_Dir = CRONPATH.'/Application/Status';
    public static $Lock_Dir = CRONPATH.'/Application/Lock';

    /**
     * 加密后的名字(用来区分唯一command)
     * 
     * bname
     * @param string $command
     */
    public static function hexname($command = '')
    {
        $command = addslashes($command);
        $filename = md5(trim($command));
        return $filename;
    }

    /**
     * 生成lock文件
     *
     * @param string $command
     * @param integer $timeout
     * @return bool
     */
    public static function locktpl($command = '', $timeout = 2)
    {
        $filename = cron::hexname($command);
        $sync = Cron::issync($command);
        if($sync)
        {
            $command = substr($command, 0, -6);
        }
        $commandArr = explode(" ", $command);
        $commandparam = [];
        foreach($commandArr as $param)
        {
            $param = trim($param, "'");
            $param = trim($param, "\"");
            if(!empty($param))
            {
                array_push($commandparam, $param);
            }
        }
        $binfile = array_shift($commandparam);
        // 需要绝对路径
        if($binfile == 'php')
        {
            $binfile = '/usr/local/bin/php';
        }
        $commandstr = "['".implode("','", $commandparam)."']";
        $lock_file = self::$Lock_Dir.'/'.$filename.".php";
        $pid_file = self::$Lock_Dir.'/pid_'.$filename.".txt";
        $timeoutfile = CRONPATH.'/Application/Log/timeoutrun.txt';
        $runerror = CRONPATH.'/Application/Log/runerror.txt';
        $statusfile = self::$Status_Dir.'/'.$filename.".txt";
        $contents1=<<<EOF
<?php
\$runstarttime = microtime(true);
// 这里阻塞一下没问题的
\$tmp = exec("{$command}", \$output);
\$runendtime = microtime(true);
// 计算出运行时间
\$runningtime = \$runendtime - \$runstarttime;
if(\$runningtime >= {$timeout})
{
    file_put_contents("{$timeoutfile}","very late:{$command}\\r\\n",FILE_APPEND|LOCK_EX);
}
if(\$tmp)
{
    \$data = json_encode(['startmicrotime' => \$runstarttime, 'endmicrotime' => \$runendtime ,'time' => time(), 'runtime'=>\$runningtime,'output' => \$output]);
    // 这里要记录一下状态的,每次更新最后状态
    file_put_contents("{$statusfile}",\$data);
}
?>
EOF;
        $contents=<<<EOF
<?php
pcntl_async_signals(true);
\$runstarttime = microtime(true);
\$pid = pcntl_fork();
if(\$pid > 0)
{
    \$fork_pid = \$pid;
}
if (\$pid == -1) {
    die('could not fork');
} else if (\$pid) {
    pcntl_signal(SIGINT, function() use(\$fork_pid){
        posix_kill(\$fork_pid, \\SIGKILL);
    }, false);
    pcntl_wait(\$status);
    \$runendtime = microtime(true);
    // 计算出运行时间
    \$runningtime = \$runendtime - \$runstarttime;
    if(\$runningtime >= 2)
    {
        file_put_contents("{$timeoutfile}", "very late:php {$command}", FILE_APPEND|LOCK_EX);
    }
    \$data = json_encode(['startmicrotime' => \$runstarttime, 'endmicrotime' => \$runendtime ,'time' => time(), 'runtime' => \$runningtime,'output' => '']);
    // 这里要记录一下状态的,每次更新最后状态
    file_put_contents("{$statusfile}", \$data);
    unlink("{$lock_file}");
    unlink("{$pid_file}");
} else {
    try{
        \$tmp = pcntl_exec("{$binfile}", {$commandstr});
    }catch(\Exception \$e){
        \$tmp = "cron_exec_error".\$e->getMessage();
    }finally{
        file_put_contents("{$runerror}", "error: {$command}", FILE_APPEND|LOCK_EX);
    }
}
?>
EOF;
        // 仅当pid文件不存在的时候重新写
        // 根据命令生成log
        if(!file_exists($lock_file))
        {
            // 没有文件先生成文件先
            file_put_contents($lock_file, $contents);
            return true;
        }
        return false;
    }


    /**
     * 查看进程状态
     *
     * @param [type] $pid
     * @return void
     */
    public static function status($pid){
        $command = 'ps -p '.$pid;
        exec($command,$op);
        if (!isset($op[1]))return false;
        else return true;
    }

    public static function issync($command)
    {
        $com = substr($command, -6, 6);
        if($com == '^queue')
        {
            return true;
        }else{
            return false;
        }
    }

    /**
     * 开始任务
     *
     * @param string $filename
     * @return void
     */
    public static function start($filename = '', $sync = false){
        $lock_file = self::$Lock_Dir.'/'.$filename.".php";
        $pid_file = self::$Lock_Dir.'/pid_'.$filename.".txt";
        $result_file = self::$Status_Dir.'/result_'.$filename.".txt";
        $t2 = exec("ps -aux|grep {$filename}|grep -v 'grep' ");
        // var_dump($t2);
        // echo $lock_file."\n";
        if(empty($t2)) //或者判断lock是否在运行
        {
            if(file_exists($lock_file))
            {
                echo $lock_file."开始启动\n";
                if(!$sync)
                {
                    // echo "异步模式\n";
                    // 异步模式
                    exec("nohup php $lock_file > {$result_file} 2>&1 & echo $!", $output);
                }else{
                    // echo "同步模式\n";
                    // 同步模式
                    exec("php $lock_file > {$result_file}");
                    // exec("echo $!", $output);
                    // 运行完之后进程已经结束了
                    $output = [0 => -123];
                }
                $lockpid = (int)$output[0]."|".time();
                file_put_contents($pid_file, $lockpid);
                return $output[0];
                return 0;
            }
        }
        return 0;
    }

    /**
     * 结束任务
     *
     * @param integer $pid
     * @return void
     */
    public static function stop($pid = 0){
        if(!is_numeric($pid) || empty($pid))
        {
            return false;
        }
        // $command = 'kill -9 '.$pid;
        // exec($command);
        $status = posix_kill($pid, \SIGINT);
        return $status;
        // if (self::status($pid) == false)return true;
        // else return false;
    }

}