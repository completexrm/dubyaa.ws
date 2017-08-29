<?php

Class TagModel extends CI_Model {

/* ---------------------------------------------------------------------------- *
	MODEL "TagModel"
	Author: Brian Garstka
	All operations that touch the DB dealing with Tags's
* ---------------------------------------------------------------------------- */
	
	function doesExist($tag) {
		$s = "
			select id 
			from _tag 
			where tag = ?
		";
		$q = $this->db->query($s, array($tag));
		if($q->num_rows()) {
			$r = $q->row();
			return $r->id;
		} else {
			return false;
		}
	}

	function InsertTag($data) {
		$data['createdBy'] = $data['userId'];
		$tag = array(
			'tag' => $data['tag'],
			'createdBy' => $data['userId']
		);
		$this->db->insert('_tag', $tag);
		$tagId = $this->db->insert_id();
		return $tagId;
	}

	function doesExistTodo($tagId, $todoId) {
		$s = "
			select * 
			from todo_tag
			where tagId = ? and todoId = ?
		";
		$q = $this->db->query($s, array($tagId, $todoId));
		if($q->num_rows()) {
			return true;
		} else {
			return false;
		}
	}

	function CreateTag($data) {
		// if tag doesnt exist in global tag table, create it
		if(!$tagId = $this->doesExist($data['tag'])) {
			$tagId = $this->InsertTag($data);
		}
		// if a todo / tag relation doesn't exist, create
		if(!$this->doesExistTodo($tagId, $data['id'])) {
			$tag = array(
				'tagId' => $tagId,
				'todoId' => $data['id']
			);
			if($this->db->insert('todo_tag', $tag)) {
				return true;
			} else {
				return null;
			}
		}
	}

	function DeleteTag($data) {
		$s = "
			delete 
			from todo_tag
			where id = ? 
		";
		if($q = $this->db->query($s, array($data['tagId']))) {
			return true;
		} else {
			return null;
		}
	}

	function FetchUserPopularTags($params) {
		$s = "
			select A.tag, C.id as todoId
			from _tag A 
			left join todo_tag B 
			on B.tagId = A.id 
			left join todo C 
			on C.id = B.todoId 
			where C.userId = ?  
			and C.isDeleted = 0;
		";
		$q = $this->db->query($s, array($params['userId']));
		if($q->num_rows()) {
			$tags = array();
			$i=1;
			foreach ($q->result() as $r) {
				$tags[$i] = $r;
				$i++;
			}
			return $tags;
		} else {
			return null;
		}
	}


}
/* End of file TagModel.php */
/* Location: ./application/models/TagModel.php */
?>