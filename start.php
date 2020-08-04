<?php
/**
 * Cronshow开始运行
 */
require dirname(__FILE__).'/vendor/autoload.php';
use Workerman\Worker;
use Application\CronWorker;
//require_once __DIR__.'/Application/start.php';

$worker = new CronWorker("tcp://0.0.0.0:7788");
// 进程名称
$worker->name = 'CronWorker';
// bussinessWorker进程数量
$worker->count = 1;

// 运行所有服务
Worker::runAll();