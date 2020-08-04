# cronshow
定时任务控制台,Linux 定时任务控制

# 运行环境
1. php >= 7.0
   php扩展需要 pcntl libevent
2. Linux
3. 预留linux的 7788与8877端口

# 使用教程
## 配置
配置要启动的job
Application/config下的配置文件
### 按天运行
`
Day.php 每天某个时刻的运行
// 例如每天10点15分运行一次
return [
    "10:15" => [
    "echo no",
    "sh /test/test.sh"
    ]
];
`
### 按分钟运行
`
Minute.php 每隔多少分钟运行一次
// 每1钟01秒运行一次
return [
    "1" => [
    "echo no"
    ]
];
`
### 按秒运行
`
Second.php 每隔多少秒运行一次
// 每5秒运行一次
return [
    "5" => [
    "echo no"
    ]
];
`

# 运行cron服务
本根目录运行以下命令即可
php start.php start -d


