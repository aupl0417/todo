<?php

/* ClassName: MySql
 * Memo:PDO class
 * Version:2.1.1
 * EditTime:2015-11-08
 * Writer:aupl
 * */

class MySql {

    private $linkID = NULL;
    private $dsn = '';
    private $dbms = '';
    private $dbHost = '';
    private $dbPort = '3306';
    private $dbUser = '';
    private $dbPwd = '';
    private $dbName = '';
    private $dbPrefix = 'fly_';
    private $result = NULL;
    private $queryString = '';
    private $pconnect = false;
    private $hasActiveTransaction = false;
    private $useCache = false;
    private $rowCache = null;
    private $allCache = null;
    private $fieldCache = null;
    private $mem = null;
    private $debug =false;

    public function __construct($dbname = NULL) {
		$dbname = is_null($dbname) ? C('DB_NAME') : $dbname;
        $this->setSource(C('DB_TYPE'), C('DB_HOST'), C('DB_PORT'), C('DB_USER'), C('DB_PWD'), $dbname, C('DB_PREFIX'));
        $this->open();
    }

    public function __destruct() {
        $this->linkID = null;
    }

    //设置数据源
    public function setSource($dbms, $host, $dbPort = '3306', $username, $pwd, $dbname, $dbprefix = 'fly_') {
        $this->dbms   = $dbms;
        $this->dbHost = $host;
        $this->dbPort = $dbPort;
        $this->dbUser = $username;
        $this->dbPwd  = $pwd;
        if ($this->dbName == '') {
            $this->setDB($dbname);
        }
        $this->dbPrefix    = $dbprefix;
        $this->dsn         = $this->dbms . ":host=" . $this->dbHost . ";port=" . $this->dbPort . ";dbname=" . $this->dbName;
        $this->result      = null;
        $this->linkID      = null;
        $this->queryString = '';
    }

    //设置数据库
    public function setDB($dbname) {
        $this->dbName = $dbname;
    }

    //打开数据连接
    public function open() {
        try {
            $this->linkID = new PDO($this->dsn, $this->dbUser, $this->dbPwd, array(PDO::ATTR_PERSISTENT => $this->pconnect));
            $this->linkID->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->linkID->setAttribute(PDO::ATTR_AUTOCOMMIT, true);
            $this->linkID->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
            $this->linkID->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            //$this->linkID->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
            $this->linkID->query("SET NAMES '" . DB_CHARSET . "'");
        } catch (PDOException $e) {
            return $this->getError($e->getMessage(), false);
        }
    }

    //关闭链接
    public function close() {
        $this->linkID = null;
    }

    public function debug(){
        $this->debug =true;
        return $this;
    }

    /**
     *  设置是否使用缓存及缓存类型
     *
     *  @param     boolean	$useCache    是否使用缓存	
     *  @param     string	$cacheType   缓存类型		
     *  @return    obj			  	    
     */
    public function cache($useCache = true) {
        $this->useCache = $useCache;
        if ($useCache) {
            $this->mem = new cache();
        }
        return $this;
    }

    /**
     *  写入缓存
     *
     *  @param     string	$sql    sql		
     *  @return    obj			  	    
     */
    public function setCache($data, $type = '') {
        $key = md5($this->queryString);
        $key .= $type;

        $this->mem->set($key, $data);
    }

    //执行不返回记录集，只返回影响的记录数
    public function exec($sql = '') {
        $this->setQuery($sql);

        try {
            return $this->linkID->exec($this->queryString);
        } catch (PDOException $e) {
            return $this->getError($e->getMessage());
        }
    }

    /**
     *  获取字段值,如果sql中有多个字段,只返回第一个字段的值
     *  @param     string	$sql       查询的sql语句      
     *  @return        
     */
    public function getField($sql = '') {
        $this->setQuery($sql);
        try {
            switch ($this->useCache) {
                case true:
                    $this->fieldCache = $this->mem->get(md5($this->queryString) . '_field');
                    if ($this->fieldCache) {//如果有缓存
                        $result = $this->fieldCache;
                        break;
                    }
                case false:
                default:
                    $this->result = $this->linkID->query($this->queryString);
                    $result = $this->result->fetchColumn();
                    $this->useCache && $this->setCache($result, '_field');
                    break;
            }
            return $result;
        } catch (PDOException $e) {
            return $this->getError($e->getMessage());
        }
    }

    /**
     *  查询数据 - 单行
     *  @param     string	$sql       查询的sql语句      
     *  @return    array    
     */
    public function getRow($sql = '', $mode = PDO::FETCH_ASSOC) {
        $this->setQuery($sql);
        try {
            switch ($this->useCache) {
                case true:
                    $this->rowCache = $this->mem->get(md5($this->queryString) . '_row');
                    if ($this->rowCache) {//如果有缓存						
                        $result = $this->rowCache;
                        break;
                    }
                case false:
                default:
                    $this->result = $this->linkID->query($this->queryString);
                    $this->result->setFetchMode($mode);
                    $result = $this->result->fetch();
                    $this->useCache && $this->setCache($result, '_row');
                    break;
            }
            return ($result) ? $result : array();
        } catch (PDOException $e) {
            return $this->getError($e->getMessage());
        }
    }

    /**
     *  查询数据 - 多行

     *  @return    array    
     */
    public function getAll($sql = '', $mode = PDO::FETCH_ASSOC) {
        $this->setQuery($sql);
        try {
            switch ($this->useCache) {
                case true:
                    $this->allCache = $this->mem->get(md5($this->queryString) . '_all');
                    if ($this->allCache) {//如果有缓存
                        $result = $this->allCache;
                        break;
                    }
                case false:
                default:
                    $this->result = $this->linkID->query($this->queryString);
                    $this->result->setFetchMode($mode);
                    $result = $this->result->fetchAll();
                    $this->useCache && $this->setCache($result, '_all');
                    break;
            }
            return ($result) ? $result : array();
        } catch (PDOException $e) {
            return $this->getError($e->getMessage());
        }
    }

    /**
     *  查询数据
     * 
     *  @param     boolean	$returnRow true:getRow; false:getAll      
     *  @return    array    
     */
    public function get($sql = '', $returnRow = false, $mode = PDO::FETCH_ASSOC) {
        return ($returnRow) ? $this->getRow($sql, $mode) : $this->getAll($sql, $mode);
    }

    //最后ID
    public function getLastID() {
        return $this->linkID->lastInsertId();
    }

    //SQL安全过滤
    public function setQuery($sql = '') {
        if ($sql == '') {
            $this->getError('Sql为空', false);
        }
        $prefix = "#@_@__";
        $sql = trim($sql);
        $inQuote = false;
        $escaped = false;
        $quoteChar = "";
        $n = strlen($sql);
        $np = strlen($prefix);
        $restr = "";
        $j = 0;
        for (; $j < $n; ++$j) {
            $c = $sql [$j];
            $test = substr($sql, $j, $np);
            if (!$inQuote) {
                if ($c == "\"" || $c == "'") {
                    $inQuote = true;
                    $escaped = false;
                    $quoteChar = $c;
                }
            } else if ($c == $quoteChar && !$escaped) {
                $inQuote = false;
            } else if ($c == "\\" && !$escaped) {
                $escaped = true;
            } else {
                $escaped = false;
            }
            if ($test == $prefix && !$inQuote) {
                $restr .= $this->dbPrefix;
                $j += $np - 1;
            } else {
                $restr .= $c;
            }
        }
		if(preg_match("/TRUNCATE|INFORMATION_SCHEMA|ALTER|'\s*;/i", $restr)) {
			$this->getError('Danger!', false);
		}
		if ($this->debug){
		    echo $restr.PHP_EOL;
        }
        $this->queryString = $restr;
    }

    //释放记录集
    public function freeResultAll() {
        $this->result = NULL;
    }

    //返回记录集对象
    public function getResult() {
        return $this->result;
    }

    //事务处理
    public function beginTRAN() {
        if ($this->hasActiveTransaction) {
            return false;
        } else {
            $this->hasActiveTransaction = $this->linkID->beginTransaction();
        }
        return $this->hasActiveTransaction;
    }

    public function commitTRAN() {
        $result = $this->linkID->commit();
        $this->hasActiveTransaction = false;
		return $result;
    }

    public function rollBackTRAN() {
        $this->linkID->rollback();
        $this->hasActiveTransaction = false;
    }

    //执行存储过程
    public function execProcedure($pname, $vartab = '', $mode = PDO::FETCH_NUM) {
        if (is_array($vartab)) {
            $var = '';
            foreach ($vartab as $v) {
                $var .= "'{$v}'" . ",";
            }
            $var = trim($var, ',');
        } else {
            $var = $vartab;
        }
        $sql = "CALL {$pname}({$var});";
        return $this->getAll($sql, $mode);
    }

    //执行函数
    public function execFunction($fname, $vartab = '') {
        if (is_array($vartab)) {
            $var = '';
            foreach ($vartab as $v) {
                $var .= "'{$v}'" . ",";
            }
            $var = trim($var, ',');
        } else {
            $var = '';
        }
        $sql = "SELECT {$fname}({$var})";
        return $this->getField($sql);
    }

    /**
     *  插入数据
     *
     *  @param     string	$table    插入的表名
     *  @param     array	$vartab   插入单条数据:array('field1'=>$value1, 'field2'=>$value2, 'field3'=>$value3, ...);
     *  @return    number 		      成功:id
     * 								  失败:-1
     */
    public function insert($table, $vartab) {
        if (!is_array($vartab)) {
            return -1;
        }

        $field = $var = '';
        foreach ($vartab as $key => $value) {
            $field .= $key . ",";
            $var .= "'" . $value . "',";
        }
        $field = trim($field, ',');
        $var = '(' . trim($var, ',') . ')';

        $sql = "INSERT INTO {$table} ({$field}) VALUES {$var}";
        return $this->exec($sql);
    }

    /**
     *  插入多条数据
     *
     *  @param     string	$table    插入的表名
     *  @param     array	$fields   字段列表array('field1', 'field2', 'field3', ...);
     *  @param     array	$param2   数据array(array($value1, $value2, $value3, ...), array($value4, $value5, $value6, ...), ...);
     *  @return    array    成功:
     * 										插入的id数组:array(id1, id2, id3);
     * 								  失败:-1
     */
    public function inserts($table, $fields, $values) {
        if (!is_array($fields) || !is_array($values)) {
            return -1;
        }

        $var = "";

        $fields = implode(',', $fields);
        foreach ($values as $key => $val) {
            foreach ($val as $k => $v) {
                $var[$key][$k] = "'" . $v . "'";
            }
            $var[$key] = "(" . implode(',', $var[$key]) . ")";
        }
        $var = implode(',', $var);

        $sql = "INSERT INTO {$table} ({$fields}) VALUES {$var}";

        return $this->exec($sql);
    }

    /**
     *  Replace
     *
     *  @param     string	$table    表名
     *  @param     array	$vartab   array('field1'=>$value1, 'field2'=>$value2, 'field3'=>$value3, ...); 必须包含主键或者唯一索引
     *  @return    number 		      成功:id
     * 								  失败:-1
     */
//    public function replace($table, $vartab) {
//        return false; //不要
//        if (!is_array($vartab)) {
//            return -1;
//        }
//        $primary = $this->tableIndex($table);
//        if (array_intersect_key($vartab, array_flip($primary))) {//如果$vartab中包含了主键或者unique
//            $field = $var = '';
//            foreach ($vartab as $key => $value) {
//                $field .= $key . ",";
//                $var .= "'" . $value . "',";
//            }
//            $field = trim($field, ',');
//            $var = '(' . trim($var, ',') . ')';
//
//            $sql = "REPLACE INTO {$table} ({$field}) VALUES {$var}";
//
//            return $this->exec($sql);
//        } else {
//            return -1;
//        };
//    }

    /**
     *  修改数据
     *
     *  @param     string	$table    修改的表名
     *  @param     array	$vartab   修改的数据:array('field1'=>$value1, 'field2'=>$value2, 'field3'=>$value3, ...);	 *								 
     *  @param     string	$where    修改条件
     *  @return    number			  修改的行数	    
     */
    public function update($table, $vartab, $where = '') {
        if (!is_array($vartab)) {
            return -1;
        }
        $str = '';
        foreach ($vartab as $key => $value) {
            if (is_null($value)) {
                $str .= "{$key}=NULL,";
            } else {
                $str .= "{$key}='{$value}',";
            }
        }

        $str = trim($str, ",");
        $sql = "UPDATE {$table} SET " . $str . " WHERE " . $where;
        //echo $sql;
        return $this->exec($sql);
    }

    //获取最后一次查询语句 
    public function lastSql() {
        return $this->queryString;
    }

    //删除
    public function delete($table, $where = '') {
        return $this->exec("DELETE FROM {$table} WHERE " . $where);
    }

    //数据库中是否存在表
    public function isTable($tbname) {
        $this->setQuery($tbname);
        $tbname = $this->queryString;

        $row = $this->getAll("SHOW TABLES", PDO::FETCH_NUM);
        
        foreach ($row as $v) {
            if ($v[0] == $tbname) {
                return true;
            }
        }
        return false;
    }

    /**
     *  查询表的主键和unique索引
     *
     *  @param     string	$table    表名		
     *  @return    array			  	    
     */
    public function tableIndex($tbname) {
        $idx = array();
        $sql = "SHOW INDEX FROM " . $tbname;
        $columns = $this->getAll($sql);
        foreach ($columns as $column) {
            if ($column['Key_name'] == 'PRIMARY' || $column['Non_unique'] == '0') {
                $idx[] = $column['Column_name'];
            }
        };
        return $idx;
    }

    //数据库版本
    public function getVersion() {
        return $this->execFunction("VERSION");
    }

    //表字段
    public function getTableFields($tbname) {
        return $this->getAll("DESCRIBE {$tbname}");
    }

    //获得错误信息
    public function getError($e) {
        if ($e !== '') {
            exit($e . ' db error! ');
        }
    }

    //统计查询的数量
//    public function count($tab, $where = '1') {
//        return $this->getField('select count(*) as num from ' . $tab . ' where ' . $where);
//    }

}

?>
