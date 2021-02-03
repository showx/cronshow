<?php
/**
 * 运行web管理
 */
namespace Application;

class WebWorker extends CronBaseWorker
{
    public $name = 'WebWorker';
    public $count = 1;
    public $oparr = ['list','start','end'];
    
    public function __construct()
    {
        parent::__construct("http://0.0.0.0:8080");
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
     */
    public function onMessage($connection, $request)
    {
        $op = $request->get('op','list');
        if(!in_array($op,$this->oparr))
        {
            $connection->send("不存在的action");
        }else{

            switch($op)
            {
                case 'list':
                    $connection->send("列出文件");
                    break;
                default:
                    $connection->send("what are you doing!");
                    break;
            }
        }
    }

}