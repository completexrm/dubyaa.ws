<?php

Class UserModel extends CI_Model {

	function generateUserHash($pw) {
		$hash = password_hash($pw, PASSWORD_BCRYPT);
		return $hash;
	}

	function FetchAllActive() {
		$s = "
			SELECT A.*  
			FROM user A 
			WHERE isDeleted = 0 
		";
		$q = $this->db->query($s);
		if($q->num_rows()) {
			$users = array();
			foreach ($q->result() as $r) {
				$users[$r->id] = array(
					'accountId' => $r->accountId,
					'emailAddress' => $r->emailAddress
				);
			}
			return $users;
		} else {
			return null;
		}
	}

	function FetchSuggestionCount($params) {
		$s = "
			SELECT count(*) as totalUnreadSuggestions
			FROM suggest A 
			WHERE 
			( 
				toId = ? OR ( accountID = ? AND toId IS NULL) 
			)
			AND isDeleted = 0;
		";
		$q = $this->db->query($s, array($params['userId'], $params['accountId']));
		$r = $q->row();
		$total = $r->totalUnreadSuggestions;
		return $total;

	}

	function FetchMessagesUnread($params) {
		$s = "
			SELECT count(*) as totalMessages 
			FROM message A 
			WHERE accountId = ? 
			AND parentId IS NULL 
			AND createdBy != ?
			AND isDeleted = 0
		";
		$q = $this->db->query($s, array($params['accountId'], $params['userId']));
		$r = $q->row();
		$totalMessages = $r->totalMessages;

		$s = "
			SELECT count(*) as totalRead  
			FROM message_read A 
			WHERE userId = ? 
		";
		$q = $this->db->query($s, array($params['userId']));
		$r = $q->row();
		$totalRead = $r->totalRead;
		
		$totalUnread = $totalMessages - $totalRead;

		return $totalUnread;

	}
	
	function getUserHash($emailAddress) {
		$s = "
			SELECT A.*, B.displayName as accountName  
			FROM user A 
			LEFT JOIN account B on B.id = A.accountId 
			WHERE emailAddress = ? 
			LIMIT 1
		";
	
		$q = $this->db->query($s, array($emailAddress));
	
		if($q->num_rows()) {
			$r = $q->row();
			foreach($r as $key => $value) {
				$rtn['profile'][$key] = $value;
			}
			$sT = "
				SELECT count(*) AS totalTeams
				FROM user_team 
				WHERE userId = ?
			";
			$qT = $this->db->query($sT, array($r->id));
			$rT = $qT->row();
			$rtn['profile']['hasTeams'] = false;
			if($rT->totalTeams > 0) {
				$rtn['profile']['hasTeams'] = true;
			}
			$sP = "
				SELECT A.* 
				FROM user_pref A 
				WHERE userId = ? 
				LIMIT 1
			";
		
			$qP = $this->db->query($sP, array($r->id));
		
			if($qP->num_rows()) {
				$rP = $qP->row();
				foreach($rP as $keyP => $valueP) {
					$rtn['prefs'][$keyP] = $valueP;
				}
			}
			return $rtn;
		} else {
			return null;
		}
	}

	function EmailAvailable($params) {
		$s = "
			SELECT emailAddress 
			FROM user 
			WHERE emailAddress = ? 
		";
	
		$q = $this->db->query($s, array($params['email']));
	
		if($q->num_rows()) {
			return false;
		} else {
			return true;
		}		
	}

	function GetUserRemindersMorning() {
		
		$s = "
			SELECT A.id, A.displayName, A.firstName, A.lastName, A.emailAddress, A.mobileNumber, A.mobileCarrierSMS, B.tzOffset, B.hasReminders, B.hasRemindersSMS, B.amRemindTime, C.id as accountId   
			FROM user A 
			LEFT JOIN user_pref B 
			ON B.userId = A.id 
			LEFT JOIN account C 
			ON A.accountId = C.id 
			WHERE A.isDeleted = 0 
			AND (
				(
					C.isActive = 0 AND C.isTrial = 1
				) 
				OR 
				(
					C.isActive = 1 AND C.isTrial = 0
				)
			)
			AND C.isDeleted = 0 
			AND (
				B.hasReminders = 1 
				OR 
				B.hasRemindersSMS = 1 
			)
		";
	
		$q = $this->db->query($s);
	
		if($q->num_rows()) {
			
			$nowDay = date('Y-m-d');
			$now = date('Y-m-d H:i:s');
			$nowTs = strtotime($now);

			foreach ($q->result() as $r) {
				// Convert stored DB offset in minutes to seconds
				$tzOffset = $r->tzOffset * 60;
				// Account for user Timezone offset based on server Eastern timezone
				$remindTime = strtotime($nowDay.' '.$r->amRemindTime) - $tzOffset;
				if($nowTs >= $remindTime) { 
					$salutation = 'Hello';
					if($r->displayName) {
						$salutation = $r->displayName;
					} elseif($r->firstName) {
						$salutation = $r->firstName;
						$salutation = ($r->lastName) ? ' '.$r->lastName : '';
					}
					$rtn[$r->id] = array(
						'salutation' => $salutation,
						'emailAddress' => $r->emailAddress,
						'hasReminders' => $r->hasReminders,
						'hasRemindersSMS' => $r->hasRemindersSMS,
						'mobileNumber' => $r->mobileNumber,
						'mobileCarrierSMS' => $r->mobileCarrierSMS,
					);
				}
			}
			return $rtn;
		} else {
			return null;
		}
	}

	function GetUserRemindersEvening() {
		
		$s = "
			SELECT A.id, A.displayName, A.firstName, A.lastName, A.emailAddress, A.mobileNumber, A.mobileCarrierSMS, B.tzOffset, B.hasReminders, B.hasRemindersSMS, B.pmRemindTime, C.id as accountId   
			FROM user A 
			LEFT JOIN user_pref B 
			ON B.userId = A.id 
			LEFT JOIN account C 
			ON A.accountId = C.id 
			WHERE A.isDeleted = 0 
			AND (
				(
					C.isActive = 0 AND C.isTrial = 1
				) 
				OR 
				(
					C.isActive = 1 AND C.isTrial = 0
				)
			)
			AND C.isDeleted = 0 
			AND (
				B.hasReminders = 1 
				OR 
				B.hasRemindersSMS = 1 
			)
		";
	
		$q = $this->db->query($s);
	
		if($q->num_rows()) {
			$nowDay = date('Y-m-d');
			$now = date('Y-m-d H:i:s');
			$nowTs = strtotime($now);
			$rtn = array();
			foreach ($q->result() as $r) {
				// Convert stored DB offset in minutes to seconds
				$tzOffset = $r->tzOffset * 60;
				// Account for user Timezone offset based on server Eastern timezone
				$remindTime = strtotime($nowDay.' '.$r->amRemindTime) - $tzOffset;
				if($nowTs >= $remindTime) { 
					$salutation = 'Hello';
					if($r->displayName) {
						$salutation = $r->displayName;
					} elseif($r->firstName) {
						$salutation = $r->firstName;
						$salutation = ($r->lastName) ? ' '.$r->lastName : '';
					}
					$rtn[$r->id] = array(
						'salutation' => $salutation,
						'emailAddress' => $r->emailAddress,
						'hasReminders' => $r->hasReminders,
						'hasRemindersSMS' => $r->hasRemindersSMS,
						'mobileNumber' => $r->mobileNumber,
						'mobileCarrierSMS' => $r->mobileCarrierSMS
					);
				}
			}
			return $rtn;
		} else {
			return null;
		}
	}

	function getUser($id = null) {
		$s = "
			SELECT * 
			FROM user A 
			WHERE id = ? 
			LIMIT 1
		";
	
		$q = $this->db->query($s, array($id));
	
		if($q->num_rows()) {
			$r = $q->row();
			$r->prefs = $this->FetchUserPrefs($id);
			return $r;
		} else {
			return null;
		}
	}

	function genAutoLoginKey($userId, $key) {
	    $data = array(
	    	'autoLoginKey' => $key,
	    	'autoLoginExpires' => date('Y-m-d H:i:s', strtotime('+1 day', time()))
	    );
	    $this->db->where('id', $userId);
	    $this->db->update('user', $data);
	    return $key;
	}

	function validateAutoLoginKey($data) {
		$s = "
			SELECT autoLoginExpires 
			FROM user A 
			WHERE isDeleted = ? 
			AND autoLoginKey = ? 
			AND emailAddress = ?
		";
		$where = array(
			0, $data['key'], $data['emailAddress']
		);
		$q = $this->db->query($s, $where);
		if($q->num_rows()) {
			$r = $q->row();
			$stamp = strtotime($r->autoLoginExpires);
			if(time() <= $stamp) {
				$user = $this->getUserHash($data['emailAddress']);
				$this->killAutoLoginKey($user['profile']['id']);
				return $user;
			} else {
				return false;
			}
		}
	}

	function killAutoLoginKey($userId) {
		$data['autoLoginKey'] = null;
		$data['autoLoginExpires'] = null;
		$this->db->where('id', $userId);
		$this->db->update('user', $data);
	}

	function Delete($params) {
		$data['isDeleted'] = 1;
		$data['updatedOn'] = date('Y-m-d H:i:s');
		$data['updatedBy'] = $params['userId'];
		$this->db->where('id', $params['id']);
		if($this->db->update('user', $data)) {
			$this->db->where('userId', $params['id']);
			$this->db->delete('user_team');
			return true;
		} else {
			return null;
		}
	}

	function Update($data) {
		$data['updatedOn'] = date('Y-m-d H:i:s');
		$data['updatedBy'] = $data['userId'];
		$id = $data['id'];
		unset($data['id']);
		unset($data['userId']);
		$this->db->where('id', $id);
		if($this->db->update('user', $data)) {
			return true;
		} else {
			return null;
		}
	}

	function UpdatePhoto($data) {
		$data['updatedOn'] = date('Y-m-d H:i:s');
		$data['updatedBy'] = $data['userId'];
		$userId = $data['userId'];
		unset($data['userId']);
		$this->db->where('id', $userId);
		if($this->db->update('user', $data)) {
			return true;
		} else {
			return null;
		}
	}

	function DeletePhoto($data) {
		$data['updatedOn'] = date('Y-m-d H:i:s');
		$data['updatedBy'] = $data['userId'];
		$data['photoPath'] = '';
		$userId = $data['userId'];
		unset($data['userId']);
		$this->db->where('id', $userId);
		if($this->db->update('user', $data)) {
			return true;
		} else {
			return null;
		}
	}

	function Create($data) {
		$date = date('Y-m-d H:i:s');
		$user = array(
			'createdBy' => $data['userId'],
			'updatedBy' => $data['userId'],
			'createdOn' => $date,
			'updatedOn' => $date,
			'accountId' => $data['accountId'],
			'displayName' => $data['firstName'].' '.$data['lastName'],
			'firstName' => $data['firstName'],
			'lastName' => $data['lastName'],
			'emailAddress' => $data['emailAddress'],
			'isAccountAdmin' => $data['isAccountAdmin'],
			'userHash' => $this->generateUserHash($data['password']),
			'isPWReset' => 1
		);
		if($this->db->insert('user', $user)) {
			$id = $this->db->insert_id();
			$userPrefs = array(
				'userId' => $id,
				'amRemindTime' => $data['defaults']['amRemindTime'],
				'pmRemindTime' => $data['defaults']['pmRemindTime']
			);
			$this->CreateDefaultPrefs($userPrefs);
			if($data['teamId']) {
				$this->UserAddTeam($id, $data['teamId']);
			}
			return $id;
		} else {
			return false;
		}
	}

	function UserAddTeam($userId, $teamId) {
		$team = array(
			'userId' => $userId,
			'teamId' => $teamId
		);
		$this->db->insert('user_team', $team);
	}

	function CreateSeed($data) {
		$date = date('Y-m-d H:i:s');
		$user = array(
			'createdBy' => 0,
			'updatedBy' => 0,
			'createdOn' => $date,
			'updatedOn' => $date,
			'accountId' => $data['accountId'],
			'displayName' => $data['firstName'].' '.$data['lastName'],
			'firstName' => $data['firstName'],
			'lastName' => $data['lastName'],
			'emailAddress' => $data['emailAddress'],
			'userHash' => $this->generateUserHash($data['password'])
		);
		if(!$data['key'] || $data['key']=='') {
			$user['isAccountAdmin'] = 1;
		}
		if($this->db->insert('user', $user)) {
			$id = $this->db->insert_id();
			$userPrefs = array(
				'userId' => $id,
				'amRemindTime' => $data['defaults']['amRemindTime'],
				'pmRemindTime' => $data['defaults']['pmRemindTime']
			);
			$this->CreateDefaultPrefs($userPrefs);
			return $id;
		} else {
			return false;
		}
	}

	function CreateDefaultPrefs($data) {
		$data['createdOn'] = date('Y-m-d H:i:s');
		$data['updatedOn'] = date('Y-m-d H:i:s');
		$this->db->insert('user_pref', $data);
	}

	function SetGoal($params) {
		$params['goalYear'] = date('Y');
		$params['updatedOn'] = date('Y-m-d H:i:s');
		if(isset($params['Y'])) {
			$this->SetGoalYear($params);
		}
		if(isset($params['Q'])) {
			$this->SetGoalQuarter($params);
		}
		if(isset($params['M'])) {
			$this->SetGoalMonth($params);
		}

		$data = array(
			'avgPointsDay' => $params['avgPointsDay']
		);
		$this->db->where('userId', $params['userId']);
		$this->db->update('user_pref', $data);
		return true;
	}

	function SetGoalYear($params) {
		
		$s = "
			select * 
			from user_goal 
			where userId = ? 
			and goalYear = ? 
			and goalType = 'Y'
		";

		$data = array(
			'goalPts' => $params['Y'],
			'updatedOn' => date('Y-m-d H:i:s')
		);

		$where = array($params['userId'], date('Y'));
		$q = $this->db->query($s, $where);
		if(!$q->num_rows()) {
			$data['userId'] = $params['userId'];
			$data['createdOn'] = date('Y-m-d H:i:s');
			$data['goalType'] = 'Y';
			$data['goalYear'] = date('Y');
			$this->db->insert('user_goal', $data);
		} else {
			$dbWhere = array(
				'userId' => $params['userId'],
				'goalYear' => date('Y'),
				'goalType' => 'Y',
			);
			$this->db->where($dbWhere);
			$this->db->update('user_goal', $data);
		}
	}

	function SetGoalQuarter($params) {
		$s = "
			select * 
			from user_goal 
			where userId = ? 
			and goalYear = ?
			and goalQuarter = ? 
			and goalType = 'Q'
		";
		$data = array(
			'goalPts' => $params['Q'],
			'updatedOn' => date('Y-m-d H:i:s')
		);
		$where = array($params['userId'], date('Y'), $params['quarter']['quarter']);
		$q = $this->db->query($s, $where);
		if(!$q->num_rows()) {
			$data['userId'] = $params['userId'];
			$data['createdOn'] = date('Y-m-d H:i:s');
			$data['goalType'] = 'Q';
			$data['goalYear'] = date('Y');
			$data['goalQuarter'] = $params['quarter']['quarter'];
			$this->db->insert('user_goal', $data);
		} else {
			$dbWhere = array(
				'userId' => $params['userId'],
				'goalYear' => date('Y'),
				'goalQuarter' => $params['quarter']['quarter'],
				'goalType' => 'Q',
			);
			$this->db->where($dbWhere);
			$this->db->update('user_goal', $data);
		}
	}

	function SetGoalMonth($params) {
		$s = "
			select * 
			from user_goal 
			where userId = ? 
			and goalYear = ?
			and goalMonth = ? 
			and goalType = 'M'
		";
		$data = array(
			'goalPts' => $params['M'],
			'updatedOn' => date('Y-m-d H:i:s')
		);
		$where = array($params['userId'], date('Y'), date('m'));
		$q = $this->db->query($s, $where);
		if(!$q->num_rows()) {
			$data['userId'] = $params['userId'];
			$data['createdOn'] = date('Y-m-d H:i:s');
			$data['goalType'] = 'M';
			$data['goalYear'] = date('Y');
			$data['goalMonth'] = date('m');
			$this->db->insert('user_goal', $data);
		} else {
			$dbWhere = array(
				'userId' => $params['userId'],
				'goalYear' => date('Y'),
				'goalMonth' => date('m'),
				'goalType' => 'M',
			);
			$this->db->where($dbWhere);
			$this->db->update('user_goal', $data);
		}
	}

	function StoreSession($params) {
		$params['createdOn'] = date('Y-m-d H:i:s');
		$this->db->insert('user_session', $params);
	}

	function KillSession($params) {
		$data = array(
			'isActive' => 0
		);
		$this->db->where('sessionId', $params['si']);
		if($this->db->update('user_session', $data)) {
			return true;
		} else {
			return null;
		}
	}

	function SaveProfile($params) {
		$userId = $params['userId'];
		unset($params['userId']);
		$user = array(
			'firstName' => $params['firstName'],
			'tagLine' => $params['tagLine'],
			'lastName' => $params['lastName'],
			'mobileNumber' => $params['mobileNumber'],
			'mobileCarrierSMS' => $params['mobileCarrierSMS'],
			'displayName' => $params['displayName']
		);
		$prefs = array(
			'tzOffset' => $params['tzOffset'],
			'hasReminders' => $params['hasReminders'],
			'hasRemindersSMS' => $params['hasRemindersSMS'],
			'amRemindTime' => $params['amRemindTime'],
			'pmRemindTime' => $params['pmRemindTime'],
			'appHints' => $params['appHints']
		);
		if(isset($params['pw']) && $params['pw'] !== '') {
			$pw = $params['pw'];
			unset($params['pw']);
			$user['userHash'] = $this->generateUserHash($pw);
		}
		$this->db->where('userId', $userId);
		if($this->db->update('user_pref', $prefs)) {			
			$this->db->where('id', $userId);
			$this->db->update('user', $user);
			return true;
		} else {
			return false;
		}
	}

	function NoAppHints($params) {
		$userId = $params['userId'];
		$prefs = array(
			'appHints' => 0
		);
		$this->db->where('userId', $userId);
		if($this->db->update('user_pref', $prefs)) {
			return true;
		} else {
			return false;
		}
	}

	function SaveOnboard($params) {
		$user=array();
		$userId = $params['userId'];
		unset($params['userId']);
		unset($params['account']);
		if(isset($params['pw'])) {
			$pw = $params['pw'];
			unset($params['pw']);
			$user['userHash'] = $this->generateUserHash($pw);
		}
		$user = array(
			'emailAddress' => $params['emailAddress'],
			'mobileNumber' => $params['mobileNumber'],
			'mobileCarrierSMS' => $params['mobileCarrierSMS']
		);
		unset($params['emailAddress']);
		unset($params['mobileNumber']);
		unset($params['mobileCarrierSMS']);
		$this->db->where('userId', $userId);
		if($this->db->update('user_pref', $params)) {
			$user['isFirstLogin'] = 0;
			$user['isPWReset'] = 0;
			$this->db->where('id', $userId);
			$this->db->update('user', $user);
			return true;
		} else {
			return false;
		}
	}

	function FetchUserPrefs($userId) {
		$s = "
			SELECT A.* 
			FROM user_pref A 
			WHERE userId = ?
		";
		$q = $this->db->query($s, array($userId));
		$r = $q->row();
		return $r;
	}

	function LogActivity($params) {
		$this->db->insert('user_activity', $params);
	}

	function FetchSuggestions($params) {
		$s = "
			SELECT A.*, B.displayName 
			FROM suggest A 
			LEFT JOIN user B 
			ON B.id = A.fromId 
			WHERE A.toId = ? 
			AND A.isDeleted = ? 
			ORDER BY updatedOn DESC 
		";
		$isDeleted = (isset($params['isDeleted'])) ? $params['isDeleted'] : 0;
		$q = $this->db->query($s, array($params['userId'], $isDeleted));
		if($q->num_rows()) {
			$suggestions['total'] = $q->num_rows();
			$suggestions['list'] = array();
			$i=1;
			foreach ($q->result() as $r) {
				$suggestions['list'][$i] = $r;
				$i++;
			}
			return $suggestions;
		} else {
			return null;
		}
	}

	function FetchSuggestionsSent($params) {
		$s = "
			SELECT A.*, B.displayName 
			FROM suggest A 
			LEFT JOIN user B 
			ON B.id = A.toId 
			WHERE A.fromId = ? 
			AND A.toId IS NOT NULL 
			AND A.isDeleted = 0 
			ORDER BY updatedOn DESC 
		";
		$q = $this->db->query($s, array($params['userId']));
		if($q->num_rows()) {
			$suggestions = array();
			$i=1;
			foreach ($q->result() as $r) {
				$suggestions[$i] = $r;
				$i++;
			}
			return $suggestions;
		} else {
			return null;
		}
	}

	function ReadSuggestion($params) {
		$data = array(
			'isRead' => 1,
			'updatedOn' => date('Y-m-d H:i:s'),
			'updatedBy' => $params['userId']
		);
		$this->db->where('id', $params['id']);
		if($this->db->update('suggest', $data)) {
			return true;
		} else {
			return null;
		}
	}

	function UnreadSuggestion($params) {
		$data = array(
			'isRead' => 0,
			'updatedOn' => date('Y-m-d H:i:s'),
			'updatedBy' => $params['userId']
		);
		$this->db->where('id', $params['id']);
		if($this->db->update('suggest', $data)) {
			return true;
		} else {
			return null;
		}
	}

	function DeleteSuggestion($params) {
		$data = array(
			'isDeleted' => 1,
			'updatedOn' => date('Y-m-d H:i:s'),
			'updatedBy' => $params['userId']
		);
		$this->db->where('id', $params['id']);
		if($this->db->update('suggest', $data)) {
			return true;
		} else {
			return null;
		}
	}

	function UndeleteSuggestion($params) {
		$data = array(
			'isDeleted' => 0,
			'updatedOn' => date('Y-m-d H:i:s'),
			'updatedBy' => $params['userId']
		);
		$this->db->where('id', $params['id']);
		if($this->db->update('suggest', $data)) {
			return true;
		} else {
			return null;
		}
	}

	function TorchSuggestion($params) {
		$data = array(
			'isDeleted' => 1
		);
		$this->db->where('id', $params['id']);
		if($this->db->update('suggest', $data)) {
			return true;
		} else {
			return null;
		}
	}

	function FetchAllHistory($params) {
		$start = (!isset($params['start'])) ? '0' : $params['start'];
		$limit = (!isset($params['limit'])) ? '10' : $params['limit'];
		$dateWhere='';
		if(isset($params['month']) && isset($params['year'])) {
			$dateWhere = " 
			AND updatedOn LIKE '".$params['year'].'-'.$params['month']."-%'";
		}
		$s = "
			SELECT A.* 
			FROM todo A 
			WHERE A.userId = ? 
			AND isBacklog = 0 
			AND isDeleted = 0 
			$dateWhere
			ORDER BY updatedOn DESC 
			LIMIT $start , $limit
		";
		$q = $this->db->query($s, array($params['id']));

		if($q->num_rows()) {
			$sT = "
				SELECT count(*) as totalHistory
				FROM todo 
				WHERE userId = ? 
				AND isBacklog = 0 
				AND isDeleted = 0 
				$dateWhere
			";
			$qT = $this->db->query($sT, array($params['id']));
			$rT = $qT->row();
			$todos['total'] = $rT->totalHistory;
			$i=1;
			$todos['list'] = array();
			foreach ($q->result() as $r) {
				$todos['list'][$i] = $r;
				$i++;
			}
			return $todos;
		} else {
			return null;
		}
	}

	function FetchMonthList($params) {
		$s = "
			SELECT DISTINCT(CONCAT(DATE_FORMAT(updatedOn, '%m'), '__', DATE_FORMAT(updatedOn, '%M'))) AS month 
			FROM todo 
			WHERE userId = ? 
			AND DATE_FORMAT(updatedOn, '%Y') = ?
			AND isDeleted = 0 
			AND isBacklog = 0 
			ORDER BY month DESC; 
		";
		$q = $this->db->query($s, array($params['id'], $params['year']));
		if($q->num_rows()) {
			$months = array();
			foreach ($q->result() as $r) {
				$dateParts = explode('__', $r->month);
				$months[$dateParts[0]] = $dateParts[1];
			}
			return $months;
		} else {
			return null;
		}
	}

}
?>