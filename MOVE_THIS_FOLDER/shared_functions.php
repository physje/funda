<?php

function connect_db() {
	global $dbHostname, $dbUsername, $dbPassword, $dbName;

	
	$link = mysqli_connect($dbHostname, $dbUsername, $dbPassword, $dbName) or die("Oeps! Error " . mysqli_connect_error($link));
				
	return $link;
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
	# Get your own at https://www.locationiq.com/
	$AccessToken = "";
		
	if($straat != '')		$q[] = urlencode(html_entity_decode($straat));
	if($postcode != '')	$q[] = urlencode($postcode);
	if($plaats != '')		$q[] = urlencode(html_entity_decode($plaats));
	if($land != '')			$q[] = urlencode(html_entity_decode($land));
	
	$url = "https://eu1.locationiq.com/v1/search.php?key=$AccessToken";	
	$url .= "&q=". implode(",+", $q);
	$url .= "&format=json";
			
	$contents		= file_get_contents($url);
	$json				= json_decode($contents, true);		
	$lat				= $json[0]['lat'];
	$lon				= $json[0]['lon'];
	$latitude		= explode('.', $lat);
	$longitude	= explode('.', $lon);
	
	//echo $json .'<br>';
	//var_dump($contents); 
	
	return array($latitude[0], substr($latitude[1], 0, 5), $longitude[0], substr($longitude[1], 0, 5), $json[0]['importance']);	
}


function getDistance($coord1, $coord2) {
	//http://www.postcode.nl/index.php?PageID=151
		
	DEFINE ('R', 6367000); // Radius of the Earth in meters

    if(count($coord1) > 4) {
        $lat1 = $coord1[0] .'.'. $coord1[1];
	    $lon1 = $coord1[2] .'.'. $coord1[3];
    } else {
	    $lat1 = $coord1[0];
		$lon1 = $coord1[1];
    }
	
	if(count($coord2) > 4) {
		$lat2 = $coord2[0] .'.'. $coord2[1];
		$lon2 = $coord2[2] .'.'. $coord2[3];
	} else {
		$lat2 = $coord2[0];
		$lon2 = $coord2[1];
	}
	
	//echo $lat1.'|'.$lon1 .'<br>';
	//echo $lat2.'|'.$lon2 .'<br>';
	
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

function generatePassword ($length = 8) {
	// start with a blank password
	$password = "";
	$possible = "";
	
	// define possible characters - any character in this string can be
	// picked for use in the password, so if you want to put vowels back in
  // or add special characters such as exclamation marks, this is where
  // you should do it
  //$possible = "1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!#$%&";
  $possible .= "1234567890";
  $possible .= "bcdfghjkmnpqrtvwxyz";
  $possible .= "BCDFGHJKLMNPQRTVWXYZ";
  $possible .= "!#$%&";
  
  // we refer to the length of $possible a few times, so let's grab it now
  $maxlength = strlen($possible);
  
  // check for length overflow and truncate if necessary
  if ($length > $maxlength) {
  	$length = $maxlength;
  }
  
  // set up a counter for how many characters are in the password so far
  $i = 0;
  
  // add random characters to $password until $length is reached
  while ($i < $length) { 
  	// pick a random character from the possible ones
  	$char = substr($possible, mt_rand(0, $maxlength-1), 1);
  	
  	// have we already used this character in $password?
  	if (!strstr($password, $char)) {
  		// no, so it's OK to add it onto the end of whatever we've already got...
  		$password .= $char;
      // ... and increase the counter by one
      $i++;
    }
  }
  
  // done!
  return $password;
}


?>