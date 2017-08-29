<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Todo extends CI_Controller {

/* ---------------------------------------------------------------------------- *
	DUBYAA CLASS "Todo"
	Author: Brian Garstka
	EndPoint: /Todo
	This class handles all CRUD operations for system todo's, including managing
	and manipulationg TODO infomration, TAG information and DUBYAA info.
* ---------------------------------------------------------------------------- */

	function __construct() {
		parent::__construct();
		// COMMON Model
		$this->load->model('CommonModel','',TRUE);
		// TODO Model
		$this->load->model('TodoModel','',TRUE);
		// TAGS Model
		$this->load->model('TagModel','',TRUE);
		// USER Model
		$this->load->model('UserModel','',TRUE);
		// REWARD Model
		$this->load->model('RewardModel','',TRUE);
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
	CREATE SINGLE TODO
	Required :: (userId) (label) 
	Returns: SUCCESS :: TRUE / FALSE :: ID
	Endpoint: /Todo/Create
	Method: POST
 * ---------------------------------------------------------------------------- */
	function Create () {
		$params = $this->input->post();
		try {
			// Missing required information
			if
				(
					(is_null($params) && !is_array($params)) 
					|| !isset($params['label']) 
					|| !isset($params['userId']) 
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
			$params['multiplier'] = (isset($params['multiplier'])) ? $params['multiplier'] : $this->dubyaaConfig['dubyaaMultiplier'];
			// Try to create
			if(!$todoId = $this->TodoModel->CreateTodo($params)) {
				throw new Exception($this->exceptions['noCreate']);
			}
			
			if(!$params['isBacklog']) {
				// Log action
				$activity = array(
					'accountId' => $params['accountId'],
					'userId' => $params['userId'],
					'actionTable' => 'todo',
					'actionId' => $todoId,
					'actionType' => 'created'
				);
				$this->UserModel->LogActivity($activity);
			}
			
			$data['rtn']['success'] = TRUE;
			$data['rtn']['todoId'] = $todoId;
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			echo $e->getMessage();
			log_message('error', 'UserID :: '.$params['userId'].' :: '.$e->getMessage());
		}	
	}

/* ---------------------------------------------------------------------------- *
	CREATE NEW INSTAWIN
	Required :: (userId) (label) (accountId)
	Returns: SUCCESS :: TRUE / FALSE :: ID
	Endpoint: /Todo/CreateInstawin
	Method: POST
 * ---------------------------------------------------------------------------- */
	function CreateInstawin () {
		$params = $this->input->post();
		try {
			// Missing required information
			if
				(
					(is_null($params) && !is_array($params)) 
					|| !isset($params['label']) 
					|| !isset($params['userId']) 
					|| !isset($params['accountId']) 
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
			$params['multiplier'] = (isset($params['multiplier'])) ? $params['multiplier'] : $this->dubyaaConfig['dubyaaMultiplier'];
			$params['pointsAwarded'] = $params['multiplier'] * 1;
			// Try to create
			$params['dateDue'] = date('Y-m-d 23:59:59');
			$params['isDubyaa'] = '1';
			$params['dubyaaDate'] = date('Y-m-d H:i:s');
			
			if(!$todoId = $this->TodoModel->CreateTodo($params)) {
				throw new Exception($this->exceptions['noCreate']);
			}
			
			// Log action
			$activity = array(
				'accountId' => $params['accountId'],
				'userId' => $params['userId'],
				'actionTable' => 'todo',
				'actionId' => $todoId,
				'actionType' => 'instawin'
			);
			$this->UserModel->LogActivity($activity);
			
			$data['rtn']['success'] = TRUE;
			$data['rtn']['todoId'] = $todoId;
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			echo $e->getMessage();
			log_message('error', 'UserID :: '.$params['userId'].' :: '.$e->getMessage());
		}	
	}

/* ---------------------------------------------------------------------------- *
	MOVE EXISTING TODO TO BACKLOG
	Required :: (userId) (id) (accountId)
	Returns: SUCCESS :: TRUE / FALSE :: ID
	Endpoint: /Todo/SaveToBacklog
	Method: POST
 * ---------------------------------------------------------------------------- */
	function SaveToBacklog () {
		$params = $this->input->post();
		try {
			// Missing required information
			if
				(
					(is_null($params) && !is_array($params)) 
					|| !isset($params['id']) 
					|| !isset($params['userId']) 
					|| !isset($params['accountId']) 
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
			
			if(!$this->TodoModel->SaveToBacklog($params)) {
				$data['rtn']['success'] = FALSE;
			} else {
				// Log action
				$activity = array(
					'accountId' => $params['accountId'],
					'userId' => $params['userId'],
					'actionTable' => 'todo',
					'actionId' => $params['id'],
					'actionType' => 'moved to backlog'
				);
				$this->UserModel->LogActivity($activity);
				$data['rtn']['success'] = TRUE;
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
	GET SINGLE TODO
	Required :: (id) 
	Returns: JSON obj for all TODO, TAG and DUBYAA information
	Endpoint: /Todo/Fetch
	Method: GET
 * ---------------------------------------------------------------------------- */
	function Fetch () {
		$params = $_GET;
		try {
			// Missing required information
			if((is_null($params) && !is_array($params)) || !isset($params['id'])) {
				throw new Exception($this->exceptions['noInfo']);
			}
			// Todo does not exist
			if(!$todo = $this->TodoModel->FetchTodo($params)) {
				throw new Exception($this->exceptions['noRecord']);
			}
			$data['rtn']['success'] = TRUE;
			$data['rtn']['todo'] = $todo;
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			http_response_code(500);
			echo $e->getMessage();
			log_message('error', 'TodoId :: '.$params['id'].' :: '.$e->getMessage());
		}
	}

/* ---------------------------------------------------------------------------- *
	GET USER ACTIVE TODO
	Required :: (id) (viewingId)
	Returns: JSON obj for all TODO, TAG and DUBYAA information
	Endpoint: /Todo/Fetch
	Method: GET
 * ---------------------------------------------------------------------------- */
	function UserActive () {
		$params = $_GET;
		try {
			// Missing required information
			if((is_null($params) && !is_array($params)) || !isset($params['id']) || !isset($params['viewingId'])) {
				throw new Exception($this->exceptions['noInfo']);
			}
			// Todo does not exist
			if($todos = $this->TodoModel->UserActive($params)) {
				$data['rtn']['success'] = TRUE;
				$data['rtn']['todos'] = $todos;
			} else {
				$data['rtn']['success'] = FALSE;
			}
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			http_response_code(500);
			echo $e->getMessage();
			log_message('error', 'TodoId :: '.$params['id'].' :: '.$e->getMessage());
		}
	}

/* ---------------------------------------------------------------------------- *
	ENSURE USER CAN STILL ENTER
	Required :: (userId) (accountId) 
	Returns: JSON obj
	Endpoint: /Todo/CanEnter
	Method: POST
 * ---------------------------------------------------------------------------- */
	function CanEnter() {
		$params = $this->input->post();
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
			$data['rtn']['canEnter'] = $this->TodoModel->UserCanEnter($params);
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			$data['rtn']['success'] = FALSE;
			$this->load->view('json', $data);
		}
	}

/* ---------------------------------------------------------------------------- *
	GET USER TODOS
	Required :: (userId) 
	Options  :: (sortField) (sortDirection) (active)
	Option Defaults :: sortField=label, sortDirection=asc, active=Y
	Returns: JSON obj for all TODOS, TAGS and DUBYAA information
	Endpoint: /Todo/FetchUser
	Method: GET
 * ---------------------------------------------------------------------------- */
	function FetchUser() {
		$params = $_GET;
		try {
			// Missing required information
			if((is_null($params) && !is_array($params)) || !isset($params['userId'])) {
				throw new Exception($this->exceptions['noInfo']);
			}
			// Try to fetch
			if(!$todos = $this->TodoModel->FetchUserTodos($params)) {
				throw new Exception($this->exceptions['noRecord']);
			}
			$data['rtn']['success'] = TRUE;
			$data['rtn']['todos'] = $todos;
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			$data['rtn']['success'] = FALSE;
			$this->load->view('json', $data);
		}
	}

/* ---------------------------------------------------------------------------- *
	GET USER TODOS IN BACKLOG
	Required :: (userId) 
	Returns: JSON obj for all TODOS in Backlog
	Endpoint: /Todo/FetchUserBacklog
	Method: GET
 * ---------------------------------------------------------------------------- */
	function FetchUserBacklog() {
		$params = $_GET;
		try {
			// Missing required information
			if((is_null($params) && !is_array($params)) || !isset($params['userId'])) {
				throw new Exception($this->exceptions['noInfo']);
			}
			// Try to fetch
			if(!$todos = $this->TodoModel->FetchUserTodosBacklog($params)) {
				throw new Exception($this->exceptions['noRecord']);
			}
			$data['rtn']['success'] = TRUE;
			$data['rtn']['todos'] = $todos;
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			$data['rtn']['success'] = FALSE;
			$this->load->view('json', $data);
		}
	}

/* ---------------------------------------------------------------------------- *
	GET USER TODO COMMENTS
	Required :: (id) 
	Returns: JSON obj for all reaction to todo
	Endpoint: /Todo/FetchTodoComments
	Method: GET
 * ---------------------------------------------------------------------------- */
	function FetchTodoComments() {
		$params = $_GET;
		try {
			// Missing required information
			if((is_null($params) && !is_array($params)) 
				|| !isset($params['id']))
			{
				throw new Exception($this->exceptions['noInfo']);
			}
			// Try to fetch
			if(!$comments = $this->TodoModel->FetchTodoComments($params)) {
				throw new Exception($this->exceptions['noRecord']);
			}
			$data['rtn']['success'] = TRUE;
			$data['rtn']['comments'] = $comments;
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			$data['rtn']['success'] = FALSE;
			$this->load->view('json', $data);
		}
	}

/* ---------------------------------------------------------------------------- *
	UPDATE SINGLE TODO
	Required :: (userId) (id)
	Returns: SUCCESS :: TRUE / FALSE
	Endpoint: /Todo/Update
	Method: PUT
 * ---------------------------------------------------------------------------- */
	function Update () {
		$params = $this->input->post();
		try {
			// Missing required information
			if((is_null($params) && !is_array($params)) || !isset($params['id']) || !isset($params['userId'])) {
				throw new Exception($this->exceptions['noInfo']);
			}
			// Try to update
			if(!$this->TodoModel->UpdateTodo($params)) {
				throw new Exception($this->exceptions['noUpdate']);
			}

			// Log action
			$activity = array(
				'userId' => $params['userId'],
				'accountId' => $params['accountId'],
				'actionTable' => 'todo',
				'actionId' => $params['id'],
				'actionType' => 'updated'
			);
			$this->UserModel->LogActivity($activity);

			$data['rtn']['success'] = TRUE;
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			echo $e->getMessage();
		}	
	}

/* ---------------------------------------------------------------------------- *
	CONVERT BACKLOG ITEM TO ACTIVE
	Required :: (userId) (id) (accountId)
	Returns: SUCCESS :: TRUE / FALSE
	Endpoint: /Todo/BacklogConvert
	Method: PUT
 * ---------------------------------------------------------------------------- */
	function BacklogConvert () {
		$params = $this->input->post();
		try {
			// Missing required information
			if((is_null($params) && !is_array($params)) || !isset($params['id']) || !isset($params['userId'])) {
				throw new Exception($this->exceptions['noInfo']);
			}
			// Try to update
			if(!$this->TodoModel->BacklogConvert($params)) {
				throw new Exception($this->exceptions['noUpdate']);
			}
			// Log action
			$activity = array(
				'userId' => $params['userId'],
				'accountId' => $params['accountId'],
				'actionTable' => 'todo',
				'actionId' => $params['id'],
				'actionType' => 'created'
			);
			$this->UserModel->LogActivity($activity);

			$data['rtn']['success'] = TRUE;
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			echo $e->getMessage();
		}	
	}

/* ---------------------------------------------------------------------------- *
	CONVERT BACKLOG ITEM TO ACTIVE
	Required :: (userId) (id) (accountId) (label) ()
	Returns: SUCCESS :: TRUE / FALSE
	Endpoint: /Todo/SuggestionToBacklog
	Method: POST
 * ---------------------------------------------------------------------------- */
	function SuggestionToBacklog () {
		$params = $this->input->post();
		try {
			// Missing required information
			if((is_null($params) && !is_array($params)) || !isset($params['id']) || !isset($params['userId'])) {
				throw new Exception($this->exceptions['noInfo']);
			}
			
			$hasGift = $params['hasGift'];
			$fromId = $params['fromId'];
			unset($params['hasGift']);
			unset($params['fromId']);

			// Try to update
			if(!$todoId = $this->TodoModel->SuggestionToBacklog($params)) {
				throw new Exception($this->exceptions['noUpdate']);
			}

			$torchData = array(
				'id' => $params['id']
			);
			$this->UserModel->TorchSuggestion($torchData);

			if($hasGift===1) {
				$params['todoId'] = $todoId;
				$params['fromId'] = $fromId;
				$this->RewardModel->SetupGift($params);
			}

			// Log action
			$activity = array(
				'userId' => $params['userId'],
				'accountId' => $params['accountId'],
				'actionTable' => 'todo',
				'actionId' => $todoId,
				'actionType' => 'created'
			);
			$this->UserModel->LogActivity($activity);

			$data['rtn']['success'] = TRUE;
			$data['rtn']['todoId'] = $todoId;
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			echo $e->getMessage();
		}	
	}

/* ---------------------------------------------------------------------------- *
	DELETE TODO
	Required :: (id) for TODO
	Returns: SUCCESS :: TRUE / FALSE
	Notes: This simply throws an isDeleted flag in DB
	Endpoint: /Todo/Delete
	Method: PUT
 * ---------------------------------------------------------------------------- */
	function Delete () {
		$params = $this->input->post();
		try {
			// Missing required information
			if((is_null($params) && !is_array($params)) || !isset($params['id']) || !isset($params['userId'])) {
				throw new Exception($this->exceptions['noInfo']);
			}
			// Try to deactivate the todo
			if(!$this->TodoModel->DeleteTodo($params)) {
				throw new Exception($this->exceptions['noDelete']);
			}

			// Log action
			$activity = array(
				'accountId' => $params['accountId'],
				'userId' => $params['userId'],
				'actionTable' => 'todo',
				'actionId' => $params['id'],
				'actionType' => 'archived'
			);
			$this->UserModel->LogActivity($activity);

			$data['rtn']['success'] = TRUE;
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			echo $e->getMessage();
		}	
	}

/* ---------------------------------------------------------------------------- *
	TORCH TODO
	Required :: (id) for TODO
	Returns: SUCCESS :: TRUE / FALSE
	Notes: This physically deletes all records tied to indicated TODO id
	Endpoint: /Todo/Torch
	Method: POST
 * ---------------------------------------------------------------------------- */
	function Torch () {
		$params = $this->input->post();
		try {
			// Missing required information
			if((is_null($params) && !is_array($params)) || !isset($params['id'])) {
				throw new Exception($this->exceptions['noInfo']);
			}
			// Try to deactivate the todo
			if(!$this->TodoModel->TorchTodo($params)) {
				throw new Exception($this->exceptions['noDelete']);
			}

			$data['rtn']['success'] = TRUE;
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			echo $e->getMessage();
		}	
	}

/* ---------------------------------------------------------------------------- *
	UNDELETE TODO
	Required :: (id) for TODO
	Returns: SUCCESS :: TRUE / FALSE
	Notes: This simply lowers an isDeleted flag in DB
	Endpoint: /Todo/Undelete
	Method: PUT
 * ---------------------------------------------------------------------------- */
	function Undelete () {
		$params = $this->input->post();
		try {
			// Missing required information
			if((is_null($params) && !is_array($params)) || !isset($params['id']) || !isset($params['userId'])) {
				throw new Exception($this->exceptions['noInfo']);
			}
			// Try to update the todo
			if(!$this->TodoModel->UndeleteTodo($params)) {
				throw new Exception($this->exceptions['noUpdate']);
			}
			// Log action
			$activity = array(
				'accountId' => $params['accountId'],
				'userId' => $params['userId'],
				'actionTable' => 'todo',
				'actionId' => $params['id'],
				'actionType' => 'undeleted'
			);
			$this->UserModel->LogActivity($activity);
			$data['rtn']['success'] = TRUE;
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			echo $e->getMessage();
		}	
	}

/* ---------------------------------------------------------------------------- *
	DELEGATE TODO
	Required :: (id) (delegatorId) (userId)
	Returns: SUCCESS :: TRUE / FALSE
	Endpoint: /Todo/Delegate
	Method: POST
 * ---------------------------------------------------------------------------- */
	function Delegate () {
		$params = $this->input->post();
		try {
			// Missing required information
			if
				(
					(is_null($params) && !is_array($params)) 
					|| !isset($params['id']) 
					|| !isset($params['userId']) 
					|| !isset($params['delegatorId']) 
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
			// Try to update the todo
			if(!$this->TodoModel->DelegateTodo($params)) {
				throw new Exception($this->exceptions['noUpdate']);
			}
			// Log action
			$activity = array(
				'accountId' => $params['accountId'],
				'userId' => $params['delegatorId'],
				'actionTable' => 'todo',
				'actionId' => $params['id'],
				'actionType' => 'delegated'
			);
			$this->UserModel->LogActivity($activity);
			$data['rtn']['success'] = TRUE;
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			echo $e->getMessage();
		}	
	}

/* ---------------------------------------------------------------------------- *
	SET TODO DUE DATE
	Required :: (id) for TODO (userId)
	Returns: SUCCESS :: TRUE / FALSE
	Notes: sets a user defined due date and time
	Endpoint: /Todo/SetDueDate
	Method: POST
 * ---------------------------------------------------------------------------- */
	function SetDueDate () {
		$params = $this->CommonModel->InjectionCleanse($this->input->post());
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
			// Pull-in config-level multiplier for due date
			$params['multiplier'] = (isset($params['multiplier'])) ? $params['multiplier'] : $this->dubyaaConfig['dubyaaMultiplier'];
			// Try to update the todo
			if(!$this->TodoModel->SetDueDate($params)) {
				throw new Exception($this->exceptions['noUpdate']);
			}
			// Log action
			$activity = array(
				'accountId' => $params['accountId'],
				'userId' => $params['userId'],
				'actionTable' => 'todo',
				'actionId' => $params['id'],
				'actionType' => 'set expiration date'
			);
			$this->UserModel->LogActivity($activity);
			$data['rtn']['success'] = TRUE;
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			echo $e->getMessage();
		}	
	}

/* ---------------------------------------------------------------------------- *
	DUBYAA OPERATIONS
 * ---------------------------------------------------------------------------- */

/* ---------------------------------------------------------------------------- *
	CREATE "DUBYAA" FOR TODO
	Required :: (userId) (id) (dateWon)
	Returns: SUCCESS :: TRUE / FALSE
	Endpoint: /Todo/UpdateDubyaa
	Method: POST
 * ---------------------------------------------------------------------------- */
	function DoDubyaa() {
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
			// Try to create Dubyaa
			if(!$this->TodoModel->DoDubyaa($params)) {
				throw new Exception($this->exceptions['noCreate']);
			}
			// Log action
			$activity = array(
				'accountId' => $params['accountId'],
				'userId' => $params['userId'],
				'actionTable' => 'todo',
				'actionId' => $params['id'],
				'actionType' => 'won'
			);
			$this->UserModel->LogActivity($activity);
			$data['rtn']['success'] = TRUE;
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			echo $e->getMessage();
		}	
	}

/* ---------------------------------------------------------------------------- *
	DELETE "DUBYAA" FOR TODO
	Required :: (id) for TODO
	Returns: SUCCESS :: TRUE / FALSE
	Notes: This is a rare occurrence where we actually delete a DB record
	Endpoint: /Todo/DeleteDubyaa
	Method: DELETE
 * ---------------------------------------------------------------------------- */
	function DeleteDubyaa() {
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
			// Kill DB record for dubyaa
			if(!$this->TodoModel->DeleteDubyaa($params)) {
				throw new Exception($this->exceptions['noDelete']);
			}
			// Log action
			$activity = array(
				'accountId' => $params['accountId'],
				'userId' => $params['userId'],
				'actionTable' => 'todo',
				'actionId' => $params['id'],
				'actionType' => 'unwon'
			);
			$this->UserModel->LogActivity($activity);
			$data['rtn']['success'] = TRUE;
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			echo $e->getMessage();
		}	
	}

/* ---------------------------------------------------------------------------- *
	TAG OPERATIONS
 * ---------------------------------------------------------------------------- */

/* ---------------------------------------------------------------------------- *
	CREATE "TAG" FOR TODO
	Required :: (id) for TODO (userId) (tag)
	Returns: SUCCESS :: TRUE / FALSE : New set of tags for Todo
	Endpoint: /Todo/CreateTag
	Method: POST
 * ---------------------------------------------------------------------------- */
	function CreateTag() {
		$params = $this->input->post();
		try {
			// Missing required information
			if(
				(
					is_null($params) && !is_array($params))
					|| !isset($params['id']) 
					|| !isset($params['userId']) 
					|| !isset($params['tag']))
			{
				throw new Exception($this->exceptions['noInfo']);
			}
			// Try to create
			if(!$this->TagModel->CreateTag($params)) {
				throw new Exception($this->exceptions['noCreate']);
			}
			$this->TodoModel->UpdateTodo(array('userId'=>$params['userId'], 'id' => $params['id']));
			// Log action
			$activity = array(
				'accountId' => $params['accountId'],
				'userId' => $params['userId'],
				'actionTable' => 'todo',
				'actionId' => $params['id'],
				'actionType' => 'modified tags'
			);
			$this->UserModel->LogActivity($activity);
			$data['rtn']['tags'] = $this->TodoModel->FetchTodoTags($params['id']);
			$data['rtn']['success'] = TRUE;
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			echo $e->getMessage();
		}
	}

/* ---------------------------------------------------------------------------- *
	DELETE "TAG" FOR TODO
	Required :: (id) for TODO (tagId) (userId)
	Returns: SUCCESS :: TRUE / FALSE
	Endpoint: /Todo/CreateTag
	Method: POST
 * ---------------------------------------------------------------------------- */
	function DeleteTag() {
		$params = $this->input->post();
		try {
			// Missing required information
			if
				(
					(is_null($params) && !is_array($params)) 
					|| !isset($params['id']) 
					|| !isset($params['userId']) 
					|| !isset($params['tagId']) 
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
			// Try to create
			if(!$this->TagModel->DeleteTag($params)) {
				throw new Exception($this->exceptions['noDelete']);
			}
			$this->TodoModel->UpdateTodo(array('userId'=>$params['userId'], 'id' => $params['id']));
			// Log action
			$activity = array(
				'accountId' => $params['accountId'],
				'userId' => $params['userId'],
				'actionTable' => 'todo',
				'actionId' => $params['id'],
				'actionType' => 'modified tags'
			);
			$this->UserModel->LogActivity($activity);
			$data['rtn']['success'] = TRUE;
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			echo $e->getMessage();
		}
	}

/* ---------------------------------------------------------------------------- *
	GET USER POPULAR TAGS
	Required :: (userId)
	Returns: SUCCESS :: TRUE / FALSE : JSON OBJ OF TAGS
	Endpoint: /Todo/FetchUserPopularTags
	Method: GET
 * ---------------------------------------------------------------------------- */
	function FetchUserPopularTags() {
		$params = $_GET;
		try {
			// Missing required information
			if(
				(
					is_null($params) && !is_array($params))
					|| !isset($params['userId']) 
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
			// Try to create
			if(!$tags = $this->TagModel->FetchUserPopularTags($params)) {
				throw new Exception($this->exceptions['noCreate']);
			}
			$data['rtn']['tags'] = $tags;
			$data['rtn']['success'] = TRUE;
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			echo $e->getMessage();
		}
	}

/* ---------------------------------------------------------------------------- *
	REACT TO A TODO
	Required :: (toid) (fromId) (actionId) (activityId) (isGood) (isBad)
	Returns: SUCCESS :: TRUE / FALSE
	Endpoint: /Todo/React
	Method: POST
 * ---------------------------------------------------------------------------- */
	function React() {
		$params = $this->input->post();
		try {
			// Missing required information
			if
				(
					(is_null($params) && !is_array($params)) 
					|| !isset($params['toId']) 
					|| !isset($params['fromId']) 
					|| !isset($params['actionId']) 
					|| !isset($params['activityId']) 
					|| !isset($params['isGood']) 
					|| !isset($params['isBad'])  
					|| !isset($params['isDispute'])  
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
			// Try to create
			if(!$this->TodoModel->React($params)) {
				throw new Exception($this->exceptions['noDelete']);
			}

			$actionType = ($params['isGood']==1) ? 'Kudos!' : 'Boo!';

			// Log action
			$activity = array(
				'accountId' => $params['accountId'],
				'userId' => $params['fromId'],
				'actionTable' => 'user_activity',
				'actionId' => $params['actionId'],
				'actionType' => 'gave a '.$actionType
			);
			$this->UserModel->LogActivity($activity);
			$data['rtn']['success'] = TRUE;
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			echo $e->getMessage();
		}
	}

/* ---------------------------------------------------------------------------- *
	DELETE REACTION TO A TODO
	Required :: (id) (userId) (todoId)
	Returns: SUCCESS :: TRUE / FALSE
	Endpoint: /Todo/ReactDelete
	Method: POST
 * ---------------------------------------------------------------------------- */
	function ReactDelete() {
		$params = $this->input->post();
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
			if(!$this->TodoModel->ReactDelete($params)) {
				throw new Exception($this->exceptions['noDelete']);
			}
			$data['rtn']['success'] = TRUE;
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			echo $e->getMessage();
		}
	}

/* ---------------------------------------------------------------------------- *
	SUGGESTION FROM USER TO USER
	Required :: (fromId) (suggestion)
	Returns: SUCCESS :: TRUE / FALSE
	Endpoint: /Todo/Suggestion
	Method: POST
 * ---------------------------------------------------------------------------- */
	function Suggestion() {
		$params = $this->input->post();
		try {
			// Missing required information
			if
				(
					(is_null($params) && !is_array($params)) 
					|| !isset($params['fromId']) 
					|| !isset($params['suggestion']) 
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
			// Try to create
			if(!$this->TodoModel->Suggestion($params)) {
				throw new Exception($this->exceptions['noCreate']);
			}
			$data['rtn']['success'] = TRUE;
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			echo $e->getMessage();
		}
	}

}

/* End of file Todo.php */
/* Location: ./application/controllers/Todo.php */