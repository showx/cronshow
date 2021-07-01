<?php
/**
 * 运行web管理
 */
namespace cronshow;
use \Application\Library\Web as web;
use \Application\Library\WebView as webview;

class WebWorker extends CronBaseWorker
{
    public $name = 'WebWorker';
    public $master = 1;
    public $count = 1;
    public $oparr = ['list', 'stop', 'status'];
    public $config = [];
    public $client = [];
    public $ip = "0.0.0.0";
    // 主服务器使用8080,其它可以使用指定的
    public $port = "8090";
    
    public function __construct()
    {
        $this->config = include __DIR__.'/Config/Web.php';
        // 这台是主服务器 映射本地端口
        if(!empty($this->config['client']))
        {
            $this->master = 1;
            $this->client = $this->config['client'];
            echo 'server'.PHP_EOL;
        }else{
            echo 'client'.PHP_EOL;
        }
        $this->ip = $this->config['ip'] ?? $this->ip;
        $this->port = $this->config['port'] ?? $this->port;
        parent::__construct("http://{$this->ip}:{$this->port}");

        web::$Lock_Dir = $this->Lock_Dir;
        web::$Status_Dir = $this->Status_Dir;
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

    /**
     * agent的处理
     *
     * @param string $op
     * @return void
     */
    public function agent($op = 'list')
    {
        $secret = md5($this->config['key'].date("Ymd"));
        $txtArr = [];
        $result = '';
        foreach($this->client as $host)
        {
            $txtArr[] = "client:".$host;
            switch($op)
            {
                case 'list':
                    $tmp = web::agentOp($host, "cl_list", $secret);
                    if($tmp)
                    {
                        $txtArr = web::listData($tmp, $txtArr);
                    }
                    break;
                case 'status':
                    $tmp = web::agentOp($host, "cl_status", $secret);
                    if($tmp)
                    {
                        $txtArr = web::statusData($tmp, $txtArr);
                    }
                    break;
                default:
                    $txtArr[] = "没数据";
                    break;
            }
        }
        switch($op)
        {
            case 'list':
                $result = webview::listTable($txtArr);
                break;
            case 'status':
                $result = webview::statusTable($txtArr);
                break;
        }
        return $result;
    }

    

    /**
     * 处理web请求
     * list 列出配置
     * listrun 列出正在运行
     * list/1/del, 删除配置
     * stop 关闭正在运行
     */
    public function onMessage($connection, $request)
    {
        $op = $request->get('op', '');
        $getsecret = $request->get('secret', '');
        $this->LogEchoWrite("操作：".$op);
        // 客户端接收的处理
        if(substr($op, 0, 3) == 'cl_')
        {
            // 只提供信息返回
            $secret = md5($this->config['key'].date("Ymd"));
            if($getsecret == $secret)
            {
                switch($op)
                {
                    case 'cl_list':
                        $data = web::clientList();
                        $sendstatus = $connection->send($data);
                        break;
                    case 'cl_status':
                        $data = web::clientStatus();
                        $sendstatus = $connection->send($data);
                        break;
                }
            }else{
                echo 'error'.PHP_EOL;
            }
        }
        if($this->master == 1)
        {
            if(!in_array($op, $this->oparr))
            {
                $connection->send("不存在的action!");
            }else{
                // 列出更agent的
                $result = $this->agent($op);
                $connection->send($result);
            }
        }
        
        
    }

}