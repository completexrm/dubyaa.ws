<?php

Class TodoModel extends CI_Model {

/* ---------------------------------------------------------------------------- *
	MODEL "TodoModel"
	Author: Brian Garstka
	All operations that touch the DB dealing with ToDo's
* ---------------------------------------------------------------------------- */

	function doesExist($id) {
		$s = "
			select id from todo where id = ? limit 1;
		";
		$q = $this->db->query($s, array($id));
		if($q->num_rows()) {
			return true;
		} else {
			return false;
		}
	}

	function CreateTodo($data) {
		$params = $data;
		unset($data['multiplier']);
		$data['createdOn'] = date('Y-m-d H:i:s');
		$data['updatedOn'] = date('Y-m-d H:i:s');
		$data['updatedBy'] = $data['userId'];
		$data['createdBy'] = $data['userId'];
		if(isset($data['dateDue'])) {
			$data['dateDue'] = date('Y-m-d 23:59:59', strtotime($data['dateDue']));
		}
		if($this->db->insert('todo', $data)) {
			$params['id'] = $this->db->insert_id();
			$this->SetDueDate($params);
			return $params['id'];
		} else {
			return null;
		}
	}

	function React($data) {
		
		$sqlWhere = array(
			'activityId' => $data['activityId'],
			'fromId' => $data['fromId']
		);

		$this->db->where($sqlWhere);
		$this->db->delete('user_reaction');

		$data['updatedOn'] = date('Y-m-d H:i:s');
		$data['todoId'] = $data['actionId'];
		unset($data['actionId']);
		if($this->db->insert('user_reaction', $data)) {
			return true;
		} else {
			return null;
		}
	}

	function ReactDelete($data) {
		$this->db->where('id', $data['id']);
		if($this->db->delete('user_reaction')) {
			return true;
		} else {
			return null;
		}
	}

	function FetchTodo($params) {
		$s  ="
			select A.* 
			from todo A 
			where A.id = ?
		";

		$q = $this->db->query($s, array($params['id']));
	
		if($q->num_rows()) {
			$r = $q->row();
			return $r;
		} else {
			return null;
		}
	}
	function FetchUserTodos($params) {
		$sqlDubyaaDeleted='';
		if(isset($params['isDubyaa']) && $params['isDubyaa']==1) {
			$sqlDubyaaDeleted = ' and isDubyaa = 1 and isDeleted = 0';
			$params['orderBy'] = ' updatedOn desc, dubyaaDate desc';
		} elseif(isset($params['isDeleted']) && $params['isDeleted']==1) {
			$sqlDubyaaDeleted = ' and isDeleted = 1';
			$params['orderBy'] = ' updatedOn desc, isDubyaa, points';
		} elseif(isset($params['isDelegated']) && $params['isDelegated']==1) {
			$sqlDubyaaDeleted = ' and delegatorId > 0';
			$params['orderBy'] = ' updatedOn desc, dateDue, points';
		} else {
			$sqlDubyaaDeleted = ' and isDeleted = 0 and isDubyaa = 0';
			$params['orderBy'] = ' dateDue, points desc, createdOn';
		}
		if(isset($params['sortField'])) {
			$params['orderBy'] = $params['sortField'];
			if(isset($params['sortDirection'])) {
				$params['orderBy'] .= ' '.$params['sortDirection'];
			} else {
				$params['orderBy'] .= ' asc';
			}
		}

		$params['dateStart'] = date('Y').'-01-01';
		$params['dateEnd'] = date('Y').'-12-31';
		if(isset($params['dateRange'])) {
			switch($params['dateRange']) {
				case 'today':
					$params['dateStart'] = date('Y').'-'.date('m').'-'.date('d').' 00:00:00';
					$params['dateEnd'] = date('Y').'-'.date('m').'-'.date('d').' 23:59:59';
					break;
				case 'month':
					$params['dateStart'] = date('Y').'-'.date('m').'-01';
					$params['dateEnd'] = date('Y').'-'.date('m').'-'.date('t');
					break;
			}
		}
		$dateRangeField = (isset($params['dateRangeType']) && $params['dateRangeType']!=='' && $params['dateRangeType']!=='undefined') ? $params['dateRangeType'] : 'updatedOn';
		$s = "
			select * 
			from todo A 
			where A.userId = ? 
			and A.isBacklog = 0 
			
			$sqlDubyaaDeleted  
			order by ".$params['orderBy']."
		";
		// Removed from query to allow all items to appear on user main page :: BG 3/9/16
		// and $dateRangeField between ? and ? 

		$q = $this->db->query($s, array(
				$params['userId'],
				$params['dateStart'],
				$params['dateEnd']
			)
		);
	
		if($q->num_rows()) {
			$todos = array();
			$i=1;
			foreach ($q->result() as $r) {
				// build tag object
				$r->tags = $this->FetchTodoTags($r->id);
				$r->reactions = $this->FetchTodoReactions($r->id);
				$todos[$i] = $r;
				$i++;
			}
			return $todos;
		} else {
			return null;
		}
	}

	function FetchUserTodosBacklog($params) {
		$s = "
			select * 
			from todo A 
			where A.userId = ? 
			and A.isBacklog = 1 
			order by updatedOn desc
		";

		$q = $this->db->query($s, array(
				$params['userId']
			)
		);
	
		if($q->num_rows()) {
			$todos = array();
			$i=1;
			foreach ($q->result() as $r) {
				$todos[$i] = $r;
				$i++;
			}
			return $todos;
		} else {
			return null;
		}
	}

	function FetchTodoComments($params) {
		$s = "
			select sum(A.isGood) as isGoodTotal, sum(A.isBad) as isBadTotal, sum(A.isDispute) as isDisputeTotal 
			from user_reaction A 
			left join user_activity B 
			on B.id = A.activityId 
			where A.todoId = ?
		";

		$q = $this->db->query($s, array($params['id']));
	
		if($q->num_rows()) {
			$r = $q->row();
			return $r;
		} else {
			return null;
		}
	}

	function FetchTodoTags($id) {
		$s = "
			select B.id, A.tag 
			from _tag A 
			left join todo_tag B 
			on B.tagId = A.id 
			where B.todoId = ? 
			order by tag
		";
		$q = $this->db->query($s, array($id));
		if($q->num_rows()) {
			foreach ($q->result() as $r) {
				$tags[] = array('tagId' => $r->id, 'tag' => $r->tag);
			}
			return $tags;
		} else {
			return array();
		}
	}

	function FetchTodoReactions($id) {
		$s = "
			select sum(isGood) as totalGood, sum(isBad) as totalBad, sum(isDispute) as totalDispute
			from user_reaction 
			where todoId = ? 
		";
		$q = $this->db->query($s, array($id));
		$r = $q->row();
		if(is_null($r->totalBad) && is_null($r->totalGood) && is_null($r->totalDispute)) {
			return null;
		} else {
			$reaction = array(
				'totalGood' => $r->totalGood,
				'totalBad' => $r->totalBad,
				'totalDispute' => $r->totalDispute,
			);
			return $reaction;
		}
	}

	function UpdateTodo($data) {
		$data['updatedBy'] = $data['userId'];
		$data['updatedOn'] = date('Y-m-d H:i:s');
		$id = $data['id'];
		unset($data['id']);
		unset($data['userId']);
		$this->db->where('id', $id);
		if($this->db->update('todo', $data)) {
			return true;
		} else {
			return null;
		}
	}

	function BacklogConvert($data) {
		$data['updatedBy'] = $data['userId'];
		$data['updatedOn'] = date('Y-m-d H:i:s');
		$id = $data['id'];
		unset($data['id']);
		unset($data['userId']);
		unset($data['accountId']);
		$data['isBacklog']=0;
		$this->db->where('id', $id);
		if($this->db->update('todo', $data)) {
			return true;
		} else {
			return null;
		}
	}

	function SuggestionToBacklog($params) {
		$data = array(
			'label' => $params['label'],
			'accountId' => $params['accountId'],
			'userId' => $params['userId'],
			'createdBy' => $params['userId'],
			'updatedBy' => $params['userId'],
			'updatedOn' => date('Y-m-d H:i:s'),
			'isBacklog' => 1
		);
		if($this->db->insert('todo', $data)) {
			return $this->db->insert_id();
		} else {
			return null;
		}
	}

	function DelegateTodo($params) {
		$data = array(
			'delegatorId' => $params['delegatorId'],
			'userId' => $params['userId'],
			'delegatorName' => $params['delegatorName'],
			'updatedBy' => $params['userId'],
			'updatedOn' => date('Y-m-d H:i:s'),
			'delegateDate' => date('Y-m-d H:i:s'),
			'isBacklog' => 1
		);
		$this->db->where('id', $params['id']);
		if($this->db->update('todo', $data)) {
			return true;
		} else {
			return null;
		}
	}

	function DeleteTodo($data) {
		$this->db->where('id', $data['id']);
		$updateData = array(
			'isDeleted' => 1,
			'updatedOn' => date('Y-m-d H:i:s'),
			'updatedBy' => $data['userId']
		);
		if($this->db->update('todo', $updateData)) {
			// Delete user reactions
			$this->db->where('todoId', $data['id']);
			$this->db->delete('user_reaction');
			// Delete from activity
			$this->db->query("delete from user_activity where actionTable='todo' and actionId='".$data['id']."'");
			return true;
		} else {
			return null;
		}
	}

	function SaveToBacklog($data) {
		$this->db->where('id', $data['id']);
		$updateData = array(
			'isBacklog' => 1,
			'updatedOn' => date('Y-m-d H:i:s'),
			'updatedBy' => $data['userId']
		);
		if($this->db->update('todo', $updateData)) {
			$this->db->where('todoId', $data['id']);
			$this->db->delete('user_reaction');
			$this->db->where('actionId', $data['id']);
			$this->db->delete('user_activity');
			return true;
		} else {
			return null;
		}
	}

	function TorchTodo($data) {
		$id = $data['id'];
		// Delete primary Todo records
		$this->db->where('id', $id);
		$this->db->delete('todo');
		// Delete tag associations
		$this->db->where('todoId', $id);
		$this->db->delete('todo_tag');
		// Delete user reactions
		$this->db->where('todoId', $id);
		$this->db->delete('user_reaction');
		// Delete from activity
		$this->db->query("delete from user_activity where actionTable='todo' and actionId='$id'");
		return true;
	}

	function UndeleteTodo($data) {
		$this->db->where('id', $data['id']);
		$updateData = array(
			'isDeleted' => 0,
			'updatedOn' => date('Y-m-d H:i:s'),
			'updatedBy' => $data['userId']
		);
		if($this->db->update('todo', $updateData)) {
			return true;
		} else {
			return null;
		}
	}

	function SetDueDate($data) {
		
		$data['pointsMultiple'] = (isset($data['dateDue']) && $data['dateDue'] !='' && !is_null($data['dateDue'])) ? $data['multiplier'] : 1;
		$data['dateDue'] = (isset($data['dateDue']) && $data['dateDue']!='' && !is_null($data['dateDue'])) ? $data['dateDue'] : null;
		if($data['dateDue']) {
			$data['dateDue'] = date('Y-m-d 23:59:59', strtotime($data['dateDue']));
		}
		$this->db->where('id', $data['id']);
		unset($data['id']);
		unset($data['userId']);
		unset($data['multiplier']);
		if($this->db->update('todo', $data)) {
			return true;
		} else {
			return null;
		}

	}

	function DoDubyaa($data) {
		
		$s = "
			select dateDue, points, pointsMultiple 
			from todo 
			where id = ? 
			limit 1
		";

		$q = $this->db->query($s, array($data['id']));
		
		// get row
		$r = $q->row();
		// setup vars from result
		$dateDue = $r->dateDue;
		$pointsMultiple = $r->pointsMultiple;
		$points = $r->points;
		
		// date we are marking as a dubyaa
		$dubyaaDate = date('Y-m-d H:i:s');

		// if user has a due date and completes before that date...kudos!
		$data['pointsAwarded'] = (!is_null($dateDue) && $dateDue > $dubyaaDate) ? $points * $pointsMultiple : $points;
		$data['updatedBy'] = $data['userId'];
		$data['dubyaaDate'] = date('Y-m-d H:i:s');
		$data['isDubyaa'] = 1;
		$this->db->where('id', $data['id']);
		unset($data['id']);
		unset($data['userId']);
		if($this->db->update('todo', $data)) {
			return true;
		} else {
			return null;
		}
	}

	function DeleteDubyaa($data) {
		$data['pointsAwarded'] = 0;
		$data['updatedOn'] = date('Y-m-d H:i:s');
		$data['updatedBy'] = $data['userId'];
		$data['dubyaaDate'] = null;
		$data['isDubyaa'] = 0;
		$this->db->where('id', $data['id']);
		unset($data['id']);
		unset($data['userId']);
		if($this->db->update('todo', $data)) {
			return true;
		} else {
			return null;
		}
	}

	function UserCanEnter($params) {
		$s = "
			select maxOpen 
			from account_pref 
			where accountId = ? 
		";
		$q = $this->db->query($s, array($params['accountId']));
		$r = $q->row();

		$sTot = "
			select count(*) as total
			from todo 
			where 
			userId = ? 
			and isDubyaa = 0 
			and isDeleted = 0
		";
		$qTot = $this->db->query($sTot, array($params['accountId']));
		$rTot = $qTot->row();
		if($rTot->total < $r->maxOpen) {
			return true;
		} else {
			return false;
		}
	}

	function DailyProductivity($params) {

		// Check to ensure we haven't already done this for this user on this day
		$s = "
			SELECT count(*) as isEntered 
			FROM user_productivity_score 
			WHERE userId = ? 
			AND date = ?
		";
		$q = $this->db->query($s, array($params['userId'], $params['day']));
		$r = $q->row();
		// There are no matches, so go ahead
		if($r->isEntered=='0') {

			// Baseline the daily score
			$dP = 0;
			/* ---------------------------------------------------------------------------- *
				POINTS FOR LOGGING IN
			 * ---------------------------------------------------------------------------- */
			$s = "
				SELECT COUNT(*) as totalLogins
				FROM user_session 
				WHERE userId = ? 
				AND createdOn like ?
			";
			$q = $this->db->query($s, array($params['userId'], $params['day'].'%'));
			$r = $q->row();
			$dailyLogin = ($r->totalLogins > 0) ? $params['scoring']['login'] : 0;

			/* ---------------------------------------------------------------------------- *
				POINTS FOR NO. PLANNED VS. MAX ALLOWED OPEN
			 * ---------------------------------------------------------------------------- */
			$s = "
				SELECT maxOpen
				FROM account_pref
				WHERE accountId = ? 
			";
			$q = $this->db->query($s, array($params['accountId']));
			$r = $q->row();
			$maxOpen = $r->maxOpen;

			$s = "
				SELECT count(*) as totalPlanned
				FROM todo 
				WHERE userId = ? 
				AND isDeleted = ? 
				AND isBacklog = 0 
				AND createdOn like ? 
			";
			$q = $this->db->query($s, array($params['userId'], 0, $params['day'].'%'));
			$r = $q->row();
			$totalPlanned = $r->totalPlanned;
			$planningScore = ($totalPlanned / $maxOpen) * $params['scoring']['planned'];
			$dailyPlanning = ($totalPlanned >= $maxOpen) ? $params['scoring']['planned'] : $planningScore;

			/* ---------------------------------------------------------------------------- *
				POINTS FOR NO. WITH EXPIRATION DATE VS. NO. PLANNED
			 * ---------------------------------------------------------------------------- */
			$s = "
				SELECT count(*) as totalWithExpiration
				FROM todo 
				WHERE userId = ? 
				AND isDeleted = ? 
				AND isBacklog = 0 
				AND createdOn like ? 
				AND dateDue is not null
			";
			$q = $this->db->query($s, array($params['userId'], 0, $params['day'].'%'));
			$r = $q->row();
			if($totalPlanned > 0) {
				$expirationScore = ($r->totalWithExpiration / $totalPlanned) * $params['scoring']['expiration'];
			} else {
				$expirationScore=0;
			}
			$dailyExpiration = ($r->totalWithExpiration == $totalPlanned) ? $params['scoring']['expiration'] : $expirationScore;
			$dailyExpiration = $dailyExpiration * ($dailyPlanning / $params['scoring']['planned']);

			/* ---------------------------------------------------------------------------- *
				PTS FOR "W'S" W/EXP.DATE & WIN DATE LESS < THAN EXP.DATE & WIN DATE == CURRENT DAY
			 * ---------------------------------------------------------------------------- */
			$s = "
				SELECT count(*) as totalDubyaasWithExpiration
				FROM todo 
				WHERE userId = ? 
				AND isDeleted = ? 
				AND isDubyaa = ? 
				AND isBacklog = 0 
				AND dateDue is not null 
				AND dubyaaDate like ? 
				AND dateDue >= ?
			";
			$q = $this->db->query($s, array($params['userId'], 0, 1, $params['day'].'%', $params['day'].'%'));
			$r = $q->row();
			$dubyaaScore = ($r->totalDubyaasWithExpiration / $maxOpen) * $params['scoring']['dubyaa'];
			$dailyDubyaa = ($r->totalDubyaasWithExpiration >= $maxOpen) ? $params['scoring']['dubyaa'] : $dubyaaScore;

			/* ---------------------------------------------------------------------------- *
				PTS FOR GETTING ACCOUNT LEVEL REACTION
			 * ---------------------------------------------------------------------------- */
			$s = "
				SELECT sum(isGood) as totalGood, sum(isBad) as totalBad 
				FROM user_reaction 
				WHERE toId = ? 
				AND toDoId > 0 
				AND createdOn like ? 
			";
			$q = $this->db->query($s, array($params['userId'], $params['day'].'%'));
			$r = $q->row();
			$totalIsGood = $r->totalGood;
			$totalIsBad = $r->totalBad;
			
			$dailySocial = (($r->totalGood > 0) || ($r->totalBad > 0)) ? $params['scoring']['social'] : 0;
			$dailySocialPositive = ($totalIsGood > $totalIsBad) ? $params['scoring']['socialPositive'] : 0;

			/* ---------------------------------------------------------------------------- *
				PTS FOR TRENDING UP OVER LAST 7 DAYS
			 * ---------------------------------------------------------------------------- */
			$s = "
				select avg(scoreTotal) as avgScore 
				from user_productivity_score
				where userId = ? 
				and date >= ? - INTERVAL 7 DAY 
				AND date < ?
			";
			$q = $this->db->query($s, array($params['userId'], $params['day'], $params['day']));
			$r = $q->row();
			
			$total = number_format($dailyLogin + $dailyPlanning + $dailyExpiration + $dailyDubyaa + $dailySocial + $dailySocialPositive, 3);

			$total = $total + $params['scoring']['trendingUp'];
			$dailyTrendingUp = $params['scoring']['trendingUp'];
			if(isset($r->avgScore) && $r->avgScore!='NULL' && !is_null($r->avgScore)) {
				if($r->avgScore > $total) {
					$total = $total - $params['scoring']['trendingUp'];
					$dailyTrendingUp = 0;
				}
			}
			
			$data = array(
				'userId' => $params['userId'],
				'accountId' => $params['accountId'],
				'date' => $params['day'],
				'scoreTotal' => $total,
				'scoreLogin' => $dailyLogin,
				'scorePlanning' => $dailyPlanning,
				'scoreExpiration' => $dailyExpiration,
				'scoreDubyaa' => $dailyDubyaa,
				'scoreSocial' => $dailySocial,
				'scoreSocialPositive' => $dailySocialPositive,
				'scoreTrendingUp' => $dailyTrendingUp
			);
			
			$this->db->insert('user_productivity_score', $data);
			return true;
		} else {
			return false;
		}

	}

	function UserActive($params) {
		$CI =& get_instance();
		$CI->load->model('AccountModel');
		$s = "
			SELECT A.* 
			FROM todo A
			WHERE A.userId = ? 
			AND isDeleted = 0 
			AND isBacklog = 0 
			AND isDubyaa = 0
			AND isPrivate = 0 
			ORDER by dateDue, points
		";
		$q = $this->db->query($s, array($params['id'], date('Y-m-d').'%'));
		if($q->num_rows()) {
			$todos = array();
			$i=1;
			foreach ($q->result() as $r) {
				$reactionParams['userId'] = $params['viewingId'];
				$reactionParams['todoId'] = $r->id;
				$r->reaction = $this->AccountModel->FetchUserReaction($reactionParams);
				$todos[$i] = $r;
				$i++;
			}
			return $todos;
		} else {
			return null;
		}
	}

	function Suggestion($params) {
		$params['toId'] = ($params['toId']==='0' || $params['toId']==='') ? null : $params['toId'];
		$params['createdBy'] = $params['fromId'];
		$params['updatedBy'] = $params['fromId'];
		$params['updatedOn'] = date('Y-m-d H:i:s');
		if($this->db->insert('suggest', $params)) {
			return $this->db->insert_id();
		} else {
			return null;
		}
	}

}

/* End of file TodoModel.php */
/* Location: ./application/models/TodoModel.php */
?>