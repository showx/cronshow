<?php
/**
 * Config配置获取
 * Author:show
 */

namespace Application\Library;

Class Web
{
    public static $Status_Dir;
    public static $Lock_Dir;
    /**
     * 列出文件逻辑
     * @todo 考虑读取超时的问题，client一般填内网ip
     * 
     */
    public static function agentOp($url, $op="cl_list", $secret)
    {
        $gurl = "http://{$url}/?op={$op}&secret={$secret}";
        // 这里地址不对容易死循环
        $tmp = web::httpget($gurl);
        if($tmp)
        {
            $tmp = json_decode($tmp, true);
            return $tmp;
        }else{
            return false;
        }
    }

    /**
     * 返回cronjob列表
     * json格式
     */
    public static function clientList()
    {
        $dayArr = include __DIR__.'/Config/Day.php';
        $minArr = include __DIR__.'/Config/Minute.php';
        $secArr = include __DIR__.'/Config/Second.php';
        $data = ['day' => $dayArr, 'min' => $minArr, 'sec' => $secArr];
        $data = json_encode($data);
        return $data;
    }

    /**
     * 命令状态
     *
     * @return void
     */
    public static function clientStatus()
    {
        $dayArr = include __DIR__.'/Config/Day.php';
        $minArr = include __DIR__.'/Config/Minute.php';
        $secArr = include __DIR__.'/Config/Second.php';
        $tmp = ['day' => $dayArr, 'min' => $minArr, 'sec' => $secArr];
        $txtArr = [];
        foreach(['day', 'min', 'sec'] as $key)
        {
            if(isset($tmp[$key]))
            {
                foreach($tmp[$key] as $daytime => $day)
                {
                    if(is_array($day))
                    {
                        foreach($day as $dd)
                        {
                            $data = self::mdfile($dd);
                            $txtArr[] = $daytime."|".$dd."|".$data['status'];
                        }
                    }else{
                        $data = self::mdfile($dd);
                        $txtArr[] = $daytime."|".$day."|".$data['status'];
                    }
                }
            }
        }
        $data = json_encode($txtArr);
        return $data;

    }

    /**
     * 查看命令状态
     *
     * @param string $command
     */
    public static function mdfile($command = '')
    {
        $command = addslashes($command);
        $filename = md5($command);
        $lock_file = self::$Lock_Dir.'/'.$filename.".php";
        $status_file = self::$Status_Dir.'/'.$filename.".txt";
        $result = [];
        if(file_exists($status_file))
        {
            $result['status'] = file_get_contents($status_file);
        }
        return $result;
    }

    /**
     * 整理返回数组
     *
     * @param [type] $tmp
     * @param array $txtArr
     * @return void
     */
    public static function listData($tmp, $txtArr = [])
    {
        if($tmp)
        {
            foreach(['day', 'min', 'sec'] as $key)
            {
                if(isset($tmp[$key]))
                {
                    foreach($tmp[$key] as $daytime => $day)
                    {
                        if(is_array($day))
                        {
                            foreach($day as $dd)
                            {
                                $txtArr[] = $daytime."|".$dd;
                            }
                        }else{
                            $txtArr[] = $daytime."|".$day;
                        }
                    }
                }
            }
        }
        return $txtArr;
    }



    /**
     * 整理返回数组
     *
     * @param [type] $tmp
     * @param array $txtArr
     * @return void
     */
    public static function statusData($tmp, $txtArr = [])
    {
        if($tmp)
        {
            foreach($tmp as $key => $val)
            {
                $txtArr[] = $val;
            }
        }
        return $txtArr;
    }


    public static function httpget($url)
    {
        // echo 'curl_start'.PHP_EOL;
        $ch = curl_init(); 
        // set url 
        curl_setopt($ch, CURLOPT_URL, $url); 
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        //return the transfer as a string 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 

        curl_setopt($ch, CURLOPT_VERBOSE, true); // curl debug
        curl_setopt($ch, CURLOPT_STDERR, fopen(CRONPATH.'/Application/Log/curl.log', 'w+'));


        // $output contains the output string 
        $output = curl_exec($ch); 

        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            echo 'curl error:'.$error_msg.PHP_EOL;
        }
        //echo output
        // close curl resource to free up system resources 
        curl_close($ch); 
        return $output;
    }

}