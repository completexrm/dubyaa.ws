<?php

Class TeamModel extends CI_Model {

	function Create($params) {
		$userId = $params['userId'];
		unset($params['userId']);
		$params['createdOn'] = date('Y-m-d H:i:s');
		$params['createdBy'] = $userId;
		$params['updatedOn'] = date('Y-m-d H:i:s');
		$params['updatedBy'] = $userId;
		$params['teamLeadId'] = $userId;
		if($this->db->insert('team', $params)) {
			$teamId = $this->db->insert_id();
			$team = array(
				'userId' => $userId,
				'teamId' => $teamId
			);
			$this->db->insert('user_team', $team);
			return true;
		} else {
			return null;
		}
	}

	function UserList($params) {
		$s = "
			select A.*, D.displayName as teamOwner, C.id as teamLeadId, concat(C.firstName,' ', C.lastName) as teamLead  
			from team A 
			left join user_team B 
			on B.teamId = A.id 
			left join user C 
			on C.id = A.teamLeadId 
			left join account D 
			on D.id = A.accountId 
			where A.accountId = ?  
			and A.isDeleted = 0 
			order by displayName;
		";
		$q = $this->db->query($s, array(
				$params['accountId']
			)
		);
	
		if($q->num_rows()) {
			$teams = array();
			$i=1;
			foreach ($q->result() as $r) {
				$r->userCount = $this->TeamUserCount($r->id);
				$r->teamPoints = $this->TeamPointsCount($r->id);
				$teams[$i] = $r;
				$i++;
			}
			return $teams;
		} else {
			return null;
		}
	}

	function FetchRoster($params) {
		$s = "
			SELECT A.id 
			FROM user A 
			LEFT JOIN user_team B 
			ON B.userId = A.id 
			WHERE B.teamId = ?;
		";
		$q = $this->db->query($s, array($params['id']));
	
		if($q->num_rows()) {
			$users = array();
			$i=1;
			foreach ($q->result() as $r) {
				$users[$i] = $r->id;
				$i++;
			}
			return $users;
		} else {
			return null;
		}
	}

	function FetchTeamRoster($params) {
		$s = "
			SELECT A.*
			FROM user A 
			LEFT JOIN user_team B 
			ON B.userId = A.id 
			WHERE B.teamId = ?;
		";
		$q = $this->db->query($s, array($params['id']));
	
		if($q->num_rows()) {
			$users = array();
			$i=1;
			foreach ($q->result() as $r) {
				foreach($r as $key=>$value) {
					$users[$i][$key] = $value;
				}
				$i++;
			}
			return $users;
		} else {
			return null;
		}
	}

	function AccountList($params) {
		$s = "
			SELECT A.*, B.displayName as teamLead  
			FROM team A 
			LEFT JOIN user B 
			ON B.id = A.teamLeadId 
			WHERE A.accountId = ? 
			and A.isDeleted = ?
			ORDER BY A.displayName ASC
		";
	
		$q = $this->db->query($s, array($params['accountId'], 0));
	
		if($q->num_rows()) {
			$i=1;
			$teams=array();
			foreach ($q->result() as $r) {
				$r->userCount = $this->TeamUserCount($r->id);
				$teams[$i]['info'] = $r;
				$rosterParams = array('id' => $r->id);
				$teams[$i]['users'] = $this->FetchTeamRoster($rosterParams);
				$i++;
			}
			return $teams;
		} else {
			return null;
		}
	}

	function Update($params) {
		$data = array(
			'displayName' => $params['displayName'],
			'updatedOn' => date('Y-m-d H:i:s'),
			'updatedBy' => $params['userId']
		);
		$this->db->where('id', $params['teamId']);
		if($this->db->update('team', $data)) {
			$this->db->where('teamId', $params['teamId']);
			$this->db->delete('user_team');
			return true;
		} else {
			return null;
		}
	}

	function Delete($params) {
		$data = array(
			'isDeleted' => 1,
			'updatedOn' => date('Y-m-d H:i:s'),
			'updatedBy' => $params['userId']
		);
		$this->db->where('id', $params['teamId']);
		if($this->db->update('team', $data)) {
			$this->db->where('teamId', $params['teamId']);
			$this->db->delete('user_team');
			return true;
		} else {
			return null;
		}
	}

	function UpdateRoster($params) {
		
		if($params['include']==0) {
			// remove connection from user_team
			$s = "
				DELETE FROM user_team 
				WHERE userId = ? 
				AND teamId = ?
			";
			$q = $this->db->query($s, array($params['userId'], $params['teamId']));
			// Check to see if this user is set as teamLead
			// If so, set lead to nothing
			$s = "
				SELECT teamLeadId 
				FROM team 
				WHERE id = ?
			";
			$q = $this->db->query($s, array($params['teamId']));
			$r = $q->row();
			// user is teamLead
			if($r->teamLeadId==$params['userId']) {
				$data = array(
					'teamLeadId' => 0
				);
				// remove user as team
				$this->db->where('id', $params['teamId']);
				$this->db->update('team', $data);
			}
		} else {
			$data = array(
				'userId' => $params['userId'],
				'teamId' => $params['teamId']
			);
			$this->db->insert('user_team', $data);
		}
		return true;
	}

	function ChangeTeamLead($params) {
		$data = array(
			'teamLeadId' => $params['teamLeadId'],
			'updatedOn' => date('Y-m-d H:i:s'),
			'updatedBy' => $params['userId']
		);
		$this->db->where('id', $params['teamId']);
		if($this->db->update('team', $data)) {
			$s = "
				SELECT COUNT(*) as inTeam 
				FROM user_team 
				WHERE teamId = ? 
				AND userId = ?
			";
			$q = $this->db->query($s, array($params['teamId'], $params['teamLeadId']));
			$r = $q->row();
			$inTeam = $r->inTeam;
			if($inTeam<=0) {
				$data = array(
					'userId' => $params['teamLeadId'],
					'teamId' => $params['teamId']
				);
				$this->db->insert('user_team', $data);
			}
			return true;
		} else {
			return null;
		}
	}

	function TeamUserCount($id) {
		$s = "
			select count(*) as totalUsers 
			from user_team 
			where teamId = $id;
		";
		$total = 0;
		$q = $this->db->query($s);
		if($q->num_rows()) {
			$r = $q->row();
			$total = $r->totalUsers;
		}
		return $total;
	}

	function TeamPointsCount($teamId) {
		$s = "
			select sum(pointsAwarded) as totalPoints
			from todo 
			where createdOn like '".date('Y')."%' 
			and isDeleted=0 
			and isDubyaa=1 
			and userId in (
				select userId from user_team where teamId=?
			)
		";
		$total = 0;
		$q = $this->db->query($s, array($teamId));
		if($q->num_rows()) {
			$r = $q->row();
			$total = $r->totalPoints;
		}
		return $total;
	}

}
?>