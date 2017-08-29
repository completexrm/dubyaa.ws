<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Account extends CI_Controller {

/* ---------------------------------------------------------------------------- *
	DUBYAA CLASS "Account"
	Author: Brian Garstka
	EndPoint: /Account
	This class handles all operations for accounts
* ---------------------------------------------------------------------------- */

	function __construct() {
		parent::__construct();
		// COMMON Model
		$this->load->model('CommonModel','',TRUE);
		// ACCOUNT Model
		$this->load->model('AccountModel','',TRUE);
		// USER Model
		$this->load->model('UserModel','',TRUE);
		// TEAM Model
		$this->load->model('TeamModel','',TRUE);
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
	CAPTURE EMAIL ADDRESS ON REGISTRATION
	Required :: (emailAddress) 
	Returns: SUCCESS :: TRUE / FALSE :: JSON OBJ FOR ACCOUNT
	Endpoint: /Account/SnagEmail
	Method: POST
	Notes: On failure we don't want to stall registration so this is loose
 * ---------------------------------------------------------------------------- */
	function SnagEmail () {
		$params = $this->input->post();
		$this->AccountModel->SnagRegistrationEmail($params);
	}

/* ---------------------------------------------------------------------------- *
	SEARCH FOR ACCOUNT NAME
	Required :: (search term) 
	Returns: SUCCESS :: TRUE / FALSE :: JSON OBJ FOR ACCOUNT
	Endpoint: /Account/Search
	Method: GET
 * ---------------------------------------------------------------------------- */
	function Search () {
		$params = $_GET;
		try {
			// Missing required information
			if
				(
					(is_null($params) && !is_array($params)) 
					|| !isset($params['term']) 
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
			// Try to create
			if(!$accounts = $this->AccountModel->Search($params)) {
				throw new Exception($this->exceptions['noRecord']);
			}
			$data['results'] = $accounts;
			$this->load->view('jsonAutoComplete', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			echo $e->getMessage();
		}	
	}

/* ---------------------------------------------------------------------------- *
	VALIDATE REGISTRATION KEY
	Required :: (16 character key) 
	Returns: SUCCESS :: TRUE / FALSE :: accountId
	Endpoint: /Account/ValidateKey
	Method: POST
 * ---------------------------------------------------------------------------- */
	function ValidateKey () {
		$params = $this->input->post();
		try {
			// Missing required information
			if
				(
					(is_null($params) && !is_array($params)) 
					|| !isset($params['key']) 
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
			// Try to create
			if(!$account = $this->AccountModel->ValidateKey($params)) {
				throw new Exception($this->exceptions['noRecord']);
			}
			$data['rtn']['success'] = TRUE;
			$data['rtn']['id'] = $account['id'];
			$data['rtn']['displayName'] = $account['displayName'];
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			http_response_code(500);
			echo $e->getMessage();
		}	
	}

/* ---------------------------------------------------------------------------- *
	EMAIL ADDRESS AVAILABILITY
	Required :: (email address) 
	Returns: SUCCESS :: TRUE / FALSE
	Endpoint: /Account/EmailAvailable
	Method: GET
 * ---------------------------------------------------------------------------- */
	function EmailAvailable () {
		$params = $_GET;
		try {
			// Missing required information
			if
				(
					(is_null($params) && !is_array($params)) 
					|| !isset($params['email']) 
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
			// Try to create
			if($this->UserModel->EmailAvailable($params)) {
				$data['rtn']['success'] = true;
			} else {
				$data['rtn']['success'] = false;
			}
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			http_response_code(500);
			echo $e->getMessage();
		}	
	}

/* ---------------------------------------------------------------------------- *
	CREATE NEW ACCOUNT / NEW USER FOR EXISTING ACCOUNT
	Required :: (emailAddress, firstName, lastName, password, key OR displayName) 
	Returns: SUCCESS :: TRUE / FALSE :: User object
	Endpoint: /Account/Register
	Method: POST
 * ---------------------------------------------------------------------------- */
	function Register () {
		$params = $this->input->post();
		try {
			// Missing required information
			if
				(
					(is_null($params) && !is_array($params)) 
					|| !isset($params['firstName']) 
					|| !isset($params['lastName']) 
					|| !isset($params['emailAddress']) 
					|| !isset($params['password'])
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
			

			// If there is a key, get account ID : if not, create account and get ID
			$params['defaults'] = $this->dubyaaConfig;
			if($params['key']) {
				$params['accountId'] = $this->AccountModel->FetchId($params);
			} else {
				$params['registrationKey'] = $this->CommonModel->RandomString();
				$params['trialDuration'] = $this->dubyaaConfig['trialDuration'];
				$params['accountId'] = $this->AccountModel->Create($params);
			}
			// Setup "seed" user for new account with user information, will always be account admin and EXEC
			$userId = $this->UserModel->CreateSeed($params);
			// Setup teams for new account
			if(!isset($params['teamNames']) || !is_array($params['teamNames'])) {
				$params['teamNames'] = array($params['defaults']['defaultInitialTeamName']);
			}
			foreach($params['teamNames'] as $id =>$name) {
				$team = array(
					'userId' => $userId,
					'accountId' => $params['accountId'],
					'displayName' => $name
				);
				$this->TeamModel->Create($team);
			}
			// Setup auto login vars to redirect user once completed
			$key = $this->CommonModel->RandomString();
			$this->UserModel->genAutoLoginKey($userId, $key);

			// Clean out tracking record for converted / non-converted trials
			$this->AccountModel->wipeRegistrationEntry($params['emailAddress']);

			$data['rtn']['success'] = TRUE;
			$data['rtn']['user'] = $this->UserModel->getUser($userId);
			$this->load->view('json', $data);

		}
		// Something went horribly wrong
		catch (Exception $e) {
			http_response_code(500);
			echo $e->getMessage();
		}	
	}

/* ---------------------------------------------------------------------------- *
	GET LIST OF ACCOUNT USERS
	Required :: (accountId) 
	Returns: SUCCESS :: TRUE / FALSE :: JSON OBJ of USERS
	Endpoint: /Account/FetchUsers
	Method: GET
 * ---------------------------------------------------------------------------- */
	function FetchUsers () {
		$params = $_GET;
		try {
			// Missing required information
			if
				(
					(is_null($params) && !is_array($params)) 
					|| !isset($params['accountId']) 
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
			if(isset($params['userId'])) {
				$params['requesterPrefs'] = $this->UserModel->FetchUserPrefs($params['userId']);
			}
			// Try to create
			if($users = $this->AccountModel->FetchUsers($params)) {
				$data['rtn']['users'] = $users;
				$data['rtn']['success'] = true;
			} else {
				$data['rtn']['success'] = false;
			}
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			http_response_code(500);
			echo $e->getMessage();
		}	
	}

/* ---------------------------------------------------------------------------- *
	GET LIST OF ACCOUNT VALUES
	Required :: (accountId) 
	Returns: SUCCESS :: TRUE / FALSE :: JSON OBJ of VALUES
	Endpoint: /Account/FetchValues
	Method: GET
 * ---------------------------------------------------------------------------- */
	function FetchValues () {
		$params = $_GET;
		try {
			// Missing required information
			if
				(
					(is_null($params) && !is_array($params)) 
					|| !isset($params['accountId']) 
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
			// Try to create
			if(!$values = $this->AccountModel->FetchValues($params)) {
				throw new Exception($this->exceptions['noRecord']);
			}
			$data['rtn']['values'] = $values;
			$data['rtn']['success'] = true;
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			http_response_code(500);
			echo $e->getMessage();
		}	
	}

/* ---------------------------------------------------------------------------- *
	GET LIST OF ACCOUNT TEAMS
	Required :: (accountId) 
	Returns: SUCCESS :: TRUE / FALSE :: JSON OBJ of TEAMS
	Endpoint: /Account/FetchTeams
	Method: GET
 * ---------------------------------------------------------------------------- */
	function FetchTeams () {
		$params = $_GET;
		try {
			// Missing required information
			if
				(
					(is_null($params) && !is_array($params)) 
					|| !isset($params['accountId']) 
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
			// Try to create
			$teams = $this->TeamModel->AccountList($params);
			$data['rtn']['teams'] = $teams;
			$data['rtn']['success'] = true;
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			http_response_code(500);
			echo $e->getMessage();
		}	
	}

/* ---------------------------------------------------------------------------- *
	GET LIST OF ACCOUNT LEVELS
	Required :: (accountId) 
	Returns: SUCCESS :: TRUE / FALSE :: JSON OBJ of LEVELS
	Endpoint: /Account/FetchLevels
	Method: GET
 * ---------------------------------------------------------------------------- */
	function FetchLevels () {
		$params = $_GET;
		try {
			// Missing required information
			if
				(
					(is_null($params) && !is_array($params)) 
					|| !isset($params['accountId']) 
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
			// Try to create
			$levels = $this->AccountModel->FetchLevels($params);
			$data['rtn']['levels'] = $levels;
			$data['rtn']['success'] = true;
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			http_response_code(500);
			echo $e->getMessage();
		}	
	}

/* ---------------------------------------------------------------------------- *
	GET LIST OF ACCOUNT SETTINGS
	Required :: (accountId) 
	Returns: SUCCESS :: TRUE / FALSE :: JSON OBJ of SETTINGS
	Endpoint: /Account/FetchSettings
	Method: GET
 * ---------------------------------------------------------------------------- */
	function FetchSettings () {
		$params = $_GET;
		try {
			// Missing required information
			if
				(
					(is_null($params) && !is_array($params)) 
					|| !isset($params['accountId']) 
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
			// Try to create
			if(!$settings = $this->AccountModel->FetchSettings($params)) {
				throw new Exception($this->exceptions['noRecord']);
			}
			$data['rtn']['settings'] = $settings;
			$data['rtn']['success'] = true;
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			http_response_code(500);
			echo $e->getMessage();
		}	
	}

/* ---------------------------------------------------------------------------- *
	UPDATE PRIMARY EMAILS
	Required :: (accountId) (userId) (primaryEmail) 
	Returns: SUCCESS :: TRUE / FALSE
	Endpoint: /Account/SavePrimaryEmail
	Method: POST
 * ---------------------------------------------------------------------------- */
	function SavePrimaryEmail () {
		$params = $this->input->post();
		try {
			// Missing required information
			if
				(
					(is_null($params) && !is_array($params)) 
					|| !isset($params['accountId']) 
					|| !isset($params['userId']) 
					|| !isset($params['primaryEmail']) 
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
			// Try to create
			if(!$this->AccountModel->SavePrimaryEmail($params)) {
				throw new Exception($this->exceptions['noRecord']);
			}
			$data['rtn']['success'] = true;
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			http_response_code(500);
			echo $e->getMessage();
		}	
	}

/* ---------------------------------------------------------------------------- *
	GET LIST OF ACCOUNT USER ACTIVITY FOR A PERIOD
	Required :: (a) (u) (start) (limit)
	Returns: SUCCESS :: TRUE / FALSE :: JSON OBJ of ACTIVITY
	Endpoint: /Account/Activity
	Method: GET
 * ---------------------------------------------------------------------------- */
	function FetchTodoActivity () {
		$params = $_GET;
		try {
			// Missing required information
			if
				(
					(is_null($params) && !is_array($params)) 
					|| !isset($params['a']) 
					|| !isset($params['u']) 
					|| !isset($params['start']) 
					|| !isset($params['limit']) 
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
			// Try to fetch
			if(!$activity = $this->AccountModel->FetchTodoActivity($params)) {
				$data['rtn']['success'] = false;
			} else {
				$data['rtn']['activity'] = $activity['list'];
				$data['rtn']['totalActivity'] = $activity['totalActivity'];
				$data['rtn']['success'] = true;
			}
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			http_response_code(500);
			echo $e->getMessage();
		}	
	}

/* ---------------------------------------------------------------------------- *
	GET LIST OF ACCOUNT MONTHS OF ACTIVITY FOR YEAR
	Required :: (id) (year)
	Returns: SUCCESS :: TRUE / FALSE :: JSON OBJ for months
	Endpoint: /Account/FetchMonthList
	Method: GET
 * ---------------------------------------------------------------------------- */
	function FetchMonthList () {
		$params = $_GET;
		try {
			// Missing required information
			if
				(
					(is_null($params) && !is_array($params)) 
					|| !isset($params['id']) 
					|| !isset($params['year']) 
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
			if(!$months = $this->AccountModel->FetchMonthList($params)) {
				$data['rtn']['success'] = false;
			} else {
				$data['rtn']['success'] = true;
				$data['rtn']['months'] = $months;
			}
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			echo $e->getMessage();
		}	
	}

/* ---------------------------------------------------------------------------- *
	CREATE NEW ACCOUNT LEVEL
	Required :: (accountId) (userId) (label) (valueLo) (valueHid) 
	Returns: SUCCESS :: TRUE / FALSE
	Endpoint: /Account/CreateLevel
	Method: POST
 * ---------------------------------------------------------------------------- */
	function CreateLevel () {
		$params = $this->input->post();
		try {
			// Missing required information
			if
				(
					(is_null($params) && !is_array($params)) 
					|| !isset($params['accountId']) 
					|| !isset($params['userId']) 
					|| !isset($params['label']) 
					|| !isset($params['valueLo']) 
					|| !isset($params['valueHi']) 
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
			// Try to create
			if(!$this->AccountModel->CreateLevel($params)) {
				throw new Exception($this->exceptions['noCreate']);
			}
			$data['rtn']['success'] = true;
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			http_response_code(500);
			echo $e->getMessage();
		}	
	}

/* ---------------------------------------------------------------------------- *
	UPDATE ACCOUNT LEVEL
	Required :: (id) (userId) (label) (valueLo) (valueHid) 
	Returns: SUCCESS :: TRUE / FALSE
	Endpoint: /Account/UpdateLevel
	Method: POST
 * ---------------------------------------------------------------------------- */
	function UpdateLevel () {
		$params = $this->input->post();
		try {
			// Missing required information
			if
				(
					(is_null($params) && !is_array($params)) 
					|| !isset($params['id']) 
					|| !isset($params['userId']) 
					|| !isset($params['label']) 
					|| !isset($params['valueLo']) 
					|| !isset($params['valueHi']) 
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
			// Try to create
			if(!$this->AccountModel->UpdateLevel($params)) {
				throw new Exception($this->exceptions['noCreate']);
			}
			$data['rtn']['success'] = true;
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			http_response_code(500);
			echo $e->getMessage();
		}	
	}

/* ---------------------------------------------------------------------------- *
	UPDATE ACCOUNT LEVEL
	Required :: (id) (userId)
	Returns: SUCCESS :: TRUE / FALSE
	Endpoint: /Account/DeleteLevel
	Method: POST
 * ---------------------------------------------------------------------------- */
	function DeleteLevel () {
		$params = $this->input->post();
		try {
			// Missing required information
			if
				(
					(is_null($params) && !is_array($params)) 
					|| !isset($params['id']) 
					|| !isset($params['userId']) 
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
			// Try to create
			if(!$this->AccountModel->DeleteLevel($params)) {
				throw new Exception($this->exceptions['noCreate']);
			}
			$data['rtn']['success'] = true;
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			http_response_code(500);
			echo $e->getMessage();
		}	
	}

/* ---------------------------------------------------------------------------- *
	GET ACCOUNT UNASSIGNED, ACTIVE SUGGESTIONS
	Required :: (accountId)
	Returns: SUCCESS :: TRUE / FALSE :: JSON OBJ OF SUGGESTIONS
	Endpoint: /User/FetchSuggestions
	Method: GET
 * ---------------------------------------------------------------------------- */
	function FetchSuggestions () {
		$params = $_GET;
		try {
			// Missing required information
			if 
				(
					(is_null($params) && !is_array($params)) 
					|| !isset($params['accountId'])
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
				

			// Try
			if($suggestions = $this->AccountModel->FetchSuggestions($params)) {
				$data['rtn']['success'] = true;
				$data['rtn']['suggestions'] = $suggestions['list'];
				$data['rtn']['total'] = $suggestions['total'];
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

/* End of file Account.php */
/* Location: ./application/controllers/Account.php */