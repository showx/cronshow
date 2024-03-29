<?php
/**
 * Web模块
 * Author:show
 */

namespace Application\Library;

Class Web
{
    public static $Status_Dir = CRONPATH.'/Application/Status';
    public static $Lock_Dir = CRONPATH.'/Application/Lock';
    /**
     * 列出文件逻辑
     * @todo 考虑读取超时的问题，client一般填内网ip
     * 
     */
    public static function curlData($url, $op = "client_list", $secret = '', $param = '')
    {
        $gurl = "http://{$url}/?op={$op}&secret={$secret}{$param}";
        echo $gurl.PHP_EOL;
        $tmp = web::httpget($gurl);
        $isjson = is_null(json_decode($tmp));
        if(!$isjson)
        {
            $tmp = json_decode($tmp, true);
        }
        return $tmp;
    }

    /**
     * 命令状态
     *
     * @return void
     */
    public static function clientList()
    {
        $dayArr = include CRONPATH.'/Application/Config/Day.php';
        $minArr = include CRONPATH.'/Application/Config/Minute.php';
        $secArr = include CRONPATH.'/Application/Config/Second.php';
        $tmp = ['day' => $dayArr, 'min' => $minArr, 'sec' => $secArr];
        $txtArr = [];
        foreach(['day', 'min', 'sec'] as $key)
        {
            // echo $key.PHP_EOL;
            if(isset($tmp[$key]))
            {
                foreach($tmp[$key] as $daytime => $day)
                {
                    if(is_array($day))
                    {
                        foreach($day as $dd)
                        {
                            $txtArr[] = self::statusResult($dd, $daytime, $key);
                        }
                    }else{
                        $txtArr[] = self::statusResult($day, $daytime, $key);
                    }
                }
            }
        }
        
        $data = json_encode($txtArr);
        return $data;

    }

    /**
     * 状态action的结果集
     *
     * @param string $command
     * @param string $daytime
     * @param [type] $key
     */
    public static function statusResult($command = '', $daytime = '', $key = '')
    {
        $hexname = Cron::hexname($command);
        $data = self::mdfile($command);
        $result = $key."^".$daytime."|".$command."|".$data['status']."|".$hexname."|".$data['output'];
        return $result;
    }

    /**
     * 查看命令状态
     *
     * @param string $command
     */
    public static function mdfile($command = '')
    {
        $filename = Cron::hexname($command);
        $status_file = self::$Status_Dir.'/'.$filename.".txt";
        $result_file = self::$Status_Dir.'/result_'.$filename.".txt";
        $result = [];
        if(file_exists($status_file))
        {
            $result_content = file_get_contents($result_file);
            // 这里解决一下再显示
            $status_content = file_get_contents($status_file);
            $data = json_decode($status_content, true);
            $status_content = "开始运行时间:".date("Y-m-d H:i:s", $data['startmicrotime'])." 结束运行时间:".date("Y-m-d H:i:s", $data['endmicrotime'])."运行时长:".$data['runtime'];
            $result['status'] = $status_content;
            $result['output'] = $result_content;
        }else{
            $result['status'] = '';
            $result['output'] = '';
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
    public static function txtData($tmp, $txtArr = [])
    {
        if($tmp)
        {
            foreach($tmp as $val)
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