<?php
/**
 * Cronshow开始运行
 */
date_default_timezone_set('PRC');
require dirname(__FILE__).'/vendor/autoload.php';
use Workerman\Worker;
use cronshow\CronWorker;
use cronshow\JobWorker;
use cronshow\WebWorker;

// task worker，使用Text协议
$task_worker = new JobWorker();
$cronworker = new CronWorker();
$webworker = new WebWorker();

// 运行所有服务
Worker::runAll();