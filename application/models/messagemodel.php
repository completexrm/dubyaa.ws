<?php

Class MessageModel extends CI_Model {

	function All($params) {
		$start = (!isset($params['start'])) ? '0' : $params['start'];
		$limit = (!isset($params['limit'])) ? '0' : $params['limit'];
		$s = "
				SELECT A.*, B.firstName, B.lastName, B.photoPath FROM 
				message A 
				LEFT JOIN user B
				ON B.id = A.userId 
				WHERE 
				A.isDeleted = 0 
				AND A.accountId = ? 
				AND A.parentId IS NULL 
				ORDER BY updatedOn DESC 
				LIMIT $start , $limit
		";
		$q = $this->db->query($s, array($params['accountId']));

		if($q->num_rows()) {
			$tS = "
				SELECT count(*) as totalMessages FROM 
				message 
				WHERE 
				isDeleted = 0 
				AND accountId = ?
				AND parentId IS NULL 
			";
			$tQ = $this->db->query($tS, array($params['accountId']));
			$tR = $tQ->row();
			$messages = array();
			$messages['totalMessages'] = $tR->totalMessages;
			$i=1;
			foreach ($q->result() as $r) {

				$authorId = $r->userId;

				$readInsert = array(
					'accountId' => $params['accountId'],
					'userId' => $params['userId'],
					'messageId' => $r->id
				);
				$sRead = "
					SELECT *
					FROM message_read 
					WHERE userId = ? 
					AND messageId = ?
				";
				$qRead = $this->db->query($sRead, array($params['userId'], $r->id));
				
				if($authorId!=$params['userId']) {
					if($qRead->num_rows()==0) {
						$this->db->insert('message_read', $readInsert);
					}
				}
				$msgId = $r->id;
				$messages['list'][$i] = $r;
				$messages['list'][$i]->responses = $this->AllResponses($msgId);
				$i++;
			}
			return $messages;
		} else {
			return null;
		}
	}

	function AllResponses($id) {
		$s = "
			SELECT A.*, B.firstName, B.lastName, B.photoPath FROM 
			message A 
			LEFT JOIN user B
			ON B.id = A.userId 
			WHERE 
			A.isDeleted = 0 
			AND A.parentId = ?
			ORDER BY createdOn 
		";
		$q = $this->db->query($s, array($id));
		if($q->num_rows()) {
			$i=1;
			$msg = array();
			foreach ($q->result() as $r) {
				$msg[$i] = $r;
				$i++;
			}
			return $msg;
		} else {
			return null;
		}
	}

	function Post($data) {
		$data['updatedOn'] = date('Y-m-d H:i:s');
		$data['createdBy'] = $data['userId'];
		$data['updatedBy'] = $data['userId'];
		if($this->db->insert('message', $data)) {
			if(isset($data['parentId'])) {
				$updateData = array(
					'updatedOn' => date('Y-m-d H:i:s')
				);
				$this->db->where('id', $data['parentId']);
				$this->db->update('message', $updateData);
			}
			$id = $this->db->insert_id();
			return $id;
		} else {
			return null;
		}
	}

	function Delete($params) {
		$data = array(
			'updatedOn' => date('Y-m-d H:i:s'),
			'updatedBy' => $params['userId'],
			'isDeleted' => '1'
		);
		$this->db->where('id', $params['id']);
		if($this->db->update('message', $data)) {
			$this->db->where('messageId', $params['id']);
			$this->db->delete('message_read');
			$id = $this->db->insert_id();
			return $id;
		} else {
			return null;
		}
	}

}
?>