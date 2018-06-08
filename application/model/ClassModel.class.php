<?php

class ClassModel extends Model{

    private $table;
    public function __construct(){
        $this->table = 'tang_class';
    }

    function getData(){
		$join = array(
			'left join tang_branch on tangCollege=br_id ',
			'left join tang_trainingsite on cl_defaultTrainingsiteId=tra_id ',
		);
		$where = array(
			'cl_state' => 0,
			//'cl_status' => 1
		);
		$where['cl_status'] = array('EQ', 1);
		$where['cl_id'] = array('in', '156,155,154');
		// $where['cl_id'] = array('in', '156');
		// $data['cl_gradeId'] = 1;
		// $data['cl_defaultTrainingsiteId'] = 2;
		// $where['cl_id'] = array('between', array('156', '154'));
		// $where['cl_id'] = array('egt', 154);
		// $where['cl_name'] = array('like', '%测试ssss%');
		// $where['_logic'] = 'and';
		// $where['_string'] = "cl_enrollStartTime='2017-01-07' or cl_enrollEndTime='2017-01-07'";
		$res = M($this->table)->where($where)->field(array('cl_id' => 'id', 'cl_name', 'cl_logo'))->join($join)->order(array('cl_id' => 'desc'))->limit('0,10')->select();
		// $res = $this->find('*', $where, $join);
		// $res = $this->save($data, $where);
		// $res = $this->getField($data, $where);
		// $res = $this->execute("select cl_id AS id,cl_name,cl_logo from `tang_class` left join tang_branch on tangCollege=br_id left join tang_trainingsite on cl_defaultTrainingsiteId=tra_id WHERE cl_state = 0 AND cl_status = 1 AND cl_id IN (154,156) ORDER BY cl_id desc LIMIT 0,10");
		return $res;
	}
	
}