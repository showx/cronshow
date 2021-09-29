<?php
/**
 * Config配置获取
 * Author:show
 */

namespace Application\Http;
use \Application\Library\Web as web;
use \Application\Library\WebView as webview;

Class Master
{
    public $config;
    private $secret = '';
    public function __construct()
    {
        $this->config = include CRONPATH.'/Application/Config/Web.php';
        if(!empty($this->config['client']))
        {
            $this->master = 1;
            $this->client = $this->config['client'];
        }
    }

    private function acl($request)
    {
        $this->secret = md5($this->config['key'].date("Ymd"));
        $aclfile = CRONPATH.'/Application/Config/Acl.php';
        if(file_exists($aclfile))
        {
            $acl = include $aclfile;
        }else{
            return true;
        }
        return $acl;
    }

    public function list($connection, $request)
    {
        if(!$this->acl($request))
        {
            $connection->send("acl error!");
            return false;
        }
        // $data = web::clientList();
        // $connection->send($data);
        $txtArr = [];
        $result = '';
        foreach($this->client as $host)
        {
            $txtArr[] = $host;
            $clientdata = web::curlData($host, "client_list", $this->secret);
            $txtArr = web::txtData($clientdata, $txtArr);
        }
        $result = webview::statusTable($txtArr);
        $connection->send($result);
    }

    /**
     * 停止进程
     *
     * @param [type] $connection
     * @param [type] $request
     * @return void
     */
    public function stop($connection, $request)
    {
        if(!$this->acl($request))
        {
            $connection->send("acl error!");
            return false;
        }
        $host = $request->get("agent", "");
        $id = $request->get("id", "");
        $param = "&id={$id}";
        $clientdata = web::curlData($host, "client_stop", $this->secret, $param);
        $connection->send($clientdata);
    }

    /**
     * 启动job
     *
     * @param [type] $connection
     * @param [type] $request
     * @return void
     */
    public function start($connection, $request)
    {
        if(!$this->acl($request))
        {
            $connection->send("acl error!");
            return false;
        }
        $host = $request->get("agent", "");
        $id = $request->get("id", "");
        $param = "&id={$id}";
        $clientdata = web::curlData($host, "client_start", $this->secret, $param);
        $connection->send($clientdata);
    }

}