<?php 

class Model extends Sql {
	
	protected $_model;
	protected $_table;
	protected $data;
	// 查询表达式参数
    protected $options          =   array();
	protected $methods          =   array('strict','order','alias','having','group','lock','distinct','auto','filter','validate','result','token','index','force');
	
	function __construct(){
		//连接数据库
		$this->connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PREFIX, 'utf8');
		
		//转换模型 +Model为模型名称
		
		//获取对象所属类名称
		$this->_model = get_class($this);
		$this->_model = rtrim($this->_model, 'Model');
		
		//数据库表名与类名一致
		$this->_table = strtolower($this->_model);
		
	}

	public function data($data = ''){
		if($data === '' && !empty($this->data)){
			return $this->data;
		}
		if(is_object($data)){
			$data = get_object_vars($data);
		}elseif(is_string($data)){
			parse_str($data, $data);
		}elseif(!is_array($data)){
			die('数据非法');
		}
		$this->data = $data;
		return $this;
	}

	public function table($table = ''){
		$prefix = $this->_prefix;
		if(empty($table)){
			$table = $this->_table;
		}
		if(!is_array($table)){
			$table = explode(',', $table);
		}
		$this->options['table'] = $table;
		return $this;
	}

	/*
	* 指定查询条件
	* @access public
	* @param mixed $where 条件表达式
	* @return Model
	*/
	public function where($where){
		if(is_string($where)){
			$where = explode(',', $where);
		}elseif(is_object($where)){
			$where = get_object_vars($where);
		}

		$map = array();
		$map['_string'] = $where;
		$where = $map;
		if(isset($this->options['where'])){
			$this->options['where'] = array_merge($this->options['where'], $where);
		}else {
			$this->options['where'] = $where;
		}
		return $this;

	}

	public function __call($method, $args){
		if(in_array(strtolower($method, $this->methods, true))){
			$this->options[strtolower($method)] = $args[0];
			return $this;
		}
	}

	function __destruct(){

	}
	
}