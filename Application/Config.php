<?php
/**
 * Config配置获取
 * Author:show
 */

namespace Application;

Class Config
{
    public $Timestamp;
    public $Year;
    public $Month;
    public $Day;
    public $Hour;
    public $Minute;
    public $Second;
    public $Queue;
    public function __construct()
    {
//        echo 'Load Config'.PHP_EOL;
    }

    /**
     * 时间重置
     */
    public function stamp()
    {
        $this->Timestamp = time();
        $this->Year = date('Y',$this->Timestamp);
        $this->Month = intval(date('m',$this->Timestamp));
        // 1 to 31
        $this->Day = date('j',$this->Timestamp);
        // 0 through 23
        $this->Hour = date('G',$this->Timestamp);
        // 00 to 59
        $this->Minute = intval(date('i',$this->Timestamp));
        // 00 to 59
        $this->Second = intval(date('s',$this->Timestamp));
        $this->Queue = [];
    }

    /**
     * 获取现在可运行的配置
     * @param string $type
     * @return mixed
     */
    public function get($type = '')
    {
        $this->stamp();
        $this->LoadDay();
        $this->LoadMinute();
        $this->LoadSecond();
        return $this->Queue;
    }

    /**
     * 每天要执行的任务
     */
    public function LoadDay()
    {
        $Day = include __DIR__.'/Config/Day.php';
        // 计算出时分秒
        if($Day)
        {
            foreach($Day as $time => $exec)
            {
                // 按日的计算规则
                $timetmp = explode(":",$time);
                $hour = $timetmp[0];
                $minute = $timetmp[1];
                if(isset($timetmp[2]))
                {
                    $second = $timetmp[2];
                }else{
                    // 默认第1秒开始执行
                    $second = 1;
                }
                if( $this->Hour == $hour && $this->Minute == $minute && $this->Second == $second )
                {
                    // 符合的情况就加入队列
                    $this->push($exec);
                }
            }
        }
    }

    /**
     * 按每分钟计算
     */
    public function LoadMinute()
    {
        $Minute = include __DIR__.'/Config/Minute.php';
        if($Minute)
        {
            foreach($Minute as $time => $exec)
            {
                // 60分来算
                $tmp = $this->Minute % $time;
                $second = 1;
                if($tmp === 0)
                {
                    if($this->Second == $second)
                    {
                        $this->push($exec);
                    }
                }
            }
        }
    }

    /**
     * 按秒计算
     */
    public function LoadSecond()
    {
        $Second = include __DIR__.'/Config/Second.php';
        if($Second)
        {
            foreach($Second as $time => $exec)
            {
                // 60分来算
                $tmp = $this->Second % $time;

                if($tmp === 0)
                {
                    // 默认秒数是从第1秒起

                    $this->push($exec);

                }
            }
        }
    }

    /**
     * 加入定时间任务
     */
    public function push($exec)
    {
        if(is_array($exec))
        {
            foreach($exec as $subexec)
            {
                $subexec = trim($subexec);
                array_push($this->Queue,$subexec);
            }
        }else{
            $exec = trim($exec);
            array_push($this->Queue,$exec);
        }

    }

    /**
     * 按月计算
     * todo 暂时感觉没需要
     */
    public function LoadMonth()
    {

    }

}