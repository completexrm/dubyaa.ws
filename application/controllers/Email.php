<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Email extends CI_Controller {

/* ---------------------------------------------------------------------------- *
	DUBYAA CLASS "Email"
	Author: Brian Garstka
	EndPoint: /Email
	This class handles all automated operations for system
* ---------------------------------------------------------------------------- */

	function __construct() {
		parent::__construct();
		// COMMON Model
		$this->load->model('CommonModel','',TRUE);
		// USER Model
		$this->load->model('UserModel','',TRUE);
		// TODO Model
		$this->load->model('TodoModel','',TRUE);
		// Environment variables
		$this->environment = $this->config->item('environment');
		// Dubyaa config variables
		$this->dubyaaConfig = $this->config->item('dubyaa');
		// App-level Exception messaging :: /application/config/dubyaa_variables.php
		$this->exceptions = $this->config->item('exceptions');
	}

/* ---------------------------------------------------------------------------- *
	Email Index path
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
	NEW USER EMAIL
	Required :: NA
	Returns: NA
	Endpoint: /Email/Welcome (run on new registration / user creation)
	Method: POST
 * ---------------------------------------------------------------------------- */
	function Welcome () {
		// Fetch new user information
		$params = $this->input->post();
		// Ensure we have results
		if(isset($params) && is_array($params)) {
			// Pull in mail API
			$this->load->library('PostageApp');
			// Setup API variables
			$this->postageapp->from('noreply@dubyaa.com');
			$this->postageapp->template('welcome_email');
			$this->postageapp->variables(array('salutation' => $params['firstName'], 'url'=>$this->environment['url'].'/login/auto/'.$params['autoLoginKey'].'/'.$params['emailAddress']));
			$this->postageapp->to($params['emailAddress']);
			$this->postageapp->send();
		}
	}

/* ---------------------------------------------------------------------------- *
	DELETE USER EMAIL
	Required :: NA
	Returns: NA
	Endpoint: /Email/UserDeleted (run on user deletion)
	Method: POST
 * ---------------------------------------------------------------------------- */
	function UserDeleted () {
		// Fetch new user information
		$params = $this->input->post();
		// Ensure we have results
		if(isset($params) && is_array($params)) {
			// Pull in mail API
			$this->load->library('PostageApp');
			// Setup API variables
			$this->postageapp->from('noreply@dubyaa.com');
			$this->postageapp->subject('Your Dubyaa User Account is Suspended');
			$this->postageapp->template('user_deleted');
			$this->postageapp->variables(array('salutation' => $params['displayName']));
			$this->postageapp->to($params['emailAddress']);
			$this->postageapp->send();
		}
	}

/* ---------------------------------------------------------------------------- *
	PROD USER EMAIL
	Required :: NA
	Returns: NA
	Endpoint: /Email/Prod
	Method: POST
 * ---------------------------------------------------------------------------- */
	function Prod () {
		// Fetch new user information
		$params = $this->input->post();
		// Ensure we have results
		if(isset($params) && is_array($params)) {
			// Pull in mail API
			$this->load->library('PostageApp');
			// Setup API variables
			$this->postageapp->from('noreply@dubyaa.com');
			$this->postageapp->template('user_prod');
			$this->postageapp->variables(array('prodder' => $params['displayName'],'url'=>$this->environment['url']));
			$this->postageapp->to($params['emailAddress']);
			$this->postageapp->send();
		}
	}

/* ---------------------------------------------------------------------------- *
	DELEGATION TODO NOTIFICATION EMAIL
	Required :: NA
	Returns: NA
	Endpoint: /Email/DelegateNotify
	Method: POST
 * ---------------------------------------------------------------------------- */
	function DelegateNotify () {
		// Fetch new user information
		$params = $this->input->post();
		// Ensure we have results
		if(isset($params) && is_array($params)) {
			$user = $this->UserModel->getUser($params['userId']);
			$todo = $this->TodoModel->FetchTodo(array('id' => $params['id']));
			$todoDue = (!is_null($todo->dateDue)) ? date('m-d-Y', strtotime($todo->dateDue)) : 'None';

			// Pull in mail API
			$this->load->library('PostageApp');
			// Setup API variables
			$this->postageapp->from('noreply@dubyaa.com');
			$this->postageapp->subject('You Have a New Delegated Item');
			$this->postageapp->template('todo_delegated');
			$this->postageapp->variables(array('delegator'=>$params['delegatorName'], 'todolabel' => $todo->label, 'todopoints' => $todo->points, 'tododue' => $todoDue));
			$this->postageapp->to($user->emailAddress);
			$this->postageapp->send();
		}
	}

/* ---------------------------------------------------------------------------- *
	BOO EMAIL
	Required :: NA
	Returns: NA
	Endpoint: /Email/TodoBoo
	Method: POST
 * ---------------------------------------------------------------------------- */
	function TodoBoo () {
		// Fetch new user information
		$params = $this->input->post();
		// Ensure we have results
		if(isset($params) && is_array($params)) {

			$toUser = $this->UserModel->getUser($params['toId']);
			$toEmailAddress = $toUser->emailAddress;

			// Pull in mail API
			$this->load->library('PostageApp');
			// Setup API variables
			$this->postageapp->from('noreply@dubyaa.com');
			$this->postageapp->template('reaction_boo');
			$this->postageapp->variables(array('todoname' => $params['todoName'], 'salutation' => $params['displayName']));
			$this->postageapp->to($toEmailAddress);
			$this->postageapp->send();
		}
	}

/* ---------------------------------------------------------------------------- *
	KUDOS EMAIL
	Required :: NA
	Returns: NA
	Endpoint: /Email/TodoKudos
	Method: POST
 * ---------------------------------------------------------------------------- */
	function TodoKudos () {
		// Fetch new user information
		$params = $this->input->post();
		// Ensure we have results
		if(isset($params) && is_array($params)) {

			$toUser = $this->UserModel->getUser($params['toId']);
			$toEmailAddress = $toUser->emailAddress;

			// Pull in mail API
			$this->load->library('PostageApp');
			// Setup API variables
			$this->postageapp->from('noreply@dubyaa.com');
			$this->postageapp->template('reaction_kudos');
			$this->postageapp->variables(array('salutation' => $params['displayName'], 'whoreacted' => $params['whoReacted'], 'todoname' => $params['todoName']));
			$this->postageapp->to($toEmailAddress);
			$this->postageapp->send();
		}
	}

/* ---------------------------------------------------------------------------- *
	DISPUTE EMAIL
	Required :: NA
	Returns: NA
	Endpoint: /Email/TodoDispute
	Method: POST
 * ---------------------------------------------------------------------------- */
	function TodoDispute () {
		// Fetch new user information
		$params = $this->input->post();
		// Ensure we have results
		if(isset($params) && is_array($params)) {

			$toUser = $this->UserModel->getUser($params['toId']);
			$toEmailAddress = $toUser->emailAddress;

			// Pull in mail API
			$this->load->library('PostageApp');
			// Setup API variables
			$this->postageapp->from('noreply@dubyaa.com');
			$this->postageapp->template('reaction_dispute');
			$this->postageapp->variables(array('salutation' => $params['displayName'], 'todoname' => $params['todoName']));
			$this->postageapp->to($toEmailAddress);
			$this->postageapp->send();
		}
	}

}

/* End of file Email.php */
/* Location: ./application/controllers/Email.php */