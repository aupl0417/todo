<?php 
/*
	视图基类
*/

class View {
	
	protected $_controller;
	protected $_action;
	protected $variables = array();
	
	function __construct($controller, $action){
		$this->_controller = $controller;
		$this->_action     = $action;
	}
	
	function set($name, $value){
		$this->variables[$name] = $value;
	}
	
	function get(){
		
	}
	
	function render(){
		extract($this->variables);
		$defaultHeader = APPLICATION_PATH . 'views/header.php';
		$defaultFooter = APPLICATION_PATH . 'views/footer.php';
		$controllerHeader = APPLICATION_PATH . 'views/' . $this->_controller . '/header.php';
		$controllerFooter = APPLICATION_PATH . 'views/' . $this->_controller . '/footer.php';
		
		//页头文件
		if(file_exists($controllerHeader)){
			include($controllerHeader);
		}else {
			include($defaultHeader);
		}

		if(!file_exists(APPLICATION_PATH . 'view/' . $this->_controller . '/' . $this->_action . '.php')){
            die('文件：' . 'view/' . $this->_controller . '/' . $this->_action . '.php不存在');
        }
		
		//页面内容
		include(APPLICATION_PATH . 'view/' . $this->_controller . '/' . $this->_action . '.php');
		
		//页脚文件
		if(file_exists($controllerFooter)){
			include($controllerFooter);
		}else {
			include($defaultFooter);
		}
	}
	
	function __destruct(){
		
	}
	
}