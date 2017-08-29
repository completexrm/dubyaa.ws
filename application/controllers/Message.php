<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Message extends CI_Controller {

/* ---------------------------------------------------------------------------- *
	DUBYAA CLASS "Message"
	Author: Brian Garstka
	EndPoint: /Message
	This class deals with all operations around messages
* ---------------------------------------------------------------------------- */

	function __construct() {
		parent::__construct();
		// COMMON Model
		$this->load->model('CommonModel','',TRUE);
		// REPORT Model
		$this->load->model('MessageModel','',TRUE);
		// USER Model
		$this->load->model('UserModel','',TRUE);
		// App-level Exception messaging :: /application/config/dubyaa_variables.php
		$this->exceptions = $this->config->item('exceptions');
	}

/* ---------------------------------------------------------------------------- *
	Report Index path
	Required :: NA
	Returns: 500 Server Error :: Don't allow direct access to Class
	Endpoint: /Message
	Method: N/A
 * ---------------------------------------------------------------------------- */
	function index() {
		http_response_code(500);
		echo $this->exceptions['noPage'];
	}

/* ---------------------------------------------------------------------------- *
	GET USER POINTS TO DATE
	Required :: (start) (limit)
	Returns: SUCCESS :: TRUE / FALSE :: JSON OBJ WITH MESSAGES
	Endpoint: /Message/All
	Method: GET
 * ---------------------------------------------------------------------------- */
	function All () {
		$params = $_GET;
		try {
			// Missing required information
			if
				(
					(is_null($params) && !is_array($params)) 
					|| !isset($params['start']) 
					|| !isset($params['limit']) 
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
			// Setup current quarter variable
			if(!$messages = $this->MessageModel->All($params)) {
				$data['rtn']['success'] = FALSE;
			} else {
				$data['rtn']['success'] = TRUE;
				$data['rtn']['messages'] = $messages['list'];
				$data['rtn']['totalMessages'] = $messages['totalMessages'];
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
	POST NEW MESSAGE
	Required :: (userId) (message)
	Returns: SUCCESS :: TRUE / FALSE
	Endpoint: /Message/Post
	Method: POST
 * ---------------------------------------------------------------------------- */
	function Post () {
		$params = $this->input->post();
		try {
			// Missing required information
			if
				(
					(is_null($params) && !is_array($params)) 
					|| !isset($params['userId']) 
					|| !isset($params['message']) 
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
			if(!$this->MessageModel->Post($params)) {
				$data['rtn']['success'] = FALSE;
			} else {
				$data['rtn']['success'] = TRUE;
			}
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			echo $e->getMessage();
			log_message('error', $e->getMessage());
		}	
	}

/* ---------------------------------------------------------------------------- *
	DELETE MESSAGE
	Required :: (id) (userId)
	Returns: SUCCESS :: TRUE / FALSE
	Endpoint: /Message/Delete
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
					|| !isset($params['id']) 
				)
			{
				throw new Exception($this->exceptions['noInfo']);
			}
			if(!$this->MessageModel->Delete($params)) {
				$data['rtn']['success'] = FALSE;
			} else {
				$data['rtn']['success'] = TRUE;
			}
			$this->load->view('json', $data);
		}
		// Something went horribly wrong
		catch (Exception $e) {
			echo $e->getMessage();
			log_message('error', $e->getMessage());
		}	
	}

}

/* End of file Message.php */
/* Location: ./application/controllers/Message.php */