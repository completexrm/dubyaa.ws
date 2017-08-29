<?php

Class CommonModel extends CI_Model {

	function InjectionCleanse($data) {
		if(isset($data) && is_array($data)) {
			foreach($data as $key=>$val) {
				$data[$key] = mysql_real_escape_string($val);
			}
		}
		return $data;
	}

	function RandomString($length = 16) {
		$c = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	    $cLen = strlen($c);
	    $key = '';
	    for ($i = 0; $i < $length; $i++) {
	        $key .= $c[rand(0, $cLen - 1)];
	    }
	    return $key;
	}

	function CurrentQuarter() {
		$rtn = array();
		switch(date('m')) {
			case 1: case 2: case 3:
				$rtn['quarter'] = '1';
				$rtn['begin'] = '01';
				$rtn['end'] = '03';
				break;
			case 4: case 5: case 6:
				$rtn['quarter'] = '2';
				$rtn['begin'] = '04';
				$rtn['end'] = '06';
				break;
			case 7: case 8: case 9:
				$rtn['quarter'] = '3';
				$rtn['begin'] = '07';
				$rtn['end'] = '09';
				break;
			case 10: case 11: case 12:
				$rtn['quarter'] = '4';
				$rtn['begin'] = '10';
				$rtn['end'] = '12';
				break;
		}
		return $rtn;
	}

	function tzConversion($data) {
		$tzOffsetSeconds = abs($data['tzOffset'] * 60);
		$dateTs = strtotime($data['date']) - $tzOffsetSeconds;
		$rtnDate = date('Y-m-d H:i:s', $dateTs);
		return $rtnDate;
	}

}
?>