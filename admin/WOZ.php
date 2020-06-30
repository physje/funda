<?php
include_once(__DIR__.'/../include/config.php');
include_once('../include/HTML_TopBottom.php');

$minUserLevel = 1;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");
$db = connect_db();

$sql = "SELECT $HuizenID FROM $TableHuizen h WHERE NOT EXISTS (SELECT $WOZFundaID FROM $TableWOZ w WHERE h.$HuizenID = w.$WOZFundaID) AND $HuizenDetails = '0' ORDER BY $HuizenEind DESC LIMIT 0,3";
$result = mysqli_query($db, $sql);

if($row = mysqli_fetch_array($result)) {
	do {		
		$fundaID = $row[$HuizenID];
		echo $fundaID .'<br>';
		$WOZwaardes = extractWOZwaarde($fundaID);
		
		# Array met waardes teruggekregen
		if(is_array($WOZwaardes)) {
			foreach($WOZwaardes as $jaar => $waarde) {
				$sql_insert = "INSERT INTO $TableWOZ ($WOZFundaID, $WOZJaar, $WOZPrijs, $WOZLastCheck) VALUES ($fundaID, $jaar, $waarde, ". time() .")";
				if(mysqli_query($db, $sql_insert)) {
					echo $sql_insert .'<br>';
					toLog('debug', '', $fundaID, 'WOZ-waarde toegevoegd; '. $jaar .':'.$waarde);
				} else {
					toLog('error', '', $fundaID, 'Kon WOZ-waarde niet wegschrijven; '. $jaar .':'.$waarde);
				}
			}
			
		# Geen array, maar boolean false teruggekregen
		} elseif(!$WOZwaardes) {
			$sql_onbekend = "INSERT INTO $TableWOZ ($WOZFundaID, $WOZLastCheck) VALUES ($fundaID, ". time() .")";
					
			if(mysqli_query($db, $sql_onbekend)) {
				toLog('info', '', $fundaID, 'WOZ-waarde kon niet opgevraagd worden');
			} else {
				toLog('error', '', $fundaID, 'Wegschrijven van fout bij WOZ-waarde ging zelf ook fout');
			}			
		}
		sleep(3);
		
	} while($row = mysqli_fetch_array($result));
}

function extractWOZwaarde($fundaID) {
	$data = getFundaData($fundaID);
	
	$adres = $data['straat'].' '.$data['nummer'].$data['letter'];
		
	if($data['toevoeging'] != '') {
		$adres .= ' '.$data['toevoeging'];
	}
	
	if($data['PC_c'] == '' OR $data['PC_l'] == '') {
		$postcode = findPCbyAdress($data['straat'], $data['nummer'], $data['letter'], $data['toevoeging'], $data['plaats']);
		toLog('debug', '', $fundaID, 'PC onbekend voor WOZ-waarde; '. $postcode);
	} else {
		$postcode = $data['PC_c'].$data['PC_l'];
	}
	
	$url = "https://drimble.nl/adres/". strtolower($data['plaats']) ."/$postcode/". convert2FundaStyle($adres) .".html";	
	$contents = file_get_contents_retry($url);
	
	if(strpos($contents, 'Page not found / Adres niet gevonden.')) {
		toLog('debug', '', $fundaID, 'Adres bestaat niet voor WOZ; '. $adres);
		return false;
	} elseif(strpos($contents, '<title>404 Page not found</title>')) {
		toLog('debug', '', $fundaID, 'URL voor WOZ niet goed opgebouwd; '. $url);
		return false;
	} else {			
		$WOZ = getString('<td colspan="2" style="font-size:18px;padding-top:3px;padding-bottom:3px;">WOZ-waarde', '<td colspan="2" style="font-size:16px;padding-top:3px;padding-bottom:3px;background-color:#404040;color:#fff">', $contents, 0);
		$aWOZ = explode('style="width:20%;">Peildatum ', $WOZ[0]);
		
		# Een array van 1 betekent dat er geen WOZ-waardes bekend zijn
		if(count($aWOZ) < 2) {
			toLog('debug', '', $fundaID, 'Geen WOZ-waardes bekend');
			return false;
		}
		
		array_shift($aWOZ);
	
		foreach($aWOZ as $key => $value) {
			$jaar = getString('', ':', $value, 0);
			$bedrag = getString('&euro; ', '</td>', $value, 0);
			$export[trim($jaar[0])] = trim(str_replace('.', '', $bedrag[0]));		
		}
		return $export;
	}	
}

?>