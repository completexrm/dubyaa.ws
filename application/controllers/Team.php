<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Team extends CI_Controller {

/* ---------------------------------------------------------------------------- *
	DUBYAA CLASS "Team"
	Author: Brian Garstka
	EndPoint: /Account
	This class handles all operations for teams
* ---------------------------------------------------------------------------- */

	function __construct() {
		parent::__construct();
		// COMMON Model
		$this->load->model('CommonModel','',TRUE);
		// TEAM Model
		$this->load->model('TeamModel','',TRUE);
		// TODO Model
		$this->load->model('TodoModel','',TRUE);
		// USER Model
		$this->load->model('UserModel','',TRUE);
		// App-level Exception messaging :: /application/config/dubyaa_variables.php
		$this->exceptions = $this->config->item('exceptions');
		// App-level variables :: /application/config/dubyaa_variables.php
		$this->dubyaaConfig = $this->config->item('dubyaa');
	}

/* ---------------------------------------------------------------------------- *
	ToDo Index path
	Required :: NA
	Returns: 500 Server Error :: Don't allow direct access to Class
	Endpoint: /Todo
	Method: N/A
 * ---------------------------------------------------------------------------- */
	function index() {
		http_response_code(500);
		echo $this->exceptions['noPage'];
	}

/* ---------------------------------------------------------------------------- *
	SEARCH FOR USER LIST OF TEAMS
	Required :: (id) 
	Returns: SUCCESS :: TRUE / FALSE :: JSON OBJ FOR TEAMS
	Endpoint: /Team/UserList
	Method: GET
 * ---------------------------------------------------------------------------- */
	function UserList () {
		$params = $_GET;
		try {
			// Missing required information
			if
				(
					(is_null($params) && !is_array($params)) 
					|| !isset($params['id']) 
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
			// Try to create
			if(!$teams = $this->TeamModel->UserList($params)) {
				throw new Exception($this->exceptions['noRecord']);
			}
			$data['rtn']['success'] = true;
			$data['rtn']['teams'] = $teams;
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			echo $e->getMessage();
		}	
	}

/* ---------------------------------------------------------------------------- *
	GET TEAM ROSTER
	Required :: (id) 
	Returns: SUCCESS :: TRUE / FALSE :: JSON OBJ FOR TEAM
	Endpoint: /Team/FetchRoster
	Method: GET
 * ---------------------------------------------------------------------------- */
	function FetchRoster () {
		$params = $_GET;
		try {
			// Missing required information
			if
				(
					(is_null($params) && !is_array($params)) 
					|| !isset($params['id']) 
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
			// Try to create
			if(!$users = $this->TeamModel->FetchRoster($params)) {
				throw new Exception($this->exceptions['noRecord']);
			}
			$data['rtn']['success'] = true;
			$data['rtn']['users'] = $users;
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			echo $e->getMessage();
		}	
	}

/* ---------------------------------------------------------------------------- *
	UPDATE TEAM ROSTER
	Required :: (teamId) (userId) (include) --> 1 / 0 
	Returns: SUCCESS :: TRUE / FALSE
	Endpoint: /Team/UpdateRoster
	Method: GET
 * ---------------------------------------------------------------------------- */
	function UpdateRoster () {
		$params = $this->input->post();
		try {
			// Missing required information
			if
				(
					(is_null($params) && !is_array($params)) 
					|| !isset($params['teamId']) 
					|| !isset($params['userId']) 
					|| !isset($params['include']) 
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
			// Try to create
			if($this->TeamModel->UpdateRoster($params)) {
				$data['rtn']['success'] = true;
			} else {
				$data['rtn']['success'] = false;
			}
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			echo $e->getMessage();
		}	
	}

/* ---------------------------------------------------------------------------- *
	CREATE NEW TEAM
	Required :: (userId) (accountId) (displayName) 
	Returns: SUCCESS :: TRUE / FALSE
	Note: The default teamLeadId will be (userId)
	Endpoint: /Team/Create
	Method: POST
 * ---------------------------------------------------------------------------- */
	function Create () {
		$params = $this->input->post();
		try {
			// Missing required information
			if
				(
					(is_null($params) && !is_array($params)) 
					|| !isset($params['userId']) 
					|| !isset($params['accountId']) 
					|| !isset($params['displayName']) 
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
			// Try to create
			if(!$this->TeamModel->Create($params)) {
				throw new Exception($this->exceptions['noCreate']);
			}
			$data['rtn']['success'] = true;
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			echo $e->getMessage();
		}	
	}

/* ---------------------------------------------------------------------------- *
	DELETE TEAM
	Required :: (teamId) (userId)
	Returns: SUCCESS :: TRUE / FALSE
	Endpoint: /Team/Delete
	Method: POST
 * ---------------------------------------------------------------------------- */
	function Delete () {
		$params = $this->input->post();
		try {
			// Missing required information
			if
				(
					(is_null($params) && !is_array($params)) 
					|| !isset($params['userId']) 
					|| !isset($params['teamId']) 
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
			// Try to create
			if(!$this->TeamModel->Delete($params)) {
				throw new Exception($this->exceptions['noDelete']);
			}
			$data['rtn']['success'] = true;
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			echo $e->getMessage();
		}	
	}

/* ---------------------------------------------------------------------------- *
	UPDATE TEAM
	Required :: (teamId) (displayName) (userId)
	Returns: SUCCESS :: TRUE / FALSE
	Endpoint: /Team/Update
	Method: POST
 * ---------------------------------------------------------------------------- */
	function Update () {
		$params = $this->input->post();
		try {
			// Missing required information
			if
				(
					(is_null($params) && !is_array($params)) 
					|| !isset($params['displayName']) 
					|| !isset($params['teamId']) 
					|| !isset($params['userId']) 
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
			// Try to create
			if(!$this->TeamModel->Update($params)) {
				throw new Exception($this->exceptions['noUpdate']);
			}
			$data['rtn']['success'] = true;
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			echo $e->getMessage();
		}	
	}

/* ---------------------------------------------------------------------------- *
	DELETE TEAM
	Required :: (teamId) (teamLeadId) (userId)
	Returns: SUCCESS :: TRUE / FALSE
	Endpoint: /Team/ChangeTeamLead
	Method: POST
 * ---------------------------------------------------------------------------- */
	function ChangeTeamLead () {
		$params = $this->input->post();
		try {
			// Missing required information
			if
				(
					(is_null($params) && !is_array($params)) 
					|| !isset($params['userId']) 
					|| !isset($params['teamLeadId']) 
					|| !isset($params['teamId']) 
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
			// Try to create
			if(!$this->TeamModel->ChangeTeamLead($params)) {
				throw new Exception($this->exceptions['noDelete']);
			}
			$data['rtn']['success'] = true;
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			echo $e->getMessage();
		}	
	}

/* ---------------------------------------------------------------------------- *
	GET ALL TEAM ACTIVITY
	Required :: (id)
	Returns: SUCCESS :: TRUE / FALSE :: OBJ WITH USERS AND ACTIVITY PER
	Endpoint: /Team/GetActivity
	Method: GET
 * ---------------------------------------------------------------------------- */
	function GetActivity () {
		$params = $_GET;
		try {
			// Missing required information
			if
				(
					(is_null($params) && !is_array($params)) 
					|| !isset($params['id']) 
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
			if($users = $this->TeamModel->FetchTeamRoster($params)) {
				$data['rtn']['success'] = true;
				$data['rtn']['users'] = $users;
			} else {
				$data['rtn']['success'] = false;
			}
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			echo $e->getMessage();
		}	
	}

}
/* End of file Team.php */
/* Location: ./application/controllers/Team.php */