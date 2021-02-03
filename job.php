<?php
/**
 * Cronshow开始运行
 */
date_default_timezone_set('PRC');
require dirname(__FILE__).'/vendor/autoload.php';
use Workerman\Worker;
use Application\CronWorker;
use Application\JobWorker;
use Application\WebWorker;

// task worker，使用Text协议
$task_worker = new JobWorker();
$cronworker = new CronWorker();
$webworker = new WebWorker();

// 运行所有服务
Worker::runAll();