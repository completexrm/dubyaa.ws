<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class User extends CI_Controller {

/* ---------------------------------------------------------------------------- *
	DUBYAA CLASS "User"
	Author: Brian Garstka
	EndPoint: /User
	This class handles all operations for users
* ---------------------------------------------------------------------------- */

	function __construct() {
		parent::__construct();
		// COMMON Model
		$this->load->model('CommonModel','',TRUE);
		// USER Model
		$this->load->model('UserModel','',TRUE);
		// ACCOUNT Model
		$this->load->model('AccountModel','',TRUE);
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
	Endpoint: /User
	Method: N/A
 * ---------------------------------------------------------------------------- */
	function index() {
		http_response_code(500);
		echo $this->exceptions['noPage'];
	}

/* ---------------------------------------------------------------------------- *
	GET FULL USER PROFILE
	Required :: (id)
	Returns: SUCCESS :: TRUE / FALSE :: JSON OBJ for profile
	Endpoint: /User/FetchProfile
	Method: GET
 * ---------------------------------------------------------------------------- */
	function FetchProfile () {
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
			if(!$profile = $this->UserModel->getUser($params['id'])) {
				$data['rtn']['success'] = false;
			} else {
				$data['rtn']['success'] = true;
				$data['rtn']['profile'] = $profile;
			}
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			echo $e->getMessage();
		}	
	}

/* ---------------------------------------------------------------------------- *
	GET USER HISTORY
	Required :: (id) (start) (limit)
	Returns: SUCCESS :: TRUE / FALSE :: JSON OBJ for profile
	Endpoint: /User/FetchProfile
	Method: GET
 * ---------------------------------------------------------------------------- */
	function FetchAllHistory () {
		$params = $_GET;
		try {
			// Missing required information
			if
				(
					(is_null($params) && !is_array($params)) 
					|| !isset($params['id']) 
					|| !isset($params['start']) 
					|| !isset($params['limit']) 
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
			if(!$history = $this->UserModel->FetchAllHistory($params)) {
				$data['rtn']['success'] = false;
			} else {
				$data['rtn']['success'] = true;
				$data['rtn']['history'] = $history['list'];
				$data['rtn']['totalHistory'] = $history['total'];
			}
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			echo $e->getMessage();
		}	
	}

/* ---------------------------------------------------------------------------- *
	SET A GOAL
	Required :: (userId) (pts) (goalType)
	Returns: SUCCESS :: TRUE / FALSE
	Endpoint: /User/SetGoal
	Method: POST
 * ---------------------------------------------------------------------------- */
	function SetGoal () {
		$params = $this->input->post();
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
			// pull in current quarter
			$params['quarter'] = $this->CommonModel->CurrentQuarter();
			// Try to create
			if(!$this->UserModel->SetGoal($params)) {
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
	SAVE ONBOARDING VALUES
	Required :: (userId)
	Returns: SUCCESS :: TRUE / FALSE
	Endpoint: /User/SaveOnboard
	Method: POST
 * ---------------------------------------------------------------------------- */
	function SaveOnboard () {
		$params = $this->input->post();
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
			// Try
			if(!$this->UserModel->SaveOnboard($params)) {
				throw new Exception($this->exceptions['noUpdate']);
			}
			// if(!$this->AccountModel->SaveOnboard($params)) {
			// 	throw new Exception($this->exceptions['noUpdate']);
			// }
			$data['rtn']['success'] = true;
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			echo $e->getMessage();
		}	
	}

/* ---------------------------------------------------------------------------- *
	SAVE USER PROFILE
	Required :: (userId) (emailAddress) (displayName) (firstName) (lastName)
	Returns: SUCCESS :: TRUE / FALSE
	Endpoint: /User/SaveProfile
	Method: POST
 * ---------------------------------------------------------------------------- */
	function SaveProfile () {
		$params = $this->input->post();
		try {
			// Missing required information
			if
				(
					(is_null($params) && !is_array($params)) 
					|| !isset($params['userId']) 
					|| !isset($params['displayName']) 
					|| !isset($params['firstName']) 
					|| !isset($params['lastName']) 
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
			// Try
			if(!$this->UserModel->SaveProfile($params)) {
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
	KILL APPLICATION OVERLAY TIPS
	Required :: (userId)
	Returns: SUCCESS :: TRUE / FALSE
	Endpoint: /User/NoAppHints
	Method: POST
 * ---------------------------------------------------------------------------- */
	function NoAppHints () {
		$params = $this->input->post();
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
			// Try
			if(!$this->UserModel->NoAppHints($params)) {
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
	CREATE USER PROFILE
	Required :: (userId) (accountId) (emailAddress) (firstName) (lastName)
	Returns: SUCCESS :: TRUE / FALSE
	Endpoint: /User/Create
	Method: POST
 * ---------------------------------------------------------------------------- */
	function Create () {
		$params = $this->input->post();
		try {
			// Missing required information
			if
				(
					(is_null($params) && !is_array($params)) 
					|| !isset($params['accountId']) 
					|| !isset($params['emailAddress']) 
					|| !isset($params['firstName']) 
					|| !isset($params['lastName']) 
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}

			// Get temp password for new user account
			$params['password'] = $this->CommonModel->RandomString(8);
			$params['defaults'] = $this->dubyaaConfig;
			// Setup auto login vars to redirect user once completed
			// Try
			if(!$userId = $this->UserModel->Create($params)) {
				throw new Exception($this->exceptions['noUpdate']);
			}
			$key = $this->CommonModel->RandomString();
			$this->UserModel->genAutoLoginKey($userId, $key);
			$data['rtn']['success'] = true;
			$data['rtn']['user'] = $this->UserModel->getUser($userId);
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			echo $e->getMessage();
		}	
	}

/* ---------------------------------------------------------------------------- *
	DELETE USER PROFILE
	Required :: (id) (userId)
	Returns: SUCCESS :: TRUE / FALSE
	Endpoint: /User/Delete
	Method: POST
 * ---------------------------------------------------------------------------- */
	function Delete () {
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
			// Try
			if(!$this->UserModel->Delete($params)) {
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
	UPDATE USER PROFILE
	Required :: (id) (userId) (emailAddress) (lastName) (firstName) (displayName)
	Returns: SUCCESS :: TRUE / FALSE
	Endpoint: /User/Update
	Method: POST
 * ---------------------------------------------------------------------------- */
	function Update () {
		$params = $this->input->post();
		try {
			// Missing required information
			if
				(
					(is_null($params) && !is_array($params)) 
					|| !isset($params['id'])
					|| !isset($params['userId'])
					|| !isset($params['emailAddress'])
					|| !isset($params['displayName'])
					|| !isset($params['firstName'])
					|| !isset($params['lastName'])
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
			// Try
			if(!$this->UserModel->Update($params)) {
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
	GET USERS TEAM ROSTER
	Required :: (userId) (accountId)
	Returns: SUCCESS :: TRUE / FALSE :: JSON OBJ FOR ROSTER
	Endpoint: /User/FetchTeamRoster
	Method: GET
 * ---------------------------------------------------------------------------- */
	function FetchTeamRoster () {
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
				

			// Try
			if(!$roster = $this->TeamModel->UserList($params)) {
				throw new Exception($this->exceptions['noRecord']);
			}
			$data['rtn']['success'] = true;
			$data['rtn']['teams'] = $roster;
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			echo $e->getMessage();
		}	
	}

/* ---------------------------------------------------------------------------- *
	GET USERS UNREAD SUGGESTIONS COUNT
	Required :: (accountId) (userId)
	Returns: SUCCESS :: TRUE / FALSE :: COUNT
	Endpoint: /User/FetchSuggestionCount
	Method: GET
 * ---------------------------------------------------------------------------- */
	function FetchSuggestionCount () {
		$params = $_GET;
		try {
			// Missing required information
			if 
				(
					(is_null($params) && !is_array($params)) 
					|| !isset($params['accountId'])
					|| !isset($params['userId'])

				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
				

			// Try
			if(!$count = $this->UserModel->FetchSuggestionCount($params)) {
				$data['rtn']['success'] = false;
			} else {
				$data['rtn']['success'] = true;
				$data['rtn']['count'] = $count;
			}
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			echo $e->getMessage();
		}	
	}

/* ---------------------------------------------------------------------------- *
	GET USERS UNREAD MESSAGES
	Required :: (userId) (accountId)
	Returns: SUCCESS :: TRUE / FALSE :: COUNT
	Endpoint: /User/FetchMessagesUnread
	Method: GET
 * ---------------------------------------------------------------------------- */
	function FetchMessagesUnread () {
		$params = $_GET;
		try {
			// Missing required information
			if 
				(
					(is_null($params) && !is_array($params)) 
					|| !isset($params['userId'])
					|| !isset($params['accountId'])
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
				

			// Try
			if(!$count = $this->UserModel->FetchMessagesUnread($params)) {
				$data['rtn']['success'] = false;
			} else {
				$data['rtn']['success'] = true;
				$data['rtn']['count'] = $count;
			}
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			echo $e->getMessage();
		}	
	}

/* ---------------------------------------------------------------------------- *
	GET USERS UNREAD MESSAGES
	Required :: (userId)
	Returns: SUCCESS :: TRUE / FALSE :: COUNT
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
					|| !isset($params['userId'])
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
				
			// Try
			if($suggestions = $this->UserModel->FetchSuggestions($params)) {
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

/* ---------------------------------------------------------------------------- *
	GET USERS SUGGESTIONS THEY HAVE SENT
	Required :: (userId)
	Returns: SUCCESS :: TRUE / FALSE :: COUNT
	Endpoint: /User/FetchSuggestionsSent
	Method: GET
 * ---------------------------------------------------------------------------- */
	function FetchSuggestionsSent () {
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
				

			// Try
			if($suggestions = $this->UserModel->FetchSuggestionsSent($params)) {
				$data['rtn']['success'] = true;
				$data['rtn']['suggestions'] = $suggestions;
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
	MARK SUGGESTION AS READ
	Required :: (id) (userId)
	Returns: SUCCESS :: TRUE / FALSE
	Endpoint: /User/ReadSuggestion
	Method: POST
 * ---------------------------------------------------------------------------- */
	function ReadSuggestion () {
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
				
			// Try
			if($this->UserModel->ReadSuggestion($params)) {
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
	MARK SUGGESTION AS NOT READ
	Required :: (id) (userId)
	Returns: SUCCESS :: TRUE / FALSE
	Endpoint: /User/UnreadSuggestion
	Method: POST
 * ---------------------------------------------------------------------------- */
	function UnreadSuggestion () {
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
				
			// Try
			if($this->UserModel->UnreadSuggestion($params)) {
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
	DELETE SUGGESTION
	Required :: (id) (userId)
	Returns: SUCCESS :: TRUE / FALSE
	Endpoint: /User/DeleteSuggestion
	Method: POST
 * ---------------------------------------------------------------------------- */
	function DeleteSuggestion () {
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
				
			// Try
			if($this->UserModel->DeleteSuggestion($params)) {
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
	RESTORE SUGGESTION
	Required :: (id) (userId)
	Returns: SUCCESS :: TRUE / FALSE
	Endpoint: /User/UndeleteSuggestion
	Method: POST
 * ---------------------------------------------------------------------------- */
	function UndeleteSuggestion () {
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
				
			// Try
			if($this->UserModel->UndeleteSuggestion($params)) {
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
	TORCH SUGGESTION
	Required :: (id) (userId)
	Returns: SUCCESS :: TRUE / FALSE
	Endpoint: /User/TorchSuggestion
	Method: POST
 * ---------------------------------------------------------------------------- */
	function TorchSuggestion () {
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
				
			// Try
			if($this->UserModel->TorchSuggestion($params)) {
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
	GET LIST OF USER MONTHS OF ACTIVITY FOR YEAR
	Required :: (id) (year)
	Returns: SUCCESS :: TRUE / FALSE :: JSON OBJ for profile
	Endpoint: /User/FetchMonthList
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
			if(!$months = $this->UserModel->FetchMonthList($params)) {
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
	UPDATE USER PROFILE PHOTO
	Required :: (userId) (photoPath)
	Returns: SUCCESS :: TRUE / FALSE
	Endpoint: /User/SavePhoto
	Method: GET
 * ---------------------------------------------------------------------------- */
	function SavePhoto () {
		$params = $this->input->post();
		try {
			// Missing required information
			if
				(
					(is_null($params) && !is_array($params)) 
					|| !isset($params['userId']) 
					|| !isset($params['photoPath']) 
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
			if(!$this->UserModel->UpdatePhoto($params)) {
				throw new Exception($this->exceptions['noUpdate']);	
			}
			$data['rtn']['success'] = true;
			http_response_code(200);
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			http_response_code(500);
			echo $e->getMessage();
		}	
	}

/* ---------------------------------------------------------------------------- *
	DELETE USER PROFILE PHOTO
	Required :: (userId)
	Returns: SUCCESS :: TRUE / FALSE
	Endpoint: /User/DeletePhoto
	Method: GET
 * ---------------------------------------------------------------------------- */
	function DeletePhoto () {
		$params = $this->input->post();
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
			if(!$this->UserModel->DeletePhoto($params)) {
				throw new Exception($this->exceptions['noUpdate']);	
			}
			$data['rtn']['success'] = true;
			http_response_code(200);
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			http_response_code(500);
			echo $e->getMessage();
		}	
	}

}

/* End of file Team.php */
/* Location: ./application/controllers/Team.php */