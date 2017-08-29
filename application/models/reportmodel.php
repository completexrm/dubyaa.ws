<?php

Class ReportModel extends CI_Model {

	function UserPoints($params) {
		$userId = mysql_real_escape_string($params['userId']);
		// Month-to-Date points
		$dateM['begin'] = date('Y').'-'.date('m').'-01';
		$dateM['end'] = date('Y').'-'.date('m').'-'.date('t');
		$dateM['month'] = date('m');
		$dateM['year'] = date('Y');
		$points['month'] = $this->FetchUserPoints($userId, $dateM, 'M');
		// Quarter-to-Date points
		$dateQ['begin']=date('Y').'-'.$params['quarterInfo']['begin'].'-01';
		$dateQ['end']=date('Y').'-'.$params['quarterInfo']['end'].'-'.date('t', strtotime(date('Y').'-'.$params['quarterInfo']['end'].'-01'));
		$dateQ['year'] = date('Y');
		$dateQ['quarter'] = $params['quarterInfo']['quarter'];
		$points['quarter'] = $this->FetchUserPoints($userId, $dateQ, 'Q');
		// Year-to-Date points
		$dateY['begin'] = date('Y').'-01-01';
		$dateY['end'] = date('Y').'-12-31';
		$dateY['year'] = date('Y');
		$points['year'] = $this->FetchUserPoints($userId, $dateY, 'Y');

		return $points;
	}

	function FetchUserPoints($userId, $date, $type) {
		$s = "
			select sum(pointsAwarded) as totalPoints 
			from todo 
			where userId = ? 
			and isDubyaa = 1 
			and isDeleted = 0 
			and dubyaaDate between ? and ?;
		";
		$q = $this->db->query($s, array($userId, $date['begin'], $date['end']));
		if($q->num_rows()) {
			$r = $q->row();
			$rtn['pts'] = (is_null($r->totalPoints)) ? 0 : $r->totalPoints;
			$sP = "
				select id, goalPts 
				from user_goal 
				where userId = $userId 
				and goalType = '$type' 
				and goalYear = ".$date['year']." 
			";
			switch($type) {
				case 'M':
					$sP .= "
						and goalMonth = ".$date['month']."
					";
					break;
				case 'Q':
					$sP .= "
						and goalQuarter = ".$date['quarter']."
					";
					break;
			} 
			$qP = $this->db->query($sP);
			if($qP->num_rows()) {
				$rP = $qP->row();
				$rtn['goal'] = $rP->goalPts;
				$rtn['goalId'] = $rP->id;
				$rtn['percent'] = number_format(($rtn['pts'] / $rtn['goal'])*100, 2);
			}
			return $rtn;
		} else {
			return 0;
		}
	}

	function FetchUserStats($params) {
		
		// setup time parameters
		$firstDay = $params['year'].'-'.$params['month'].'-01';
		$lastDayOfMonth = date('t', strtotime($firstDay));
		$lastDay = $params['year'].'-'.$params['month'].'-'.$lastDayOfMonth;
		
		// get total dubyaas
		$s = "
			SELECT count(*) as totalWins
			FROM todo 
			WHERE dubyaaDate 
			BETWEEN ? AND ? 
			AND userId = ?
			AND isDeleted = 0 
			AND isDubyaa = 1 
			AND isBacklog = 0 
		";
		$sqlWhere = array(
			$firstDay,
			$lastDay,
			$params['userId']
		);
		$q = $this->db->query($s, $sqlWhere);
		$rtn['totalWins'] = null;
		if($q->num_rows()) {
			$r = $q->row();
			$rtn['totalWins'] = $r->totalWins;
		}

		// get total created
		$s = "
			SELECT count(*) as totalCreated 
			FROM todo 
			WHERE createdOn  
			BETWEEN ? AND ? 
			AND userId = ?
			AND isDeleted = 0 
			AND isBacklog = 0
		";
		$sqlWhere = array(
			$firstDay,
			$lastDay,
			$params['userId']
		);
		$q = $this->db->query($s, $sqlWhere);
		$rtn['totalCreated'] = null;
		if($q->num_rows()) {
			$r = $q->row();
			$rtn['totalCreated'] = $r->totalCreated;
		}

		$s = "
			SELECT sum(isGood) as totalPositive, sum(isBad) as totalNegative 
			FROM user_reaction 
			WHERE createdOn  
			BETWEEN ? AND ? 
			AND fromId = ?
			AND isDeleted = 0 
		";
		$sqlWhere = array(
			$firstDay,
			$lastDay,
			$params['userId']
		);
		$q = $this->db->query($s, $sqlWhere);
		$rtn['totalPositive'] = null;
		$rtn['totalNegative'] = null;
		if($q->num_rows()) {
			$r = $q->row();
			$rtn['totalNegative'] = $r->totalNegative;
			$rtn['totalPositive'] = $r->totalPositive;
		};

		for($i=1; $i<=$lastDayOfMonth;$i++) {
			$d = sprintf('%02d', $i);
			$days[$i] = $params['year'].'-'.$params['month'].'-'.$d;
		}

		$rtn['wins'] = array();
		foreach($days as $id => $day) {
			$rtn['wins'][$day] = 0;
			$s = "
				SELECT count(*) as totalDayWins
				FROM todo A 
				WHERE isDubyaa = 1    
				AND dubyaaDate like '".$day." %' 
				AND userId = ".$params['userId']."
				AND isDeleted = 0 
				AND isBacklog = 0 
			";
			$q = $this->db->query($s);
			if($q->num_rows()) {
				$i=1;
				foreach ($q->result() as $r) {
					$rtn['wins'][$day] = $r->totalDayWins;
					$i++;
				}
			}	
		}
		$rtn['created'] = array();
		foreach($days as $id => $day) {
			$rtn['created'][$day] = 0;
			$s = "
				SELECT count(*) as totalDayWins
				FROM todo A 
				WHERE isDubyaa = 1    
				AND createdOn  
				like '".$day." %' 
				AND userId = ".$params['userId']."
				AND isDeleted = 0 
				AND isBacklog = 0 
			";
			$q = $this->db->query($s);
			if($q->num_rows()) {
				$i=1;
				foreach ($q->result() as $r) {
					$rtn['created'][$day] = $r->totalDayWins;
					$i++;
				}
			}	
		}
		return $rtn;
	}

	function UserGoals($params) {
		foreach($params as $key=>$val) {
			$params[$key] = mysql_real_escape_string($val);
		}
		$s = "
			SELECT A.* 
			FROM user_goal A 
			WHERE (
				goalType = 'M' 
				AND goalMonth = ?
			)
			OR (
				goalType = 'Q' 
				AND goalQuarter = ?
			) 
			OR (
				goalType = 'Y' 
			)
			AND goalYear = ?
			AND userId = ? 
			AND isDeleted = 0 
			ORDER BY goalPts desc;
		";
		$q = $this->db->query($s, array($params['month'], $params['quarter'], $params['year'], $params['userId']));
		if($q->num_rows()) {
			$i=1;
			$goals = array();
			foreach ($q->result() as $r) {
				if($r->goalType=='M') {
					$goals['month'] = $r->goalPts;
				}
				if($r->goalType=='Q') {
					$goals['quarter'] = $r->goalPts;
				}
				if($r->goalType=='Y') {
					$goals['year'] = $r->goalPts;
				}
				$i++;
			}
			return $goals;
		} else {
			return null;
		}
	}

	function UserSocial($params) {
		$s1 = "
			SELECT sum(isGood) as totalGood, sum(isBad) as totalBad, sum(isDispute) as totalDispute
			FROM user_reaction 
			WHERE toId = ? 
			AND toDoId > 0 
		";
		$q1 = $this->db->query($s1, array($params['userId']));
		$r1 = $q1->row();
		$s2 = "
			SELECT sum(isGood) as totalGoodGiven, sum(isBad) as totalBadGiven, sum(isDispute) as totalDisputeGiven 
			FROM user_reaction 
			WHERE fromId = ? 
			AND toDoId > 0 
		";
		$q2 = $this->db->query($s2, array($params['userId']));
		$r2 = $q2->row();

		$rtn['totalGood'] = (!is_null($r1->totalGood)) ? $r1->totalGood : 0;
		$rtn['totalBad'] = (!is_null($r1->totalBad)) ? $r1->totalBad : 0;
		$rtn['totalDispute'] = (!is_null($r1->totalDispute)) ? $r1->totalDispute : 0;
		$rtn['totalGoodGiven'] = (!is_null($r2->totalGoodGiven)) ? $r2->totalGoodGiven : 0;
		$rtn['totalBadGiven'] = (!is_null($r2->totalBadGiven)) ? $r2->totalBadGiven : 0;
		$rtn['totalDisputeGiven'] = (!is_null($r2->totalDisputeGiven)) ? $r2->totalDisputeGiven : 0;

		return $rtn;
	}

	function UserPerformanceDay($params) {
		$s = "
			select scoreTotal 
			from user_productivity_score 
			where userId = ? 
			and date = ?
		";
		$q = $this->db->query($s, array($params['userId'], $params['date']));
		$day=0;
		if($q->num_rows()) {
			$r = $q->row();
			$day = $r->scoreTotal;
		}
		return $day;
	}

	function UserPerformanceMonth($params) {
		$s = "
			select avg(scoreTotal) as monthAvg 
			from user_productivity_score 
			where userId = ? 
			and date like '".$params['year']."-".$params['month']."-%';
		";
		$q = $this->db->query($s, array($params['userId']));
		$r = $q->row();
		$rtn['performance'] = (is_null($r->monthAvg)) ? 0 : $r->monthAvg;
		$previousDay = date('Y-m-d', strtotime('-1 days'));
		$previousParams = array(
			'userId' => $params['userId'],
			'date' => $previousDay
		);
		$previous = $this->UserPerformanceDay($previousParams);
		$rtn['trendingUp'] = false;
		$rtn['previous'] = $previous;
		$rtn['previousDiff'] = ($previous - $rtn['performance']);
		if($rtn['performance'] >= $previous) {
			$rtn['trendingUp'] = true;
		}
		return $rtn;
	}

	function AccountPerformanceMonth($params) {
		$s = "
			select avg(scoreTotal) as monthAvg 
			from user_productivity_score 
			where accountId = ? 
			and date like '".$params['year']."-".$params['month']."-%';
		";
		$q = $this->db->query($s, array($params['accountId']));
		$r = $q->row();
		$performance = (is_null($r->monthAvg)) ? 0 : $r->monthAvg;
		return $performance;
	}

	function FetchAccountLevel($params) {
		
		// $params['score'] = $params['score'] * 1000;

		$s = "
			select label from account_level 
			where accountId = ? 
			and ? >= valueLo 
			and ? <= valueHi 
			and isDeleted = 0;
		";
		$q = $this->db->query($s, array($params['accountId'], $params['score'], $params['score']));
		if($q->num_rows() > 0) {
			$r = $q->row();
			return $r->label;
		} else {
			return null;
		}
	}

	function FetchMonthReactions($params) {
		$s = "
			select sum(isGood) as totalGood, sum(isBad) as totalBad, sum(isDispute) as totalDispute 
			from user_reaction 
			where toId = ? 
			and isDeleted = 0 
			and updatedOn like '".$params['year']."-".$params['month']."-%' 
		";
		$q = $this->db->query($s, array($params['userId']));
		$reactions = array();
		if($q->num_rows()) {
			$reactions = $q->row();
		}
		return $reactions;
	}

	function AccountLeaderboard($params) {
		$s = "
			SELECT (SUM(pointsAwarded) + (
				SELECT COUNT(isGood) * 0.25 
				FROM user_reaction 
				WHERE toId = A.userId 
				AND isGood = 1 
				AND isDeleted = 0 
				AND updatedOn LIKE '".$params['year']."-".$params['month']."-%' 
			) - (
				SELECT COUNT(isBad) * 0.25 
				FROM user_reaction 
				WHERE toId = A.userId
				AND isBad = 1 
				AND isDeleted = 0 
				AND updatedOn LIKE '".$params['year']."-".$params['month']."-%'
			) + (
				SELECT COUNT(*) * 0.1
				FROM user_reaction 
				WHERE fromId = A.userId
				AND isDeleted = 0 
				AND updatedOn LIKE '".$params['year']."-".$params['month']."-%'
			)) as monthWins, B.displayName, B.photoPath, B.id, B.tagLine 
			FROM todo A 
			LEFT JOIN user B 
			ON B.id = A.userId 
			WHERE A.accountId = ? 
			AND A.dubyaaDate like '".$params['year']."-".$params['month']."-%' 
			AND B.isDeleted = 0 
			AND A.isDubyaa = 1 
			AND A.id NOT IN (
				SELECT todoId 
				FROM user_reaction R
				WHERE R.todoId = A.id 
				AND R.isDeleted = 0 
				AND R.isDispute = 1 
				AND R.updatedOn LIKE '".$params['year']."-".$params['month']."-%'
			)
			group by A.userId
			order by monthWins desc
			;
		";
		$q = $this->db->query($s, array($params['accountId']));
		if($q->num_rows()) {
			$i=1;
			$leaderboard = array();
			foreach ($q->result() as $r) {
				$paramsLevel = array(
					'score' => $r->monthWins,
					'accountId' => $params['accountId']
				);
				$params['userId'] = $r->id;
				$r->level = $this->fetchAccountLevel($paramsLevel);
				$r->reactions = $this->FetchMonthReactions($params);
				$r->monthWins = round($r->monthWins, 0, PHP_ROUND_HALF_UP);
				$leaderboard[$i] = $r;
				$i++;
			}
			return $leaderboard;
		} else {
			return null;
		}
	}

}
?>