<?php

class AttribModel extends Model{
	
	function getData(){
		$data = array(
			'at_type' => 10,
			'at_key' => 7,
			'at_value' => '测试数据7',
			'at_fkey' => 0,
			'at_memo' => '测试'
		);
		$res = $this->add($data);
		// $res = $this->select('count(at_id)');
		return $res;
	}
	
}