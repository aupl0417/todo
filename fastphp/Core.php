<?php 

/*
	FastPHP核心框架
*/

class Fast{
	
	//运行程序
	public function run(){
		spl_autoload_register(array($this, 'loadClass'));
		$this->setReporting();
		$this->removeMagicQuotes();
		$this->unregisterGlobals();
		$this->callHook();
	}
	
	// 主请求方法，主要目的是拆分URL请求
	function callHook(){
		$action 	 = '';
		$queryString = '';
		// var_dump($_GET);die;
		if(!empty($_SERVER['PHP_SELF'])){
			$url = $_SERVER['PHP_SELF'];
			$urlArray = explode('/', trim($url, '/'));
//			array_shift($urlArray);

			//获取控制器名
            if(strpos($urlArray[0], '.php') !== false){
                array_shift($urlArray);
            }

			$controllerName = ucfirst(empty($urlArray[0]) ? 'Index' : $urlArray[0]);
			$controller     = $controllerName . 'Controller';

			//获取动作名
			array_shift($urlArray);
			$action = empty($urlArray[0]) ? 'index' : $urlArray[0];

			//获取URL参数
			array_shift($urlArray);
			// var_dump($urlArray);die;
			$queryString = empty($urlArray) ? array() : $urlArray;
		}
		
		//数据为空的处理
		$controller  = $controller ? $controller : 'Index';
		$action 	 = $action ? $action : 'index';
		$queryString = $queryString ? $queryString : [];

		//实例化控制器
		$int = new $controller($controllerName, $action, $queryString);

		//如果控制器和动作都存在，则调用并传入URL参数
		if(method_exists($controller, $action)){
			call_user_func_array(array($int, $action), $queryString);
		}else {
			exit($controller . '控制器不存在');
		}
	}
	
	//检测自定义全局变量（register globals）并移除
	function unregisterGlobals(){
		if(ini_get('register_globals')){
			$array = array('_SESSION', '_POST', '_GET', '_COOKIE', '_REQUEST', '_SERVER', '_ENV', '_FILES');
			foreach($array as $value){
				foreach($GLOBALS[$value] as $key=>$val){
					if($val === $GLOBALS[$key]){
						unset($GLOBALS[$key]);
					}
				}
			}
		}
	}
	
	//删除敏感字符
	private static function stripSlashesDeep($value){
		return is_array($value) ? array_map('stripslashes', $value) : stripslashes($value);
	}
	
	//检测敏感字符并删除
	function removeMagicQuotes(){
		if(get_magic_quotes_gpc()){
			$_GET     = self::stripSlashesDeep($_GET);
			$_POST    = self::stripSlashesDeep($_POST);
			$_COOKIE  = self::stripSlashesDeep($_COOKIE);
			$_SESSION = self::stripSlashesDeep($_SESSION);
		}
	}
	
	//检测开发环境
	function setReporting(){
		if(APP_DEBUG == true){
			error_reporting(E_ALL);
			ini_set('display_errors', 'on');
		}else {
			error_reporting(E_ALL);
			ini_set('display_errors', 'Off');
			ini_set('log_errors', 'On');
			ini_set('error_log', RUNTIME_PATH . '/logs/error.log');
		}
	}
	
	static function loadClass($class){
		$frameWorks  = FRAME_ROOT . $class . EXT;
		$controllers = CONTROLLER_PATH . $class . EXT;
		$models      = MODEL_PATH . $class . EXT;

		if(file_exists($frameWorks)){
			include $frameWorks;
		}else if(file_exists($controllers)){
			include $controllers;
		}else if(file_exists($models)){
			include $models;
		}else {
			die($class . '类不存在');
		}		
	}
}