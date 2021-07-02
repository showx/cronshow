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
    public static function agentOp($url, $op = "cl_list", $secret = '')
    {
        $gurl = "http://{$url}/?op={$op}&secret={$secret}";
        echo $gurl.PHP_EOL;
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
        $dayArr = include CRONPATH.'/Application/Config/Day.php';
        $minArr = include CRONPATH.'/Application/Config/Minute.php';
        $secArr = include CRONPATH.'/Application/Config/Second.php';
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
        $dayArr = include CRONPATH.'/Application/Config/Day.php';
        $minArr = include CRONPATH.'/Application/Config/Minute.php';
        $secArr = include CRONPATH.'/Application/Config/Second.php';
        $tmp = ['day' => $dayArr, 'min' => $minArr, 'sec' => $secArr];
        $txtArr = [];
        foreach(['day', 'min', 'sec'] as $key)
        {
            echo $key.PHP_EOL;
            if(isset($tmp[$key]))
            {
                foreach($tmp[$key] as $daytime => $day)
                {
                    if(is_array($day))
                    {
                        foreach($day as $dd)
                        {
                            $data = self::mdfile($dd);
                            $txtArr[] = $key."^".$daytime."|".$dd."|".$data['status'];
                        }
                    }else{
                        $data = self::mdfile($day);
                        $txtArr[] = $key."^".$daytime."|".$day."|".$data['status'];
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
        $filename = md5(trim($command));
        $lock_file = self::$Lock_Dir.'/'.$filename.".php";
        $status_file = self::$Status_Dir.'/'.$filename.".txt";
        $result = [];
        if(file_exists($status_file))
        {
            $result['status'] = file_get_contents($status_file);
        }else{
            $result['status'] = '';
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
                                $txtArr[] = $key."^".$daytime."|".$dd;
                            }
                        }else{
                            $txtArr[] = $key."^".$daytime."|".$day;
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

    /**
     * 同一个进程有阻塞的情况
     *
     * @param [type] $url
     * @param integer $timeout
     * @return void
     */
    public static function httpget($url, $timeout = 2)
    {
        
        $ch = curl_init();
        $headers = array();
        $headers[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
        $headers[] = 'Accept-Encoding: gzip, deflate';
        $headers[] = 'Accept-Language: en-US,en;q=0.5';
        $headers[] = 'Cache-Control: no-cache';
        $headers[] = 'Content-Type: application/x-www-form-urlencoded; charset=utf-8';
        $headers[] = 'X-MicrosoftAjax: Delta=true';
        
        // $cookie = tempnam ("/tmp", "CURLCOOKIE");
        // curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie );
        // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false );

        // curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true );
        // curl_setopt($ch, CURLOPT_ENCODING, "" );

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        // curl_setopt($ch, CURLOPT_USERAGENT, 'Your application name');
        curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 2.0.50727;)');

        // set url 
        curl_setopt($ch, CURLOPT_URL, $url); 
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        //return the transfer as a string 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_VERBOSE, true); // curl debug
        curl_setopt($ch, CURLOPT_STDERR, fopen(CRONPATH.'/Application/Log/curl.log', 'w+'));
        // $output contains the output string 
        $output = curl_exec($ch); 

        $response = curl_getinfo( $ch );

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