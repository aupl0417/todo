<?php 
header("Content-type: text/html; charset=utf-8");
// 初始化常量
defined('FRAME_ROOT')          or define('FRAME_ROOT', __DIR__ . '/');
defined('APP_PATH')            or define('APP_PATH', dirname($_SERVER['SCRIPT_FILENAME']) . '/');
defined('APP_DEBUG')           or define('APP_DEBUG', false);
defined('CONFIG_PATH')         or define('CONFIG_PATH', APP_PATH . 'config/');
defined('RUNTIME_PATH')        or define('RUNTIME_PATH', APP_PATH . 'runtime/');
defined('APPLICATION_PATH')    or define('APPLICATION_PATH', APP_PATH . 'application/');
defined('MODEL_PATH')          or define('MODEL_PATH', APPLICATION_PATH . 'model/');
defined('CONTROLLER_PATH')     or define('CONTROLLER_PATH', APPLICATION_PATH . 'controller/');
defined('DEFAULT_FILTER')      or define('DEFAULT_FILTER', 'htmlspecialchars,strip_tags');
defined('URL_PATHINFO_DEPR')   or define('URL_PATHINFO_DEPR', '/');
defined('DOMAIN')              or define('DOMAIN', '.auple.com');
defined('PATH_TEMPLATE')       or define('PATH_TEMPLATE', APPLICATION_PATH . 'views');
defined('DIRS')                or define('DIRS', '/');

//类文件扩展名
const EXT = '.class.php';

//包含配置文件
require APPLICATION_PATH . 'common/function.php';
$config = APP_PATH . 'config/config.php';
$db     = APP_PATH . 'config/db.php';
if (is_file($config)){
    C(load_config($config));
}

if (is_file($db)){
    C(load_config($db));
}

//包含核心框架类
require FRAME_ROOT . '/Core.php';

ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', DOMAIN); //
ini_set('session.cookie_lifetime', '86400');

session_start();

//实例化核心类
$fast = new Fast();
$fast->run();