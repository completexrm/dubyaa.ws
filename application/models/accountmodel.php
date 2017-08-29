<?php

Class AccountModel extends CI_Model {

	function SnagRegistrationEmail($params) {
		$this->db->insert('_registration', $params);
	}

	function Create($params) {
		$dbDate = date('Y-m-d H:i:s');
		$account= array(
			'primaryEmail' => $params['emailAddress'],
			'trialDuration' => $params['trialDuration'],
			'displayName' => $params['displayName'],
			'registrationKey' => $params['registrationKey'],
			'createdOn' => $dbDate, 'updatedOn' => $dbDate, 
			'updatedBy' => 0, 'createdBy' => 0,
		);
		$this->db->insert('account', $account);
		$accountId = $this->db->insert_id();
		$acctPrefs = array(
			'accountId' => $accountId,
			'dubyaaMultiplier' => $params['defaults']['dubyaaMultiplier'],
			'maxOpen' => $params['defaults']['maxOpen']
		);
		$this->CreateDefaultPrefs($acctPrefs);
		$acctBadges = array(
			'accountId' => $accountId,
			'badges' => $params['defaults']['badges']
		);
		$this->CreateDefaultBadges($acctBadges);
		$this->CreateDefaultValues($accountId);
		return $accountId;
	}

	function WipeRegistrationEntry($email) {
		$this->db->where('emailAddress', $email);
		$this->db->delete('_registration');
	}

	function CreateDefaultPrefs($data) {
		$dbDate = date('Y-m-d H:i:s');
		$data['createdOn'] = $dbDate;
		$data['updatedOn'] = $dbDate;
		$this->db->insert('account_pref', $data);
	}

	function CreateDefaultBadges($data) {
		$dbDate = date('Y-m-d H:i:s');
		$data['createdOn'] = $dbDate;
		$data['updatedOn'] = $dbDate;
	}

	function CreateDefaultValues($accountId) {
		// $s = "
		// 	SELECT * 
		// 	FROM _value A 
		// 	ORDER BY value
		// ";
		// $q = $this->db->query($s);
		// $date = date('Y-m-d H:i:s');
		// foreach ($q->result() as $r) {
		// 	$data = array(
		// 		'accountId' => $accountId,
		// 		'value' => $r->value,	
		// 		'updatedOn' => $date			
		// 	);
		// 	$this->db->insert('account_value', $data);
		// }
	}
	
	function Search($params) {
		$s = "
			SELECT A.* 
			FROM account A 
			WHERE displayName LIKE ? 
			ORDER BY displayName
		";
	
		$q = $this->db->query($s, array('%'.$params['term'].'%'));
	
		if($q->num_rows()) {
			$accts = array();
			foreach ($q->result() as $r) {
				$accts[] = array(
					'id' => $r->id,
					'value' => $r->displayName,
					'label' => $r->displayName
				);
			}
			return $accts;
		} else {
			return null;
		}
	}

	function FetchAccount($id) {
		$s = "
			SELECT A.*, B.label as subscriptionLabel  
			FROM account A 
			LEFT JOIN _subscription B on B.id = A.subscriptionId  
			WHERE A.id = ?
		";
		$q = $this->db->query($s, array($id));
	
		if($q->num_rows()) {
			$r = $q->row();
			$account = array();
			foreach($r as $key => $val) {
				$account[$key] = $val;
			}
			if($r->isTrial) {
				$trialCurrent = round((time() - strtotime($account['createdOn'])) / 86400);
				$account['trialRemaining'] = $account['trialDuration'] - $trialCurrent;
				if($trialCurrent >= $account['trialDuration']) {
					$this->DeactivateAccount($id);
					$account['isTrial']=0;
				}
			}
			$account['prefs'] = $this->FetchAccountPrefs($account['id']);
			$account['payment'] = $this->FetchAccountPayment($account['id']);
			return $account;
		} else {
			return null;
		}	
	}

	function FetchAvailableCarriers() {
		$s = "
			SELECT A.* 
			FROM _mobile_carrier A 
			ORDER BY label
		";
	
		$q = $this->db->query($s);
	
		if($q->num_rows()) {
			$i=1;
			$carriers=array();
			foreach ($q->result() as $r) {
				$carriers[$i] = $r;
				$i++;
			}
			return $carriers;
		} else {
			return null;
		}
	}

	function UserLastActivityDate($params) {
		$s = "
			SELECT createdOn 
			FROM user_activity A 
			WHERE userId = ? 
			ORDER BY createdOn desc limit 1
		";
		$date = '';
		$q = $this->db->query($s, array($params['userId']));
		if($q->num_rows()) {
			$r = $q->row();
			$date = $r->createdOn;
			if(isset($params['tzOffset']) && !is_null($params['tzOffset']) && $params['tzOffset']!=='') {
				$tzOffsetSeconds = abs($params['tzOffset'] * 60);
				$dateTs = strtotime($r->createdOn) - $tzOffsetSeconds;
				$date = date('Y-m-d H:i:s', $dateTs);
			}
		}
		return $date;
	}

	function FetchUsers($params) {
		$s = "
			SELECT A.* 
			FROM user A 
			WHERE accountId = ? 
			AND isDeleted = 0 
			ORDER BY firstName, lastName
		";
	
		$q = $this->db->query($s, array($params['accountId']));
	
		if($q->num_rows()) {
			$i=1;
			$users=array();
			foreach ($q->result() as $r) {
				unset($r->userHash);
				$id = $r->id;
				if(isset($params['requesterPrefs'])) {
					$activityDateParams = array('userId' => $id, 'tzOffset'=>$params['requesterPrefs']->tzOffset);
					$r->lastActivityDate = $this->UserLastActivityDate($activityDateParams);
				}
				$users[$i] = $r;
				if(isset($params['date']) && $params['date'] != '' && $r->lastActivityDate) {
					if(strtotime($r->lastActivityDate) >= strtotime($params['date'])) {
						unset($users[$i]);
					}
				}
				$i++;
			}
			if(!count($users)) {
				return null;
			} else {
				return $users;
			}
		} else {
			return null;
		}
	}

	function FetchValues($params) {
		$s = "
			SELECT A.* 
			FROM account_value A 
			WHERE accountId = ? 
			AND isDeleted = 0 
			ORDER BY ptsValue, value
		";
	
		$q = $this->db->query($s, array($params['accountId']));
	
		if($q->num_rows()) {
			$i=1;
			$values=array();
			foreach ($q->result() as $r) {
				$values[$i] = $r;
				$i++;
			}
			return $values;
		} else {
			return null;
		}
	}

	function FetchLevels($params) {
		$s = "
			SELECT A.* 
			FROM account_level A 
			WHERE accountId = ? 
			AND isDeleted = 0 
			ORDER BY valueLo ASC
		";
	
		$q = $this->db->query($s, array($params['accountId']));
	
		if($q->num_rows()) {
			$i=1;
			$levels=array();
			foreach ($q->result() as $r) {
				$levels[$i] = $r;
				$i++;
			}
			return $levels;
		} else {
			return null;
		}
	}

	function FetchTeams($params) {
		$s = "
			SELECT A.*, B.displayName as teamLead  
			FROM team A 
			LEFT JOIN user B 
			ON B.id = A.teamLeadId 
			WHERE A.accountId = ? 
			and A.isDeleted = ?
			ORDER BY A.displayName
		";
	
		$q = $this->db->query($s, array($params['accountId'], 0));
	
		if($q->num_rows()) {
			$i=1;
			$teams=array();
			foreach ($q->result() as $r) {
				$teams[$i] = $r;
				$i++;
			}
			return $teams;
		} else {
			return null;
		}
	}

	function FetchSettings($params) {
		$s = "
			SELECT A.* 
			FROM account_pref A 
			WHERE accountId = ? 
			LIMIT 1
		";
	
		$q = $this->db->query($s, array($params['accountId']));
	
		if($q->num_rows()) {
			$r = $q->row();
			return $r;
		} else {
			return null;
		}
	}

	function FetchAccountPrefs($id) {
		$s=" 
			select A.*
			from account_pref A
			where accountId = ?
		";
		$q = $this->db->query($s, array($id));
		if($q->num_rows()) {
			$r = $q->row();
			return $r;
		} else {
			return null;
		}
	}

	function FetchAccountPayment($id) {
		$s=" 
			select A.*
			from account_payment_method A
			where accountId = ? 
			and isDeleted = 0
		";
		// $q = $this->db->query($s, array($id));
		// if($q->num_rows()) {
		// 	$r = $q->row();
		// 	return $r;
		// } 
	}

	function ValidateKey($params) {
		$s = "
			SELECT id , displayName
			FROM account A 
			WHERE registrationKey = ? 
			and isDeleted = 0 
		";
	
		$q = $this->db->query($s, array($params['key']));
	
		if($q->num_rows()) {
			$r = $q->row();
			$account = array(
				'id' => $r->id,
				'displayName' => $r->displayName
			);
			return $account;
		} else {
			return null;
		}	
	}

	function FetchId($params) {
		$s = "
			SELECT id 
			FROM account A 
			WHERE registrationKey = ? 
			and isDeleted = 0 
		";
	
		$q = $this->db->query($s, array($params['key']));
	
		if($q->num_rows()) {
			$r = $q->row();
			return $r->id;
		} else {
			return null;
		}	
	}

	function DeactivateAccount($id) {
		$data = array(
			'subscriptionId' => 0,
			'isActive' => 0,
			'isTrial' => 0,
			'updatedOn' => date('Y-m-d H:i:s')
		);
		$this->db->where('id', $id);
		$this->db->update('account', $data);
	}

	function SaveOnboard($params) {
		$account = $params['account'];
		$account['updatedOn'] = date('Y-m-d H:i:s');
		$id = $account['id'];
		unset($account['id']);
		$this->db->where('accountId', $id);
		if($this->db->update('account_pref', $account)) {
			return true;
		} else {
			return false;
		}
	}

	function SavePrimaryEmail($params) {
		$id = $params['accountId'];
		$params['updatedOn'] = date('Y-m-d H:i:s');
		$params['updatedBy'] = $params['userId'];
		unset($params['accountId']);
		unset($params['userId']);
		$this->db->where('id', $id);
		if($this->db->update('account', $params)) {
			return true;
		} else {
			return false;
		}
	}

	function FetchTodoActivity($params) {

		$actionTypeSql = "
			AND actionType IN ('created', 'won', 'instawin')
		";
		if(isset($params['onlyWins']) && $params['onlyWins']==1) {
			$actionTypeSql = "
				AND (
					actionType = 'won' 
					OR 
					actionType = 'instawin'
				)
			";	
		}

		$tS = "
			SELECT count(*) as totalActivity FROM 
			user_activity 
			WHERE accountId = ? 
			AND actionTable = ? 
			$actionTypeSql
		";

		$tQ = $this->db->query($tS, array($params['a'], 'todo'));
		$tR = $tQ->row();
		
		$activity=array();
		$activity['totalActivity'] = $tR->totalActivity;

		$CI =& get_instance();
		$CI->load->model('TodoModel');
		$start = (!isset($params['start'])) ? '0' : $params['start'];
		$limit = (!isset($params['limit'])) ? '0' : $params['limit'];
		$s = "
			SELECT A.id as activityId, A.userId, A.createdOn, A.actionId, A.actionType, B.displayName, B.photoPath, C.label as todo, C.id as todoId  
			FROM user_activity A 
			LEFT JOIN user B 
			ON B.id = A.userId  
			LEFT JOIN todo C  
			ON C.id = A.actionId   
			WHERE B.accountId = ? 
			AND A.actionTable = ? 
			$actionTypeSql
			ORDER BY A.createdOn desc
			LIMIT $start , $limit
		";
	
		$q = $this->db->query($s, array($params['a'], 'todo', date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')));
	
		if($q->num_rows()) {
			$i=1;
			foreach ($q->result() as $r) {
				$reactionParams['userId'] = $params['u'];
				$reactionParams['todoId'] = $r->todoId;
				$r->reaction = $this->FetchUserReaction($reactionParams);
				$r->reactionTotals = $CI->TodoModel->FetchTodoComments(array('id'=>$r->todoId));
				$activity['list'][$i] = $r;
				$i++;
			}
			return $activity;
		} else {
			return null;
		}
	}

	function FetchUserReaction($params) {

		$s = "
			SELECT id, toId, isGood, isBad, isDispute, updatedOn 
			FROM user_reaction A 
			WHERE A.fromId = ? 
			AND A.todoId = ? 
			LIMIT 1
		";

		$r['id'] = null;
		$r['toId'] = null;
		$r['isGood'] = null;
		$r['isBad'] = null;
		$r['isDispute'] = null;
		$r['updatedOn'] = null;

		$q = $this->db->query($s, array($params['userId'], $params['todoId']));
		
		if($q->num_rows()) {
			$r = $q->row();
		}
		return $r;
	}

	function FetchMonthList($params) {
		$s = "
			SELECT DISTINCT(CONCAT(DATE_FORMAT(updatedOn, '%m'), '__', DATE_FORMAT(updatedOn, '%M'))) AS month 
			FROM todo 
			WHERE accountId = ? 
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

	function CreateLevel($params) {
		$params['createdBy'] = $params['userId'];
		$params['updatedBy'] = $params['userId'];
		$params['updatedOn'] = date('Y-m-d H:i:s');
		unset($params['userId']);
		if(!$this->db->insert('account_level', $params)) {
			return null;
		} else {
			return true;
		}
	}

	function UpdateLevel($params) {
		$params['updatedBy'] = $params['userId'];
		$params['updatedOn'] = date('Y-m-d H:i:s');
		$this->db->where('id', $params['id']);
		unset($params['userId']);
		unset($params['id']);
		if(!$this->db->update('account_level', $params)) {
			return null;
		} else {
			return true;
		}
	}

	function DeleteLevel($params) {
		$params['updatedBy'] = $params['userId'];
		$params['updatedOn'] = date('Y-m-d H:i:s');
		$params['isDeleted'] = 1;
		$this->db->where('id', $params['id']);
		unset($params['userId']);
		unset($params['id']);
		if(!$this->db->update('account_level', $params)) {
			return null;
		} else {
			return true;
		}
	}

	function FetchSuggestions($params) {
		$s = "
			SELECT A.*, B.displayName 
			FROM suggest A 
			LEFT JOIN user B 
			ON B.id = A.fromId 
			WHERE A.accountId = ? 
			AND A.toId IS NULL 
			AND A.isDeleted = 0 
			ORDER BY updatedOn DESC 
		";
		$q = $this->db->query($s, array($params['accountId']));
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

}
?>