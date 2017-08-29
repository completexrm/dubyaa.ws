<?php

Class JobModel extends CI_Model {
	
	function Log($userId, $job) {
		$job= array(
			'userId' => $userId,
			'job' => $job,
			'dateCreated' => date('Y-m-d'),
			'timeCreated' => date('H:i:s')
		);
		$this->db->insert('_job', $job);
	}

	function CheckUserJob($userId, $job) {
		$s = "
			SELECT * FROM _job 
			WHERE userId = ? 
			AND job = ?
			AND dateCreated = '".date('Y-m-d')."' 
		";
		$q = $this->db->query($s, array($userId, $job));
		if($q->num_rows() > 0) {
			return true;
		} else {
			return false;
		}
	}

}
?>