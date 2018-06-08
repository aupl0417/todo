<?php 

class Controller {
	
	protected $_controller;
	protected $_action;
	protected $_view;
    protected $data = array();
    protected $templatepath = ''; //模板路径
    protected $protocal = 'http://'; //http(s)协议
    protected $temp_html = ''; //html代码
    protected $useformtoken = false; //使用form令牌
	
	function __construct($controller, $action, $param = array()){
		$this->_controller = $controller;
		$this->_action     = $action;

		if($param){
			foreach($param as $key=>$val){
				$key % 2 == 0 ? $data_key[] = $val : $data_value[] = $val;
			}
			$param = array_combine($data_key, $data_value);
		}

        if($_GET){
            $param = array_merge($param, $_GET);
        }
		
		if($_POST){
			$param = array_merge($param, $_POST);
		}
		
		if($_COOKIE){
			$param = array_merge($param, $_COOKIE);
		}
		
		if($_SESSION){
			$param = array_merge($param, $_SESSION);
		}
		
		if($_FILES){
			$param['Files'] = array_merge($param, $_FILES);
		}
		
		is_array($param) && array_walk_recursive($param, 'filter_exp');
		
		$filters = DEFAULT_FILTER ? DEFAULT_FILTER : 'htmlspecialchars,strip_tags';
		$filters = explode(',', $filters);
		foreach($filters as $filter){
			if(function_exists($filter)) {
                $param   =   is_array($param) ? array_map_recursive($filter, $param) : $filter($param); // 参数过滤
			}else{
				$param   =   filter_var($param, is_int($filter) ? $filter : filter_id($filter));
				if(false === $param) {
					return isset($default) ? $default : NULL;
				}
			}
		}
		
		$this->option       = $param;
        $this->templatepath = PATH_TEMPLATE;
        $this->_view        = new View($controller, $action);
        $this->protocal     = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? $this->protocal : 'http://';
	}
	
	function set($name, $value){
		$this->_view->set($name, $value);
	}
	
	function get(){
		
	}

    //设置模板文件
    public function setTemplateFile($tempfile = '') {
        die($this->templatepath);
        $this->templatefile = ($tempfile == '') ? "{$this->templatepath}/{$this->_controller}/{$this->_action} .html" : "{$this->templatepath}/$tempfile.html";
        $this->getHtmlFromTemplateFile();
        return $this;
    }

    //得到模板文件的HTML内容
    public function getHtmlFromTemplateFile() {
        if (file_exists($this->templatefile)) {
            $fp = fopen($this->templatefile, 'r');
            $html = fread($fp, filesize($this->templatefile));
            fclose($fp);

            $this->temp_html = $html;
        } else {
            $this->temp_html = '警告：模版文件没找到或为空。';
        }

        return $this;
    }


    public function assign($var,$value=null){
        if (is_array($var)){
            foreach ($var as $key =>$item){
                $this->data[$key]=$item;
            }
        }else{
            $this->data[$var]=$value;
        }
    }

    /**
     * @param string $name 可支持指定目录下模板 index || index/index
     * @param array $data
     * @param bool $return
     * @return string
     */
    public function display($name='',$data=array(),$return=false){
        if (empty($name)){
            $name = $this->_controller . DIRS . $this->_action . TABLE_PRIFIX;
        }else{
            if (strpos($name, DIRS) !== false){
                $name = $name . TABLE_PRIFIX;
            }else{
                $name = $this->_controller . DIRS. $name . TABLE_PRIFIX;

            }
        }

        $templatePath = $this->templatepath . DIRS . $name;
        if (!file_exists($templatePath)){
            exit('警告：模版文件没找到或为空。');
        }

//        if (!is_dir(PATH_CACHE)){
//            @mkdir(PATH_CACHE,777);
//        }
        if (!is_dir($this->templatepath)){
            exit('警告：模版文件夹没找到或为空。');
        }

        require FRAME_ROOT.'/vendor/twig/Autoloader.php';
        $twig_config=array(
            'cache'=> false,
            'debug'=> TWIG_DEBUG,
            'auto_reload'=> TWIG_DEBUG
        );

        if (TWIG_CACHE){
            $twig_config['cache'] = PATH_CACHE;
        }

        Twig_Autoloader::register(true);
        $loader = new Twig_Loader_Filesystem($this->templatepath);
        $this->twig = new Twig_Environment($loader,$twig_config);
        $this->setDefautValue();
        $this->data = array_merge($this->data,$data);
        $this->temp_html = $this->twig->render($name,$this->data);
        $this->setTempSign();
        if ($this->useformtoken) {
            $this->setFormToken();
        }

        if ($return){
            return $this->temp_html;
        }else{
            echo $this->temp_html;
        }

    }

    //设置页面合法标签
    public function setTempSign() {
        $sign = getGID();
        if (preg_match('/\<\/body\>/i', $this->temp_html)) {
            $this->temp_html = preg_replace('/\<\/body\>/i', "<div name = '_sign' sign='$sign'></div></body>", $this->temp_html);
        } else {
            $this->temp_html .= "<div name = '_sign' sign='$sign'></div>";
        }
        /* 		dump(strlen($this->temp_html));
                dump(strlen($this->temp_html));
                die; */
        return $this;
    }

    //定义系统变量
    private function setDefautValue($array=''){
        $list=array(
            '_PUBLIC_'   => _PUBLIC_,
            '_SHARE_'    => _SHARE_,
            '_UPLOAD_'   => _UPLOAD_,
            '_CACHE_'    => _CACHE_,
            '_DOWNLOAD_' =>_DOWNLOAD_,
        );

        if (!empty($array) && is_array($array)){
            $list = array_merge($list,$array);
        }

        foreach ($list as $k =>$value){
            $this->assign($k, $value);
        }
    }

    /*
     * 设置form令牌
     * 页面form表单会话，是为了防止重复提交而设计的，在$_SESSION['formtoken']内存储token和状态值的键值对，每次生成表单
     * 时候，自动生成一个token键，对应状态为有效1，为了防止恶意刷新页面，造成此键值对数组过长，设置了数组最大长度，实现了
     * 队列的先进先出，维护定长的formtoken数组
     */

    public function setFormToken() {
        $sign = getGID();
        !isset($_SESSION['formtoken']) && $_SESSION['formtoken'] = '';

        if (count($_SESSION['formtoken']) > MAXFORMTOKEN) {
            //超过了最大formtoken，删除最旧的一个token，即数组的第一个元素。
            array_shift($_SESSION['formtoken']);
        }
        //尾部追加一个token，状态为有效1。
        $_SESSION['formtoken'][$sign] = 1;

        if (preg_match_all('/\<form\s+action.+?\>(\s|.)+?(?=\<\/form\>)/i', $this->temp_html, $matchForms)) {
            foreach($matchForms[0] as $match){
                $this->temp_html = str_replace($match, $match."<input id = '_posttoken' name = '_posttoken' type='hidden' value='$sign'>", $this->temp_html);
            };
            //$this->temp_html = preg_replace('/<form>/i', "<form><input id = '_posttoken' name = '_posttoken' type='hidden' value='$sign'>", $this->temp_html);
        } elseif (preg_match('/<body[^>]*?>/i', $this->temp_html)) {
            $this->temp_html = preg_replace('/<body>/i', "<body><form><input id = '_posttoken' name = '_posttoken' type='hidden' value='$sign'>", $this->temp_html);
            $this->temp_html = preg_replace('/<\/body>/i', "</form></body>", $this->temp_html);
        } else {
            $this->temp_html = "<form><input id = '_posttoken' name = '_posttoken' type='hidden' value='$sign'>" . $this->temp_html . '</form>';
        }

        return $this;
    }

    //删除令牌
    public function delFormToken($sign) {
        unset($_SESSION['formtoken'][$sign]);
    }

	
	function __destruct(){
		// $this->_view->render();
	}
	
}