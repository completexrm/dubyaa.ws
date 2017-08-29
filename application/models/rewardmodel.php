<?php

Class RewardModel extends CI_Model {

	function SetupGift($params) {
		
		$params['createdBy'] = $params['userId'];
		$params['updatedBy'] = $params['userId'];
		$params['toId'] = $params['userId'];
		$params['suggestId'] = $params['id'];

		$params['updatedOn'] = date('Y-m-d H:i:s');

		unset($params['userId']);
		unset($params['id']);

		if($this->db->insert('reward', $params)) {
			return true;
		} else {
			return null;
		}

	}


}
?>