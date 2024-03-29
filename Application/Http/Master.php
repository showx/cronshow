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
    public $login_url;
    public $autoredirectloginurl = false;
    public function __construct()
    {
        $this->config = include CRONPATH.'/Application/Config/Web.php';
        $this->login_url = $this->config['login_url'] ?? '';
        $this->autoredirectloginurl = $this->config['autoredirectloginurl'];
        if(!empty($this->config['client']))
        {
            $this->master = 1;
            $this->client = $this->config['client'];
        }
    }

    /**
     * 权限判断
     *
     * @param [type] $connection
     * @param [type] $request
     * @return void
     */
    private function acl($connection, $request)
    {
        $this->secret = md5($this->config['key'].date("Ymd"));
        $aclfile = CRONPATH.'/Application/Config/Acl.php';
        if(file_exists($aclfile))
        {
            $acl = include $aclfile;
        }else{
            return true;
        }
        if(!$acl)
        {
            if($this->autoredirectloginurl)
            {
                $response = new \Workerman\Protocols\Http\Response(302, [
                    'Location' => $this->login_url,
                ], '');
                $connection->send($response);
            }
        }
        return $acl;
    }

    /**
     * 进程列表
     *
     * @param [type] $connection
     * @param [type] $request
     * @return void
     */
    public function list($connection, $request)
    {
        if(!$this->acl($connection, $request))
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
            $clientdata = web::curlData($host, "Client_list", $this->secret);
            $txtArr = web::txtData($clientdata, $txtArr);
        }
        $result = webview::statusTable($txtArr);
        $connection->send($result);
    }

    /**
     * 结果页
     */
    public function result($connection, $request)
    {
        if(!$this->acl($connection, $request))
        {
            $connection->send("acl error!");
            return false;
        }
        $host = $request->get("agent", "");
        $id = $request->get("id", "");
        $param = "&id={$id}";
        $clientdata = web::curlData($host, "Client_result", $this->secret, $param);
        var_dump($clientdata);
        $connection->send("结果：".$clientdata);
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
        if(!$this->acl($connection, $request))
        {
            $connection->send("acl error!");
            return false;
        }
        $host = $request->get("agent", "");
        $id = $request->get("id", "");
        $param = "&id={$id}";
        $clientdata = web::curlData($host, "Client_stop", $this->secret, $param);
        var_dump($clientdata);
        $connection->send("状态".$clientdata['status']);
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
        if(!$this->acl($connection, $request))
        {
            $connection->send("acl error!");
            return false;
        }
        $host = $request->get("agent", "");
        $id = $request->get("id", "");
        $param = "&id={$id}";
        $clientdata = web::curlData($host, "Client_start", $this->secret, $param);
        $connection->send("状态".$clientdata['status']);
    }

}