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

    public function stop($connection, $request)
    {
        if(!$this->secret($request))
        {
            $connection->send("secret error!");
            return false;
        }
        $filename = $request->get("id", "");
        $pid_file = CRONPATH.'/Application/Lock/pid_'.$filename.".txt";
        $content = file_get_contents($pid_file);
        $data = explode("|", $content);
        $filetime = $data[1];
        $pid = $data[0];
        $status = false;
        if($pid)
        {
            echo "进行stop\n";
            $status = cron::stop($pid);
            var_dump($status);
        }
        $consolelog = "start_".$pid.".".$status;
        echo $consolelog."\n";
        $connection->send($consolelog);
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
        echo $consolelog."\n";
        $connection->send($consolelog);
    }

}