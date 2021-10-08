<?php
/**
 * Config配置获取
 * Author:show
 */

namespace Application\Http;
use \Application\Library\Web as web;
use \Application\Library\Cron as cron;
use \Application\Library\WebView as webview;

Class Client
{
    public $config;
    public function __construct()
    {
        $this->config = include CRONPATH.'/Application/Config/Web.php';
    }

    private function secret($request)
    {
        $getsecret = $request->get('secret', '');
        // 只提供信息返回
        $secret = md5($this->config['key'].date("Ymd"));
        echo $secret."\n";
        if($getsecret == $secret)
        {
            return true;
        }else{
            return false;
        }
    }

    public function list($connection, $request)
    {
        if(!$this->secret($request))
        {
            $connection->send("secret error!");
            return false;
        }
        $data = web::clientList();
        $connection->send($data);
    }

    public function result($connection, $request)
    {
        if(!$this->secret($request))
        {
            $connection->send("secret error!");
            return false;
        }
        $filename = $request->get("id", "");
        $result_file = CRONPATH.'/Application/Status/result_'.$filename.".txt";
        $content = '';
        if(file_exists($result_file))
        {
            $content = file_get_contents($result_file);
        }
        $connection->send($content);
    }

    public function stop($connection, $request)
    {
        if(!$this->secret($request))
        {
            $connection->send("secret error!");
            return false;
        }
        $filename = $request->get("id", "");
        $pid_file = CRONPATH.'/Application/Lock/pid_'.$filename.".txt";
        $status = false;
        if(file_exists($pid_file))
        {
            $content = file_get_contents($pid_file);
            $data = explode("|", $content);
            $filetime = $data[1];
            $pid = $data[0];
            
            if($pid)
            {
                echo "进行stop\n";
                $status = cron::stop($pid);
                var_dump($status);
            }
        }
        $consolelog = "stop_".$pid.".".$status;
        $senddata = json_encode(['id' => $filename, "console" => $consolelog, "status" => $status]);
        $connection->send($senddata);
    }

    public function start($connection, $request)
    {
        if(!$this->secret($request))
        {
            $connection->send("secret error!");
            return false;
        }
        $filename = $request->get("id", "");
        $status = false;
        if($filename)
        {
            $status = cron::start($filename);
        }
        $consolelog = "start_".$filename.".".$status;
        $senddata = json_encode(['id' => $filename, "console" => $consolelog, "status" => $status]);
        $connection->send($senddata);
    }

}