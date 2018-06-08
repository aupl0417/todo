<?php

function M($tableName = ''){
    $model = new Model();
	if(empty($tableName)){
	    return $model;
    }

    static $_model  = array();
    if (!isset($_model[$tableName])){
        $_model[$tableName] = $model->table($tableName);
    }

    return $_model[$tableName];
}

function D($modelName){
    $modelName .= 'Model';
    $model = new Model();
    if(empty($modelName)){
        return $model;
    }

    static $_model  = array();
    if (!isset($_model[$modelName])){
        $_model[$modelName] = new $modelName;
    }

    return $_model[$modelName];
}

//得到毫秒级时间戳(12位)
function getMtID() {
    return sprintf('%012o', getMicrotime());
}

//得到唯一id
function getGID($len = 32) {
    return substr(md5(getMtID() . rand(0, 1000)), 0, $len);
}

//得到毫\微秒级时间
function getMicrotime() {
    list($usec, $sec) = explode(" ", microtime());
    return sprintf('%s%03d', date('YmdHis',$sec),$usec * 1000);
}

/**
 * 浏览器友好的变量输出
 * @param mixed $var 变量
 * @param boolean $echo 是否输出 默认为True 如果为false 则返回输出字符串
 * @param string $label 标签 默认为空
 * @param boolean $strict 是否严谨 默认为true
 * @return void|string
 */
function dump($var, $echo=true, $label=null, $strict=true) {
    $label = ($label === null) ? '' : rtrim($label) . ' ';
    if (!$strict) {
        if (ini_get('html_errors')) {
            $output = print_r($var, true);
            $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
        } else {
            $output = $label . print_r($var, true);
        }
    } else {
        ob_start();
        var_dump($var);
        $output = ob_get_clean();
        if (!extension_loaded('xdebug')) {
            $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);
            $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
        }
    }
    if ($echo) {
        echo($output);
        return null;
    }else
        return $output;
}

/**
 * 获取输入参数 支持过滤和默认值
 * 使用方法:
 * <code>
 * I('id',0); 获取id参数 自动判断get或者post
 * I('post.name','','htmlspecialchars'); 获取$_POST['name']
 * I('get.'); 获取$_GET
 * </code>
 * @param string $name 变量的名称 支持指定类型
 * @param mixed $default 不存在的时候默认值
 * @param mixed $filter 参数过滤方法
 * @param mixed $datas 要获取的额外数据源
 * @return mixed
 */
function I($name,$default='',$filter=null,$datas=null) {
    if(strpos($name,'.')) { // 指定参数来源
        list($method,$name) =   explode('.',$name,2);
    }else{ // 默认为自动判断
        $method =   'param';
    }
	
    switch(strtolower($method)) {
        case 'get'     :   $input =& $_GET;break;
        case 'post'    :   $input =& $_POST;break;
        case 'put'     :   parse_str(file_get_contents('php://input'), $input);break;
        case 'param'   :
            switch($_SERVER['REQUEST_METHOD']) {
                case 'POST':
                    $input  =  $_POST;
                    break;
                case 'PUT':
                    parse_str(file_get_contents('php://input'), $input);
                    break;
                default:
                    $input  =  $_GET;
            }
            break;
        case 'path'    :   
            $input  =   array();
            if(!empty($_SERVER['PATH_INFO'])){
                $depr   =   URL_PATHINFO_DEPR;
                $input  =   explode($depr,trim($_SERVER['PATH_INFO'],$depr));            
            }
            break;
        case 'request' :   $input =& $_REQUEST;   break;
        case 'session' :   $input =& $_SESSION;   break;
        case 'cookie'  :   $input =& $_COOKIE;    break;
        case 'server'  :   $input =& $_SERVER;    break;
        case 'globals' :   $input =& $GLOBALS;    break;
        case 'data'    :   $input =& $datas;      break;
        default:
            return NULL;
    }
    if(''==$name) { // 获取全部变量
        $data       =   $input;
        array_walk_recursive($data,'filter_exp');
        $filters    =   isset($filter)?$filter: DEFAULT_FILTER;
        if($filters) {
            if(is_string($filters)){
                $filters    =   explode(',',$filters);
            }
            foreach($filters as $filter){
                $data   =   array_map_recursive($filter,$data); // 参数过滤
            }
        }
    }elseif(isset($input[$name])) { // 取值操作
        $data       =   $input[$name];
        is_array($data) && array_walk_recursive($data,'filter_exp');
        $filters    =   isset($filter)?$filter: DEFAULT_FILTER;
        if($filters) {
            if(is_string($filters)){
                $filters    =   explode(',',$filters);
            }elseif(is_int($filters)){
                $filters    =   array($filters);
            }
            
            foreach($filters as $filter){
                if(function_exists($filter)) {
                    $data   =   is_array($data)?array_map_recursive($filter,$data):$filter($data); // 参数过滤
                }else{
                    $data   =   filter_var($data,is_int($filter)?$filter:filter_id($filter));
                    if(false === $data) {
                        return isset($default)?$default:NULL;
                    }
                }
            }
        }
    }else{ // 变量默认值
        $data       =    isset($default)?$default:NULL;
    }
    return $data;
}

function array_map_recursive($filter, $data) {
    $result = array();
    foreach ($data as $key => $val) {
        $result[$key] = is_array($val)
         ? array_map_recursive($filter, $val)
         : call_user_func($filter, $val);
    }
    return $result;
}

// 过滤表单中的表达式
function filter_exp(&$value){
    if (in_array(strtolower($value),array('exp','or'))){
        $value .= ' ';
    }
}

/**
 * 获取和设置配置参数 支持批量定义
 * @param string|array $name 配置变量
 * @param mixed $value 配置值
 * @param mixed $default 默认值
 * @return mixed
 */
function C($name=null, $value=null,$default=null) {
    static $_config = array();
    // 无参数时获取所有
    if (empty($name)) {
        return $_config;
    }
    // 优先执行设置获取或赋值
    if (is_string($name)) {
        if (!strpos($name, '.')) {
            $name = strtoupper($name);
            if (is_null($value))
                return isset($_config[$name]) ? $_config[$name] : $default;
            $_config[$name] = $value;
            return null;
        }
        // 二维数组设置和获取支持
        $name = explode('.', $name);
        $name[0]   =  strtoupper($name[0]);
        if (is_null($value))
            return isset($_config[$name[0]][$name[1]]) ? $_config[$name[0]][$name[1]] : $default;
        $_config[$name[0]][$name[1]] = $value;
        return null;
    }
    // 批量设置
    if (is_array($name)){
        $_config = array_merge($_config, array_change_key_case($name,CASE_UPPER));
        return null;
    }
    return null; // 避免非法参数
}

/**
 * 加载配置文件 支持格式转换 仅支持一级配置
 * @param string $file 配置文件名
 * @param string $parse 配置解析方法 有些格式需要用户自己解析
 * @return array
 */
function load_config($file){
    if (is_file($file)){
        $ext  = pathinfo($file, PATHINFO_EXTENSION);
        switch($ext){
            case 'php':
                return include $file;
            case 'ini':
                return parse_ini_file($file);
            case 'yaml':
                return yaml_parse_file($file);
            case 'xml':
                return (array)simplexml_load_file($file);
            case 'json':
                return json_decode(file_get_contents($file), true);
            default:
                return "为找到对应解析器";
        }
    }else{
        return "未找到".$file."文件!";
    }

}

/**
 * 模板引擎U使用 {{ U('/couser/baseinfo?id=12#acher@edu.dttx.la','cate_id=1&status=1',true,true) }}
 * @param $url
 * @param $vars
 * @param $suffix
 * @param bool $domain
 * @return string
 */
function U($url='',$vars='',$suffix=false,$domain=false){
    if (empty($url)){
        return "链接地址不能为空!";
    }

    $parse_url = parse_url($url);
    $url = !empty($parse_url['path']) ? $parse_url['path']:'index';
    if (isset($parse_url['fragment'])){
        $fragment =$parse_url['fragment'];
        if(false !== strpos($fragment, '?')) { // 解析参数
            list($fragment, $parse_url['query']) = explode('?',$fragment,2);
        }
        if(false !== strpos($fragment, '@')) { // 解析域名
            list($fragment, $host)    =   explode('@',$fragment, 2);
        }
    }

    if (isset($host)){
        $domain = $host . (strpos($host,'.') ? '' : strstr($_SERVER['HTTP_HOST'],'.'));
    }elseif ($domain === true){
        $domain = $_SERVER['HTTP_HOST'];
    }

    if (is_string($vars) && !empty($vars)){
        parse_str($vars, $vars);
    }elseif (!is_array($vars)){
        $vars = array();
    }

    if (isset($parse_url['query'])){
        parse_str($parse_url['query'], $params);
        if (!empty($vars)){
            $vars = array_merge($params, $vars);
        }
    }

    if (URL_MODEL==0){
        if (!empty($vars)){
            $url = $url.'?'.http_build_query($vars, PHP_QUERY_RFC1738);
        }
        if (empty($vars)){
            $url = rtrim($url,'?');
        }
    }elseif (URL_MODEL==1){
        foreach ($vars as $k=>$item){
            if (!empty($vars[$k])){
                $url .= URL_PATHINFO_DEPR . $k . URL_PATHINFO_DEPR . $item;
            }
        }
        if ($suffix){
            $suffix = $suffix === true ? URL_HTML_SUFFIX : $suffix;
            if($pos = strpos($suffix, '|')){
                $suffix = substr($suffix, 0, $pos);
            }
            if($suffix && '/' != substr($url, -1)){
                $url  .=  '.'.ltrim($suffix, '.');
            }
        }
    }

    if(isset($fragment)){
        $url  .= '#'.$fragment;
    }
    if($domain) {
        $url   =  (is_ssl()?'https://':'http://').$domain.$url;
    }

    return $url;
}