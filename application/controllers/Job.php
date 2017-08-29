<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Job extends CI_Controller {

/* ---------------------------------------------------------------------------- *
	DUBYAA CLASS "Job"
	Author: Brian Garstka
	EndPoint: /Todo
	This class handles all automated operations for system
* ---------------------------------------------------------------------------- */

	function __construct() {
		parent::__construct();
		// COMMON Model
		$this->load->model('CommonModel','',TRUE);
		// USER Model
		$this->load->model('UserModel','',TRUE);
		// JOB Model
		$this->load->model('JobModel','',TRUE);
		// TODO Model
		$this->load->model('TodoModel','',TRUE);
		// Dubyaa config variables
		$this->dubyaaConfig = $this->config->item('dubyaa');
		$this->environmentConfig = $this->config->item('environment');
		$this->dubyaaScoring = $this->config->item('productivityScoring');
		// App-level Exception messaging :: /application/config/dubyaa_variables.php
		$this->exceptions = $this->config->item('exceptions');
	}

/* ---------------------------------------------------------------------------- *
	Job Index path
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
	DAILY AM REMINDER
	Required :: NA
	Returns: NA
	Endpoint: /Job/ReminderAM (run by CRONTAB)
	Method: NA
 * ---------------------------------------------------------------------------- */
	function ReminderAM () {
		// Fetch active user emails with hasReminder of 1
		$emailAddresses = $this->UserModel->GetUserRemindersMorning();
		// Ensure we have results
		if(is_array($emailAddresses) && count($emailAddresses)) {
			
			// Pull in mail API
			$this->load->library('PostageApp');
			// Setup API variables
			$this->postageapp->from('noreply@dubyaa.com');
			$this->postageapp->subject('Dubyaa! AM Reminder');
			
			foreach($emailAddresses as $id => $info) {

				if(!$this->JobModel->CheckUserJob($id, 'ReminderAM')) {
					if($info['hasReminders']) {
						$key = $this->CommonModel->RandomString(24);
						$this->UserModel->genAutoLoginKey($id, $key);
						$url = $this->environmentConfig['url'].'/login/auto/'.$key.'/'.$info['emailAddress'];
						$this->postageapp->variables(array('salutation' => $info['salutation'], 'url' => $url));
						$this->postageapp->template('reminder_morning');
						$this->postageapp->to($info['emailAddress']);
						$this->postageapp->send();
					}

					if($info['hasRemindersSMS'] && isset($info['mobileNumber']) && isset($info['mobileCarrierSMS'])) {
						$this->postageapp->template('reminder_morning_sms');
						$this->postageapp->to($info['mobileNumber'].'@'.$info['mobileCarrierSMS']);
						$this->postageapp->send();
					}

					$this->JobModel->Log($id, 'ReminderAM');

				}
			}
		}
	}

/* ---------------------------------------------------------------------------- *
	DAILY PM REMINDER
	Required :: NA
	Returns: NA
	Endpoint: /Job/ReminderPM (run by CRONTAB)
	Method: NA
 * ---------------------------------------------------------------------------- */
	function ReminderPM () {
		// Fetch active user emails with hasReminder of 1
		$emailAddresses = $this->UserModel->GetUserRemindersEvening();
		// Ensure we have results
		if(is_array($emailAddresses) && count($emailAddresses)) {
			
			// Pull in mail API
			$this->load->library('PostageApp');
			// Setup API variables
			$this->postageapp->from('noreply@dubyaa.com');
			$this->postageapp->subject('Dubyaa! PM Reminder');
			
			foreach($emailAddresses as $id => $info) {

				if(!$this->JobModel->CheckUserJob($id, 'ReminderPM')) {
					if($info['hasReminders']) {
						$key = $this->CommonModel->RandomString(24);
						$this->UserModel->genAutoLoginKey($id, $key);
						$url = $this->environmentConfig['url'].'/login/auto/'.$key.'/'.$info['emailAddress'];
						$this->postageapp->variables(array('salutation' => $info['salutation'], 'url' => $url));
						$this->postageapp->template('reminder_evening');
						$this->postageapp->to($info['emailAddress']);
						$this->postageapp->send();
					}

					if($info['hasRemindersSMS'] && isset($info['mobileNumber']) && isset($info['mobileCarrierSMS'])) {
						$this->postageapp->template('reminder_evening_sms');
						$this->postageapp->to($info['mobileNumber'].'@'.$info['mobileCarrierSMS']);
						$this->postageapp->send();
					}

					$this->JobModel->Log($id, 'ReminderPM');

				}
			}
		}
	}

/* ---------------------------------------------------------------------------- *
	NEW REGISTRATION EMAIL
	Required :: NA
	Returns: NA
	Endpoint: /Job/WelcomeEmail (run on new registration)
	Method: POST
 * ---------------------------------------------------------------------------- */
	function WelcomeEmail () {
		// Fetch new user information
		$params = $this->input->post();
		// Ensure we have results
		if(isset($params) && is_array($params)) {
			// Pull in mail API
			$this->load->library('postageapp');
			// Setup API variables
			$this->postageapp->from('noreply@dubyaa.com');
			$this->postageapp->subject('Welcome to Dubyaa!');
			$this->postageapp->template('welcome_email');
			$this->postageapp->variables(array('salutation' => $params['firstName'], $url=>$this->environmentConfig['url']));
			$this->postageapp->to($params['emailAddress']);
			$this->postageapp->send();
		}
	}

/* ---------------------------------------------------------------------------- *
	DAILY PRODUCTIVITY
	Required :: NA
	Returns: NA
	Endpoint: /Job/Productivity
	GET
 * ---------------------------------------------------------------------------- */
	function DailyProductivity () {
		$params['scoring'] = $this->dubyaaScoring;
		$params['day'] = date('Y-m-d');
		$params['userId'] = 0;
		$users = $this->UserModel->FetchAllActive();
		foreach($users as $id=>$info) {
			unset($params['userId']);
			$params['userId'] = $id;
			$params['accountId'] = $info['accountId'];
			if($this->TodoModel->DailyProductivity($params)) {
				$this->JobModel->Log($params['userId'], 'DailyProductivity');
			}
		}
	}

}

/* End of file Job.php */
/* Location: ./application/controllers/Job.php */