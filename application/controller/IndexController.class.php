<?php

class IndexController extends Controller{
	
	function index(){
		$id = I('request.', 0, 'intval');
		$class = D('Class');
		$url   = U('/Index/test');
//		$email = new email();
//		$res   = $email->sendEmail('770517692@qq.com', 'aaaaa', '来自aupl的邮件提醒');die;
//		$result = $class->getData();
        $charArray = $this->orderByName();
        dump($charArray);die;
		$this->assign('result', 'aaa');
		$this->display();
	}

	function test(){
	    dump($this->option);die;
	    $this->assign('name', 'aupl is my nickname');
		$this->display('index');
	}

	public function getFirstChar($string){

        $char = mb_substr($string, 0, 1, 'utf-8');
        $s    = iconv('UTF-8','gb2312', $char);
        if (ord($char) > 128) {                      //汉字开头，汉字没有以U、V开头的
            $asc = ord($s{0}) * 256 + ord($s{1}) - 65536;
            if($asc>=-20319 and $asc<=-20284)return "A";
            if($asc>=-20283 and $asc<=-19776)return "B";
            if($asc>=-19775 and $asc<=-19219)return "C";
            if($asc>=-19218 and $asc<=-18711)return "D";
            if($asc>=-18710 and $asc<=-18527)return "E";
            if($asc>=-18526 and $asc<=-18240)return "F";
            if($asc>=-18239 and $asc<=-17923)return "G";
            if($asc>=-17922 and $asc<=-17418)return "I";
            if($asc>=-17417 and $asc<=-16475)return "J";
            if($asc>=-16474 and $asc<=-16213)return "K";
            if($asc>=-16212 and $asc<=-15641)return "L";
            if($asc>=-15640 and $asc<=-15166)return "M";
            if($asc>=-15165 and $asc<=-14923)return "N";
            if($asc>=-14922 and $asc<=-14915)return "O";
            if($asc>=-14914 and $asc<=-14631)return "P";
            if($asc>=-14630 and $asc<=-14150)return "Q";
            if($asc>=-14149 and $asc<=-14091)return "R";
            if($asc>=-14090 and $asc<=-13319)return "S";
            if($asc>=-13318 and $asc<=-12839)return "T";
            if($asc>=-12838 and $asc<=-12557)return "W";
            if($asc>=-12556 and $asc<=-11848)return "X";
            if($asc>=-11847 and $asc<=-11056)return "Y";
            if($asc>=-11055 and $asc<=-10247)return "Z";
        }else if(ord($s)>=48 and ord($s)<=57){ //数字开头
            switch(iconv_substr($s,0,1,'utf-8')){
                case 1:return "Y";
                case 2:return "E";
                case 3:return "S";
                case 4:return "S";
                case 5:return "W";
                case 6:return "L";
                case 7:return "Q";
                case 8:return "B";
                case 9:return "J";
                case 0:return "L";
            }
        }else if(ord($s)>=65 and ord($s)<=90){ //大写英文开头
            return substr($s,0,1);
        }else if(ord($s)>=97 and ord($s)<=122){ //小写英文开头
            return strtoupper(substr($s,0,1));
        }else{
            return iconv_substr($char,0,1,'utf-8');
            //中英混合的词语，不适合上面的各种情况，因此直接提取首个字符即可
        }
    }

    public function orderByName(){
        $userName = array('张三','李四','王五','小二','猫蛋','狗蛋','王花','三毛','小明','Mary','李刚','张飞','Lucy');
        sort($userName);
        $charArray = array();
        foreach($userName as $name){
            $char = $this->getFirstChar($name);
            if(!array_key_exists($char, $charArray)){
                $charArray[$char] = array();
            }
            array_push($charArray[$char], $name);//将姓名按照姓的首字母与相对的首字母键进行配对
        }

        ksort($charArray);//根据键值对排序
        return $charArray;
    }
	
}