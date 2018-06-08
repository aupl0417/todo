<?php 

// 变量配置
return array(
    /* 数据库设置 */
    'DB_TYPE'               =>  'mysql',     // 数据库类型
    'DB_HOST'               =>  '192.168.3.203', // 服务器地址
    'DB_NAME'               =>  'dttxEdu',          // 数据库名
    'DB_USER'               =>  'root',      // 用户名
    'DB_PWD'                =>  'sql@8234ERe8',        // 密码
    'DB_PREFIX'             =>  'tang_',    // 数据库表前缀
    'DB_PORT'               =>  '3306',    // 数据库端口

    //memecache配置
    'MEMCACHE_OPEN'         => TRUE,
    'MEMCACHE_COMP'         => FALSE,
    'MEMCACHE_SERVERS'      => 'localhost',
    'MEMCACHE_PORT'         => '11211'
);