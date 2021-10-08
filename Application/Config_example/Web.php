<?php
/**
 * agent的配置
 */
return [
    "agent_id" => "test",
    "ip" => "0.0.0.0",
    "port" => "8089",
    "server" => "127.0.0.1",
    // 定义web管理的账号密码
    // "server_username" => "admin",
    // "server_password" => "admin",
    "client" => [
        // '172.17.0.5:8089',
        '127.0.0.1:8089'
    ],
    "key" => "test",
    "login_url" => "http://www.baidu.com",
    "autoredirectloginurl" => false,
];