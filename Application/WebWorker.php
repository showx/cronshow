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
    // 避免阻塞，设成3个
    public $count = 3;
    // 列表，结束任务，开始任务,状态
    public $oparr = ['list', 'stop', 'start', 'status'];
    public $config = [];
    public $client = [];
    public $ip = "0.0.0.0";
    // 主服务器使用8080,其它可以使用指定的
    public $port = "8089";
    
    public function __construct()
    {
        $this->config = include __DIR__.'/Config/Web.php';
        // 这台是主服务器 映射本地端口
        if(!empty($this->config['client']))
        {
            $this->master = 1;
            $this->client = $this->config['client'];
            echo 'cronjob_server'.PHP_EOL;
        }else{
            echo 'cronjob_client'.PHP_EOL;
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
     * 处理web请求
     * list 列出配置
     * listrun 列出正在运行
     * list/1/del, 删除配置
     * stop 关闭正在运行
     */
    public function onMessage($connection, $request)
    {   
        $op = $request->get('op', '');
        $this->LogEchoWrite("操作：".$op);
        $data = explode("_", $op);
        if(count($data) != 2)
        {
            $connection->send("错误操作");
            return false;
        }
        $ct = $data[0];
        $ac = $data[1];
        $httpfile = $this->Http_Dir.'/'.$ct.'.php';
        if(file_exists($httpfile))
        {
            include_once($httpfile);
            $cls = 'Application\\Http\\'. $ct;
            if(!class_exists($cls)){
                $connection->send("不存在该操作");
                return false;
            }
            $control = new $cls;
            $response = call_user_func_array(array($control, $ac), [$connection, $request]);
            $connection->send($response);
        }
    }

}