<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Report extends CI_Controller {

/* ---------------------------------------------------------------------------- *
	DUBYAA CLASS "Report"
	Author: Brian Garstka
	EndPoint: /Report
	This class deals with all operations around reporting, stats, etc.
* ---------------------------------------------------------------------------- */

	function __construct() {
		parent::__construct();
		// COMMON Model
		$this->load->model('CommonModel','',TRUE);
		// REPORT Model
		$this->load->model('ReportModel','',TRUE);
		// USER Model
		$this->load->model('UserModel','',TRUE);
		// App-level Exception messaging :: /application/config/dubyaa_variables.php
		$this->exceptions = $this->config->item('exceptions');
	}

/* ---------------------------------------------------------------------------- *
	Report Index path
	Required :: NA
	Returns: 500 Server Error :: Don't allow direct access to Class
	Endpoint: /Report
	Method: N/A
 * ---------------------------------------------------------------------------- */
	function index() {
		http_response_code(500);
		echo $this->exceptions['noPage'];
	}

/* ---------------------------------------------------------------------------- *
	GET USER POINTS TO DATE
	Required :: (userId)
	Returns: SUCCESS :: TRUE / FALSE :: JSON OBJ WITH PTS FOR MONTH, QTR, YR
	Endpoint: /Report/UserPoints
	Method: GET
 * ---------------------------------------------------------------------------- */
	function UserPoints () {
		$params = $_GET;
		try {
			// Missing required information
			if
				(
					(is_null($params) && !is_array($params)) 
					|| !isset($params['userId']) 
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
			// Setup current quarter variable
			$params['quarterInfo'] = $this->CommonModel->CurrentQuarter();
			if(!$points = $this->ReportModel->UserPoints($params)) {
				throw new Exception($this->exceptions['noFetch']);
			}
			$data['rtn']['success'] = TRUE;
			$data['rtn']['points'] = $points;
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			echo $e->getMessage();
			log_message('error', 'UserID :: '.$params['userId'].' :: '.$e->getMessage());
		}	
	}

/* ---------------------------------------------------------------------------- *
	GET USER GOALS
	Required :: (userId) (year : YYYY)
	Returns: SUCCESS :: TRUE / FALSE :: JSON OBJ WITH GOALS
	Endpoint: /Report/UserGoals
	Method: GET
 * ---------------------------------------------------------------------------- */
	function UserGoals () {
		$params = $_GET;
		try {
			// Missing required information
			if
				(
					(is_null($params) && !is_array($params)) 
					|| !isset($params['year']) 
					|| !isset($params['userId']) 
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
			$qtr = $this->CommonModel->CurrentQuarter();
			$params['quarter'] = $qtr['quarter'];
			if(!$goals = $this->ReportModel->UserGoals($params)) {
				throw new Exception($this->exceptions['noFetch']);
			}
			$data['rtn']['success'] = TRUE;
			$data['rtn']['goals'] = $goals;
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			echo $e->getMessage();
			log_message('error', 'UserID :: '.$params['userId'].' :: '.$e->getMessage());
		}	
	}

/* ---------------------------------------------------------------------------- *
	GET USER % FOR A MONTH
	Required :: (userId) (month : MM) (year : YYYY) (accountId)
	Returns: SUCCESS :: TRUE / FALSE :: JSON OBJ WITH %
	Endpoint: /Report/UserPerformanceMonth
	Method: GET
 * ---------------------------------------------------------------------------- */
	function UserPerformanceMonth () {
		$params = $_GET;
		try {
			// Missing required information
			if
				(
					(is_null($params) && !is_array($params)) 
					|| !isset($params['month']) 
					|| !isset($params['year']) 
					|| !isset($params['userId']) 
					|| !isset($params['accountId']) 
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
			$qtr = $this->CommonModel->CurrentQuarter();
			$params['quarter'] = $qtr['quarter'];
			$userPerformance = $this->ReportModel->UserPerformanceMonth($params);
			$acctPerformance = $this->ReportModel->AccountPerformanceMonth($params);
			$data['rtn']['success'] = TRUE;
			$data['rtn']['performance'] = $userPerformance['performance'];
			$data['rtn']['trendingUp'] = $userPerformance['trendingUp'];
			$data['rtn']['previous'] = $userPerformance['previous'];
			$data['rtn']['previousDiff'] = $userPerformance['previousDiff'];
			$data['rtn']['accountPerformance'] = $acctPerformance;
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			echo $e->getMessage();
			log_message('error', 'UserID :: '.$params['userId'].' :: '.$e->getMessage());
		}	
	}

/* ---------------------------------------------------------------------------- *
	GET ALL USER STATS FOR A MONTH
	Required :: (userId) (month : MM) (year : YYYY)
	Returns: SUCCESS :: TRUE / FALSE :: JSON OBJ WITH STATS
	Endpoint: /Report/FetchUserStats
	Method: GET
 * ---------------------------------------------------------------------------- */
	function FetchUserStats () {
		$params = $_GET;
		try {
			// Missing required information
			if
				(
					(is_null($params) && !is_array($params)) 
					|| !isset($params['month']) 
					|| !isset($params['year']) 
					|| !isset($params['userId']) 
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
			$qtr = $this->CommonModel->CurrentQuarter();
			$params['quarter'] = $qtr['quarter'];
			$stats = $this->ReportModel->FetchUserStats($params);
			$data['rtn']['success'] = TRUE;
			$data['rtn']['stats'] = $stats;
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			echo $e->getMessage();
			log_message('error', 'UserID :: '.$params['userId'].' :: '.$e->getMessage());
		}	
	}

/* ---------------------------------------------------------------------------- *
	GET ACCOUNT LEADERS  FOR A MONTH
	Required :: (accountId) (month : MM) (year : YYYY)
	Returns: SUCCESS :: TRUE / FALSE :: JSON OBJ WITH %
	Endpoint: /Report/AccountUserPerformanceMonth
	Method: GET
 * ---------------------------------------------------------------------------- */
	function AccountLeaderboard () {
		$params = $_GET;
		try {
			// Missing required information
			if
				(
					(is_null($params) && !is_array($params)) 
					|| !isset($params['month']) 
					|| !isset($params['year']) 
					|| !isset($params['accountId']) 
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
			if($accountLeaderBoard = $this->ReportModel->AccountLeaderboard($params)) {
				$data['rtn']['success'] = TRUE;
				$data['rtn']['leaderboard'] = $accountLeaderBoard;
			} else {
				$data['rtn']['success'] = FALSE;
			}
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			echo $e->getMessage();
			log_message('error', 'UserID :: '.$params['userId'].' :: '.$e->getMessage());
		}	
	}

/* ---------------------------------------------------------------------------- *
	GET USER % FOR A MONTH
	Required :: (userId)
	Returns: SUCCESS :: TRUE / FALSE :: # +/-
	Endpoint: /Report/UserSocial
	Method: GET
 * ---------------------------------------------------------------------------- */
	function UserSocial () {
		$params = $_GET;
		try {
			// Missing required information
			if
				(
					(is_null($params) && !is_array($params)) 
					|| !isset($params['userId']) 
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
			$social = $this->ReportModel->UserSocial($params);
			$data['rtn']['success'] = TRUE;
			$data['rtn']['social'] = $social;
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			echo $e->getMessage();
			log_message('error', 'UserID :: '.$params['userId'].' :: '.$e->getMessage());
		}	
	}

}

/* End of file Report.php */
/* Location: ./application/controllers/Report.php */