<?php 

class Sql {
	
	protected $_linkID;
	protected $_version;
	protected $_result;
	protected $_prefix;//表前缀
	protected $_query;
	protected $_lastId;
	protected $_tables = array(); //数据表集
	protected $comparison = array('eq'=>'=','neq'=>'<>','gt'=>'>','egt'=>'>=','lt'=>'<','elt'=>'<=','notlike'=>'NOT LIKE','like'=>'LIKE','in'=>'IN','notin'=>'NOT IN');
	protected $selectSql  = 'SELECT%DISTINCT% %FIELD% FROM %TABLE%%FORCE%%JOIN%%WHERE%%GROUP%%HAVING%%ORDER%%LIMIT% %UNION%%LOCK%%COMMENT%';
	
	//连接数据库
	function connect($db_host, $db_user, $db_pwd, $db_name, $db_prefix, $db_charset = 'utf8', $pconnect = 0){
		if($pconnect){
			if(!$this->_linkID = @mysql_pconnect($db_host, $db_user, $db_pwd)){
				$this->errorMsg();
			}
		}else {
			if(!$this->_linkID = @mysql_connect($db_host, $db_user, $db_pwd, 1)){
				$this->errorMsg();
			}
		}
		
		$this->_prefix = $db_prefix;
		$this->_version = mysql_get_server_info ( $this->_linkID );
		
		if($this->getVersion() > '4.1'){
			if($db_charset){
				mysql_query('SET character_set_connection=' . $db_charset . ',character_set_results=' . $db_charset . ',character_set_client=binary', $this->_linkID);
			}
			
			if($this->getVersion() > '5.0.1'){
				mysql_query('SET sql_model=""', $this->_linkID);
			}
		}
		
		if(!mysql_select_db($db_name, $this->_linkID)){
			$this->errorMsg();
		}
	}
	
	//断开数据库
	function disconnect(){
		if(@mysql_close($this->_linkID)){
			return true;
		}
		
		return false;
	}
	
	//添加一条数据
	function add(array $data){
		if(!is_array($data)){
			return false;
		}
		
		$fieldString = '';
		$valueString = '';
		foreach($data as $key=>$value){
			$fieldString .= "`{$key}`,";
			$valueString .= "'{$value}',";
		}
		$fieldString = rtrim($fieldString, ',');
		$valueString = rtrim($valueString, ',');
		
		$query = "INSERT INTO `{$this->_prefix}{$this->_table}` ({$fieldString}) VALUES ({$valueString})";
		
		return $this->query($query);
	}
	
	/*
	* 查询数据
	* @params $field
	*/
	function select($field = '*', $where = '1', $join = array(), $order = '', $limit = ''){
		// $field       = $this->getFieldString($field);
		// $whereString = $this->getWhereString($where);
		// $joinString  = $this->getJoinString($join);
		$field       = $this->parseField($field);
		$whereString = $this->parseWhere($where);
		$joinString  = $this->parseJoin($join);
		$order       = $this->parseOrder($order);
		$limit		 = $this->parseLimit($limit);
		
		$query = "select {$field} from `{$this->_prefix}{$this->_table}` {$joinString} {$whereString} {$order} {$limit}";
		
		if($res = $this->query($query)){
			$result = array();
			$table  = array();
			$field  = array();
			$tempResults = array();
			$numOfFields = mysql_num_fields($res);
			
			for($i = 0; $i < $numOfFields; ++$i){
				array_push($table, mysql_field_table($res, $i));
				array_push($field, mysql_field_name($res, $i));
			}
			$this->_tables = array_unique($table);
			while($row = mysql_fetch_row($res)){
				$tempResults = array_combine($field,  $row);
				array_push($result, $tempResults);
			}
			
			mysql_free_result($res);
			
			return $result;
		}
		
		return false;
	}
	
	function getField($field, $where, $join = array(), $order = '', $limit = ''){
		
		$res = $this->getTableField();
		
		return $res;
	}
	
	/*
	* 更新数据
	* @params $data mixed(string/array)
	* return bool
	*/
	function save($data, $where = ''){
		
		if(!$where || $where == '1'){//$where 为空，不允许更新记录
			return false;
		}
		
		$fields = $this->getTableField();
		$message = '';
		$updateString = '';
		if(is_array($data)){
			foreach($data as $key=>$value){
				if(!array_key_exists($key, $fields)){
					$message .= $key . ',';
				}else{
					$updateString .= "`{$key}`='{$value}',";
				}
			}
			if(!empty($message)){
				$this->errorMsg($message . '字段不存在');
			}
			$updateString = rtrim($updateString, ',');
		}else if(is_string($data)) {
			$updateString = $data;
		}
		
		$whereString = $this->getWhereString($where);
		
		$query = "UPDATE `{$this->_prefix}{$this->_table}` set {$updateString} {$whereString}";
		return $this->query($query);
	}
	
	/*
	* 获取一行
	* @params $field mixed(string/array)
	* return array
	*/
	function find($field = '', $where, $join = array()){
		$field = $this->parseField($field);
		$whereString = $this->parseWhere($where);
		$joinString  = $this->parseJoin($join);
		$sql = "select {$field} from `{$this->_prefix}{$this->_table}` {$joinString} {$whereString} limit 1";
		
		if(!$res = $this->query($sql)){
			return false;
		}
		
		return mysql_fetch_assoc($res);
	}
	
	function execute($sql){
		if($res = $this->query($sql)){
			if(preg_match('/select/i', $sql)){
				$result = array();
				while($row = mysql_fetch_row($res)){
					$result[] = $row;
				}
				
				mysql_free_result($res);
				return $result;
			}
		}
		
		return $res;
	}
	
	
	function getRowsNum() {
        $query = $this->query ($this->_query);
        return mysql_num_rows ($query);
    }
	
	
	/*
	* 删除记录
	* @params $where mixed (array/string)
	*/
	function delete($where){
		if(empty($where)){ //where 条件为空，不允许删除
			return false;
		}
		
		$whereString = $this->getWhereString($where);
		$query = "DELETE FROM `{$this->_prefix}{$this->_table}` {$whereString}";
		
		return $this->query($query);
	}
	
	//查询
	function query($query, $singleResult = 0){
		$this->_query = $query;
		if(!($this->_result = @mysql_query($query, $this->_linkID))){
			$this->errorMsg();
			return false;
		}
		
		return $this->_result;
	}
	
	function getLastSql(){
		return $this->_query;
	}
	
	/*
	* 处理查询表连接
	* @param $join mixed(string/array)
	* return string
	*/
	function getJoinString($join){
		$joinString = '';
		if($join){
			$joinString = ' ' . implode(' ', $join) . ' ';
		}
		
		return $joinString;
	}
	
	/*
	* 处理查询字段
	* @param $field mixed(string/array)
	* return string
	*/
	function getFieldString($field = '*'){
		if(is_array($field)){
			$field = explode(',', $field);
		}
		
		return $field;
	}
	
	
	function getLastId(){
		return mysql_insert_id ( $this->link_id );
	}
	
	/*
	* 处理查询条件
	* @param $where mixed(string/array)
	* return string
	*/
	function getWhereString($where){
		$whereString = ' WHERE ';
		$expArray = array(
			'eq' => '=', 'neq' => '<>', 'gt' => '>', 'egt' => '>=', 'lt' => '<', 'elt' => '<=',
			'like' => ' like ', 'between' => '', 'not between' => '', 'in' => '', 'not in' => '', '_string' => ''
		);
		if(is_array($where)){
			$_logic = 'AND';
			if(array_key_exists('_logic', $where)){
				$_logic = strtolower($where['_logic']) == 'or' ? 'OR' : 'AND';
				unset($where['_logic']);
			}
			foreach($where as $key=>$value){
				if(is_array($value)){
					$exp = strtolower($value[0]);//表达式符号
					if(array_key_exists($exp, $expArray)){
						if(!$expArray[$exp] && in_array($exp, array('in', 'not in'))){
							$whereString .= "(`{$key}` {$value[0]} (" . (is_array($value[1]) ? explode(',', $value[1]) : $value[1]) . ")) {$_logic} ";
						}else if(!$expArray[$exp] && in_array($exp, array('between', 'not between'))){//处理between  和 not between 两种情况
							$whereString .= "(`{$key}` {$value[0]} ";
							if(is_array($value[1])){
								$whereString .= min($value[1]) . ' and ' . max($value[1]) . ") {$_logic} ";
							}else {
								$betArr = explode(',', $value[1]);
								$whereString .= min($betArr) . ' and ' . max($betArr) . ") {$_logic} ";
							}
						}else if($expArray[$exp]){
							$whereString .= "(`{$key}`{$expArray[$exp]}'{$value[1]}') {$_logic} ";
						}
					}
				}else if(in_array(strtolower($key), array('_string')) && !is_array($value)){
					$whereString .= '(' . $value . ') ' . $_logic . ' ';
				}else {
					$whereString .= "`{$key}`='" . $value . "' {$_logic} ";
				}
			}
			
			$whereString = trim($whereString);
			$whereString = rtrim($whereString, $_logic);//去除多余的 OR 和 AND
		}else {
			$whereString .= $where;
		}
		
		return $whereString;
	}
	
	//获得查询表字段
	function getTableField(){
		if($this->_tables){//如果表集不为空，则返回
			return $this->_field;
		}
		
		$query = "DESC `{$this->_prefix}{$this->_table}`";
		$res = $this->query($query);
		
		if(!$res){
			return false;
		}
		
		$array = array();
		
		while($row = mysql_fetch_row($res)){
			$array[] = $row[0];
		}
		
		return $array;
	}
	
	function getVersion(){
		return $this->_version;
	}
	
	function errorMsg($message = ''){
		if(!$message){
			$message = @mysql_error();
		}
		
		die($message);
	}
	
	/*
	* value 分析
	* @access protected
	* @param mixed $value
	* @return string
	*/
	protected function parseValue($value){
		if(is_string($value)){
			$value = "'" . $this->escapeString($value) . "'";
		}elseif(isset($value[0]) && is_string($value[0]) && strtolower($value[0]) == 'exp'){//表达式
			$value = $this->escapeString($value[1]);
		}elseif(is_array($value)){
			$value = array_map(array($this, 'parseValue'), $value);
		}elseif(is_bool($value)){
			$value = $value ? '1' : '0';
		}elseif(is_null($value)){
			$value = 'null';
		}
		
		return $value;
	}
	
	/*
	* field分析
	* @access protected
	* @param mixed $fields
	* @return string
	*/
	protected function parseField($fields){
		if(is_string($fields) && $fields !== ''){
			$fields = explode(',', $fields);
		}
		if(is_array($fields)){
			$array = array();
			foreach($fields as $key=>$field){
				if(!is_numeric($key)){
					$array[] = $this->parseKey($key) . ' AS ' . $this->parseKey($field);
				}else {
					$array[] = $this->parseKey($field);
				}
			}
			$fieldsStr = implode(',', $array);
		}else {
			$fieldsStr = '*';
		}
		
		return $fieldsStr;
	}
	
	/*
	* order 分析
	* @access protected
	* @param mixed $order
	* @return string
	*/
	protected function parseOrder($order){
		if(is_array($order)){
			$array = array();
			foreach($order as $key=>$val){
				if(is_numeric($key)){
					$array[] = $this->parseKey($val);
				}else {
					$array[] = $this->parseKey($key) . ' ' . $val;
				}
			}
			$order = implode(',', $array);
		}
		
		return $order ? ' ORDER BY ' . $order : '';
	}
	
	/*
	* limit 分析
	* @access protected
	* @param mixed $limit
	* @return string
	*/
	protected function parseLimit($limit){
		return $limit ? ' LIMIT ' . $limit . ' ' : '';
	}
	
	/*
	* where 分析
	* @access protected
	* @param mixed $where
	* @return string
	*/
	protected function parseWhere($where){
		$whereStr = '';
		if(is_string($where)){
			$whereStr = $where;
		}else {
			$operate = isset($where['_logic']) ? strtoupper($where['_logic']) : '';
			if(in_array($operate, array('OR', 'AND', 'XOR'))){
				$operate = ' ' . $operate . ' ';
				unset($where['_logic']);
			}else {
				$operate = ' AND ';
			}
			foreach($where as $key=>$val){
				if(is_numeric($key)){
					$key = '_complex';
				}
				if(0 === strpos($key, '_')){
					$whereStr .= $this->parseThinkWhere($key, $val);
				}else {
					$key = trim($key);
					$whereStr .= $this->parseWhereItem($this->parseKey($key), $val);
				}
				$whereStr .= $operate;
			}
			$whereStr = substr($whereStr, 0, -strlen($operate));
		}
		return empty($whereStr) ? '' : ' WHERE ' . $whereStr;
	}
	
	/*
	* join 分析
	* @access protected
	* @param mixed $join
	* @return string
	*/
	protected function parseJoin($join){
		$joinString = '';
		if($join){
			$joinString = ' ' . implode(' ', $join) . ' ';
		}
		
		return $joinString;
	}
	
	protected function parseWhereItem($key, $val){
		$whereStr = '';
		if(is_array($val)){
			if(is_string($val[0])){
				if(preg_match('/^(EQ|NEQ|GT|EGT|LT|ELT)$/i', $val[0])){ //比较运算
					$whereStr .= $key . ' ' . $this->comparison[strtolower($val[0])] . ' ' . $this->parseValue($val[1]);
				}elseif(preg_match('/^(NOTLIKE|LIKE)$/i', $val[0])){//模糊查找
					if(is_array($val[1])){
						$likeLogic = isset($val[2]) ? strtoupper($val[2]) : 'OR';
						if(in_array($likeLogic, array('AND', 'OR', 'XOR'))){
							$likeStr = $this->comparison[strtolower($val[0])];
							$like = array();
							foreach($val[1] as $item){
								$like[] = $key . ' ' . $likeStr . ' ' . $this->parseValue($item);
							}
							$whereStr .= '(' . implode(' ' . $likeLogic . ' ', $like) . ')';
						}
					}else {
						$whereStr .= $key . ' ' . $this->comparison[strtolower($val[0])] . ' ' . $this->parseValue($val[1]);
					}
				}elseif('exp' == strtolower($val[0])){//表达式
					$whereStr .= $key . ' ' . $val[1];
				}elseif(preg_match('/IN/i', $val[0])){//IN
					if(isset($val[2]) && 'exp' == $val[2]){
						$whereStr .= $key . ' ' . strtolower($val[0]) . ' ' . $val[1];
					}else {
						if(is_string($val[1])){
							$val[1] = explode(',', $val[1]);
						}
						$zone 		= implode(',', $this->parseValue($val[1]));
						$whereStr  .= $key . ' ' . strtoupper($val[0]) . ' (' . $zone . ')';
					}
				}elseif(preg_match('/BETWEEN/i', $val[0])){
					$data 		= is_string($val[1]) ? explode(',', $val[1]) : $val[1];
					$whereStr  .= $key . ' ' . strtolower($val[0]) . ' ' . $this->parseValue($data[0]) . ' AND ' . $this->parseValue($data[1]);
				}
			}
		}else {
			$whereStr .= $key . ' = ' . $this->parseValue($val);
		}
		
		return $whereStr;
	}
	
	protected function parseThinkWhere($key, $val){
		$whereStr = '';
		switch($key){
			case '_string':
				$whereStr = $val;
				break;
			case '_complex':
				$whereStr = substr($this->parseWhere($val), 6);
				break;
			case '_query':
				parse_str($val, $where);
				if(isset($where['_logic'])){
					$op = ' ' . strtoupper($where['_logic']) . ' ';
					unset($where['_logic']);
				}else {
					$op = ' AND ';
				}
				$array = array();
				foreach($where as $field=>$data){
					$array[] = $this->parseKey($field) . ' = ' . $this->parseValue($data);
				}
				$whereStr = implode($op, $array);
				break;
		}
		return '( ' . $whereStr . ' )';
	}
	
	protected function parseKey(&$key){
		$key = trim($key);
		
		if(!is_numeric($key) && !preg_match('/[,\'\"\*\(\)`.\s]/',$key)) {
           $key = '`' . $key . '`';
        }
		
		return $key;
	}
	
	
	
	protected function escapeString($value){
		return addslashes($value);
	}
	
	
	
	
	
	
}