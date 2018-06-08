<?php 

// 变量配置
define('DB_TYPE', 'mysql');
define('DB_NAME', 'tangCollege');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_PREFIX', 'tang_');
define('DB_CHARSET', 'utf8');

//模板设置
define('TWIG_CACHE', false); //是否模板开启缓存
define('TWIG_DEBUG', true);
define('PATH_CACHE', APP_PATH . '/cache');

//模版替换宏
define('_PUBLIC_',  APP_PATH . 'public');
define('_SHARE_', APPLICATION_PATH . 'views/share');
define('_UPLOAD_', _PUBLIC_ . 'upload');
define('_DOWNLOAD_', _PUBLIC_ . 'download');
define('_CACHE_', _PUBLIC_ . 'cache');
define('TABLE_PRIFIX', '.twig');
define('URL_MODEL', 1);
define('URL_HTML_SUFFIX', '.html');
define('MAXFORMTOKEN', 5);//同一个session最大的form会话数量

//七牛秘钥
define('QINIU_AK','C7BXTICSnpNWE8mXvjBjJyBnTttu6VurKOU97VBm');
define('QINIU_SK','klEAYVLO-auz0hZsjBlsRgHOxP8Z55LdqCFx8Sh7');
define('QINIU_BUCKET','dttx-edu-images');
define('QINIU_URL','https://edu-img.dttx.com/');
define('QINIU_UPLOAD_PRE','dttx-edu-');

//七牛直播配置
define('QINIU_LIVE_DOMAIN','mweisky.cn');  //直播域名
define('QINIU_HUB','live-dttx-test');      //直播空间名
define('QINIU_STREAMKEY','obs-edu');       //流名

//邮箱配置
define('EMAIL_STMP','smtp.qq.com');
define('EMAIL_PORT',25);
define('EMAIL_USER','770517692@qq.com');
define('EMAIL_PASSWORD','673323125jjun');
define('EMAIL_FROM','770517692@qq.com');
define('EMAIL_FROM_NAME','aupl0417');
//define('EMAIL_STMP','smtp.dttx.com');
//define('EMAIL_PORT',25);
//define('EMAIL_USER','edu@dttx.com');
//define('EMAIL_PASSWORD','T6GWPNQK');
//define('EMAIL_FROM','edu@dttx.com');
//define('EMAIL_FROM_NAME','大唐天下教育平台');

define('SESSION_PREX','todo_$#@');