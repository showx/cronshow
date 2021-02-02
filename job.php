<?php
/**
 * Cronshow开始运行
 */
date_default_timezone_set('PRC');
require dirname(__FILE__).'/vendor/autoload.php';
use Workerman\Worker;
use Application\CronWorker;
use Application\JobWorker;

// task worker，使用Text协议
$task_worker = new JobWorker('Text://0.0.0.0:12345');
$worker = new CronWorker("tcp://0.0.0.0:7788");

// 运行所有服务
Worker::runAll();