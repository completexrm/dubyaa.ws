<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Authenticate extends CI_Controller {

	function __construct() {
		header('Access-Control-Allow-Origin: *');
    	header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
		parent::__construct();
		$this->load->model('CommonModel','',TRUE);
		$this->load->model('UserModel','',TRUE);
		$this->load->model('AccountModel','',TRUE);
		// App-level Exception messaging :: /application/config/dubyaa_variables.php
		$this->exceptions = $this->config->item('exceptions');
		// App-level variables :: /application/config/dubyaa_variables.php
		$this->dubyaaConfig = $this->config->item('dubyaa');
		// Environment variables :: /application/config/dubyaa_variables.php
		$this->environmentConfig = $this->config->item('environment');
	}

	function Go() {
		// setup params
		$params = $this->input->post();
		// prevent injection
		if(isset($params) && is_array($params)) {
			foreach($params as $key => $val) {
				$params[$key] = mysql_real_escape_string($val);
			}
		}
		// setup vars
		$emailAddress = $params['emailAddress'];
		$pw = $params['pw'];
		// grab user info from email
		$user = $this->UserModel->getUserHash($emailAddress);
		try {
			
			if(is_null($user)) { 
				throw new Exception('Could not authenticate, no user found');
			}

			if($params['isUH']==1) {
				if($pw != $user['profile']['userHash']) {
					throw new Exception('Could not authenticate, bad password');
				}
			} else {
				if(!password_verify($pw, $user['profile']['userHash'])) {
					throw new Exception('Could not authenticate, bad password');
				}
			}

			// compile associated account
			$user['account'] = $this->AccountModel->FetchAccount($user['profile']['accountId']);

			// prevent secured hash from being passed into app
			// unset($user['profile']['userHash']);
			$data['rtn']['user'] = $user['profile'];
			$data['rtn']['prefs'] = $user['prefs'];
			$data['rtn']['account'] = $user['account'];
			$data['rtn']['success'] = true;
			
			$this->load->view('json', $data);
			
		} catch(Exception $e) {
			http_response_code(500);
			echo $e->getMessage();	
		}
	}

	function Auto() {
		// setup params
		$data = $this->input->post();
		// prevent injection
		if(isset($data) && is_array($data)) {
			foreach($data as $key => $val) {
				$data[$key] = mysql_real_escape_string($val);
			}
		}
		try {
			if(!$user = $this->UserModel->validateAutoLoginKey($data)) {
				throw new Exception('Could not authenticate, link has expired or data is missing/corrupt');
			}
			// compile associated account
			$user['account'] = $this->AccountModel->FetchAccount($user['profile']['accountId']);
			$data['rtn']['user'] = $user['profile'];
			$data['rtn']['prefs'] = $user['prefs'];
			$data['rtn']['account'] = $user['account'];
			$data['rtn']['success'] = true;

			$this->load->view('json', $data);
		} catch(Exception $e) {
			http_response_code(500);
			echo $e->getMessage();	
		}
	}

/* ---------------------------------------------------------------------------- *
	FORGOT LOGIN EMAIL
	Required :: EMAIL ADDRESS
	Returns: NA
	Endpoint: /Authenticate/Help (run form submit)
	Method: POST
 * ---------------------------------------------------------------------------- */
	function Help () {
		// Fetch user information
		$params = $this->input->post();
		
		// Ensure we have results
		if(isset($params) && is_array($params)) {
			
			// Pull in mail API
			$this->load->library('PostageApp');
			// Setup API variables
			$this->postageapp->from('noreply@dubyaa.com');
			$this->postageapp->template('login_help');
			
			$user = $this->UserModel->getUserHash($params['emailAddress']);
			
			$id = $user['profile']['id'];
			$key = $this->CommonModel->RandomString(24);
			
			$this->UserModel->genAutoLoginKey($id, $key);
			$url = $this->environmentConfig['url'].'/login/auto/'.$key.'/'.$params['emailAddress'];
			
			$this->postageapp->variables(array('url' => $url));
			$this->postageapp->to($params['emailAddress']);
			if($this->postageapp->send()) {
				$data['rtn']['success'] = true;
			} else {
				$data['rtn']['success'] = false;
			}
			$this->load->view('json', $data);
		}
	}

/* ---------------------------------------------------------------------------- *
	STORE AN AUTHENTICATED, CREATED NODEJS SESSION
	Required :: SESSION OBJECT
	Returns: NA
	Endpoint: /Authenticate/StoreSession
	Method: POST
 * ---------------------------------------------------------------------------- */
	function StoreSession () {
		// Fetch user information
		$params = $this->input->post();
		// Ensure we have results
		if(isset($params) && is_array($params)) {
			$this->UserModel->StoreSession($params);
		}
	}

/* ---------------------------------------------------------------------------- *
	KILL AN AUTHENTICATED, CREATED NODEJS SESSION
	Required :: SESSION OBJECT
	Returns: NA
	Endpoint: /Authenticate/StoreSession
	Method: POST
 * ---------------------------------------------------------------------------- */
	function KillSession () {
		// Fetch user information
		$params = $this->input->post();
		try {
			// Missing required information
			if
				(
					(is_null($params) && !is_array($params)) 
					|| !isset($params['si']) 
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
			if(!$this->UserModel->KillSession($params)) {
				throw new Exception('there was a problem at the DB layer');
			}

			$data['rtn']['success'] = TRUE;
			$this->load->view('json', $data);

		}
		// Something went horribly wrong
		catch (Exception $e) {
			http_response_code(500);
			echo $e->getMessage();
		}	
	}

}

/* End of file Authenticate.php */
/* Location: ./application/controllers/Authenticate.php */