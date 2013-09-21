<?php
function connect_db() {
	global $dbHostname, $dbUsername, $dbPassword, $dbName;
	
	$db = mysql_connect($dbHostname, $dbUsername, $dbPassword) OR connect_error();
	@mysql_select_db( "$dbName", $db) or select_db(); 
	return $db;
}


function connect_error() {
	echo "There went something wrong";
	die;
}


function select_db() {
	echo "Cannot reach the database";
	die;
}

function getString($start, $end, $string, $offset) {
	if ($start != '') {
		$startPos = strpos ($string, $start, $offset) + strlen($start);
	} else {
		$startPos = 0;
	}
	
	if ($end != '') {
		$eindPos	= strpos ($string, $end, $startPos);
	} else {
		$eindPos = strlen($string);
	}
		
	$text	= substr ($string, $startPos, $eindPos-$startPos);
	$rest	= substr ($string, $eindPos);
		
	return array($text, $rest);
}

function getCoordinates($straat, $postcode, $plaats, $land = 'Nederland') {
	$q		= urlencode($straat) .",+". urlencode($postcode) ."+". urlencode($plaats) .",+". urlencode($land);
	$url	= "http://maps.googleapis.com/maps/api/geocode/xml?address=$q&sensor=false";
		
	$contents	= file_get_contents($url);
	
	$status	= getString('<status>', '</status>', $contents, 0);

	if($status[0] == 'OK') {
		$location				= getString('<location>', '</location>', $contents, 0);
		$location_type	= getString('<location_type>', '</location_type>', $contents, 0);
		
		$lat					= getString('<lat>', '</lat>', $location[0], 0);;
		$lon					= getString('<lng>', '</lng>', $location[0], 0);;
		$latitude			= explode('.', $lat[0]);
		$longitude		= explode('.', $lon[0]);		

		return array($latitude[0], $latitude[1], $longitude[0], $longitude[1], $location_type[0]);
	} else {
		return array(0, 0, 0, 0, '');
	}
}

function getDistance($coord1, $coord2) {
	//http://www.postcode.nl/index.php?PageID=151
		
	DEFINE ('R', 6367000); // Radius of the Earth in meters

	$lat1 = $coord1[0] .'.'. $coord1[1];
	$lon1 = $coord1[2] .'.'. $coord1[3];
	
	$lat2 = $coord2[0] .'.'. $coord2[1];
	$lon2 = $coord2[2] .'.'. $coord2[3];
	
	//convert degrees to radians
	$lat1 = ($lat1 * pi() ) / 180;
	$lon1 = ($lon1 * pi() ) / 180;
	$lat2 = ($lat2 * pi() ) / 180;
	$lon2 = ($lon2 * pi() ) / 180;

	//Haversine Formula (http://www.movable-type.co.uk/scripts/GIS-FAQ-5.1.html)
	$dlon = $lon2 - $lon1;
	$dlat = $lat2 - $lat1;
	$a = pow(sin($dlat/2), 2) + cos($lat1) * cos($lat2) * pow(sin($dlon/2), 2);
	$intermediate_result = 2 * asin(min(1,sqrt($a)));
	$distance = R * $intermediate_result;

	return $distance;
}


function getParam($name, $default = '') {
	return isset($_REQUEST[$name]) ? $_REQUEST[$name] : $default;
}

function ago($datefrom,$dateto=-1) {
	// Defaults and assume if 0 is passed in that
	// its an error rather than the epoch
	
	if($datefrom==0)	{ return "Heel lang geleden"; }
	if($dateto==-1)		{ $dateto = time(); }
	
	// Calculate the difference in seconds betweeen
	// the two timestamps
	
	$difference = $dateto - $datefrom;
	
	// Based on the interval, determine the
	// number of units between the two dates
	// From this point on, you would be hard
	// pushed telling the difference between
	// this function and DateDiff. If the $datediff
	// returned is 1, be sure to return the singular
	// of the unit, e.g. 'day' rather 'days'
	
	switch(true) {
		// If difference is less than 60 seconds,
		// seconds is a good interval of choice
		case(strtotime('-1 min', $dateto) < $datefrom):
			$datediff = $difference;
			$res = ($datediff==1) ? $datediff.' seconde geleden' : $datediff.' seconden geleden';
			break;
		// If difference is between 60 seconds and
		// 60 minutes, minutes is a good interval
		case(strtotime('-1 hour', $dateto) < $datefrom):
			$datediff = floor($difference / 60);
			$res = ($datediff==1) ? $datediff.' minuut geleden' : $datediff.' minuten geleden';
			break;
		// If difference is between 1 hour and 24 hours
		// hours is a good interval
		case(strtotime('-1 day', $dateto) < $datefrom):
			$datediff = floor($difference / 60 / 60);
			$res = ($datediff==1) ? $datediff.' uur geleden' : $datediff.' uur geleden';
			break;
		// If difference is between 1 day and 7 days
		// days is a good interval               
		case(strtotime('-1 week', $dateto) < $datefrom):
			$day_difference = 1;
			while (strtotime('-'.$day_difference.' day', $dateto) >= $datefrom) {
				$day_difference++;
			}
			
			$datediff = $day_difference;
			$res = ($datediff==1) ? 'gisteren' : $datediff.' dagen geleden';
			break;
		// If difference is between 1 week and 30 days
		// weeks is a good interval           
		case(strtotime('-1 month', $dateto) < $datefrom):
		    $week_difference = 1;
		    while (strtotime('-'.$week_difference.' week', $dateto) >= $datefrom) {
		    	$week_difference++;
		    }
		   
		    $datediff = $week_difference;
		    $res = ($datediff==1) ? 'vorige week' : $datediff.' weken geleden';
		    break;           
		// If difference is between 30 days and 365 days
		// months is a good interval, again, the same thing
		// applies, if the 29th February happens to exist
		// between your 2 dates, the function will return
		// the 'incorrect' value for a day
		case(strtotime('-1 year', $dateto) < $datefrom):
		    $months_difference = 1;
		    while (strtotime('-'.$months_difference.' month', $dateto) >= $datefrom) {
		    	$months_difference++;
		    }
		   
		    $datediff = $months_difference;
		    $res = ($datediff==1) ? $datediff.' maand geleden' : $datediff.' maand geleden';
		
		    break;
		// If difference is greater than or equal to 365
		// days, return year. This will be incorrect if
		// for example, you call the function on the 28th April
		// 2008 passing in 29th April 2007. It will return
		// 1 year ago when in actual fact (yawn!) not quite
		// a year has gone by
		case(strtotime('-1 year', $dateto) >= $datefrom):
		    $year_difference = 1;
		    while (strtotime('-'.$year_difference.' year', $dateto) >= $datefrom) {
		    	$year_difference++;
		    }
		   
		    $datediff = $year_difference;
		    //$res = ($datediff==1) ? $datediff.' jaar geleden' : $datediff.' jaar geleden';
		    $res = ($datediff==1). $datediff.' jaar geleden';
		    break;
		   
	}
	return $res;
}


?>