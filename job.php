<?php
/**
 * Cronshow开始运行
 */
date_default_timezone_set('PRC');
define("CRONPATH", dirname(__FILE__));
require CRONPATH.'/vendor/autoload.php';
use Workerman\Worker;
use cronshow\CronWorker;
use cronshow\JobWorker;
use cronshow\WebWorker;

$jobworker = new JobWorker();
$cronworker = new CronWorker();
$webworker = new WebWorker();

// 运行所有服务
Worker::runAll();