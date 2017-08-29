<?php

$config['environment'] = array(
	'development' => TRUE,
	'url' => 'http://localhost:3000'
);

$config['exceptions'] = array(
	'noPage'		=>	'This method does not existr',
	'noInfo'		=>	'Missing required information or data is corrupt',
	'noCreate'		=>	'Could not create this record',
	'noFetch'		=>	'Could not retrieve this record',
	'noUpdate'		=>	'Could not update this record',
	'noDelete'		=>	'Could not delete this record',
	'noRecord'		=>	'Could not locate this record',
);

$config['dubyaa'] = array(
	'defaultInitialTeamName' =>		'My First Team',
	// IN DAYS
	'trialDuration'			 =>		7,
	'dubyaaMultiplier'		 =>		'2',
	'amRemindTime'			 =>		'06:00:00',
	'pmRemindTime'			 =>		'16:00:00',
	'maxOpen'				 =>		3,
	'badges'				 =>		array(
										'novice' => 2000,
										'expert' => 5000,
										'maven' => 12500
									)
);

$config['productivityScoring'] = array(
	'login'				=>	0.100,
	'planned'			=>	0.200,
	'expiration'		=>	0.150,
	'dubyaa'			=>	0.500,
	'social'			=>	0.025,
	'socialPositive'	=>	0.020,
	'trendingUp'		=>	0.005,
);

?>