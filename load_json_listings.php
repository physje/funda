<?php
include_once(__DIR__.'/include/config.php');
include_once($cfgGeneralIncludeDirectory.'class.phpPushover.php');
$db = connect_db();

$minUserLevel = 3;
$cfgProgDir = 'auth/';
include($cfgProgDir. "secure.php");

$String = $block = array();
$success = false;
	
if ($handle = opendir($jsonDirList)) {
	while (false !== ($entry = readdir($handle))) {
		if ($entry != "." && $entry != ".." && !strpos($entry, 'prices')) {
			$files[] = $entry;
		}
	}	
	closedir($handle);
}

$debug = 0;

if(count($files) > 10) {
	$files			= array_slice($files, 0, 10);
	$userInteraction = false;
}

include_once(__DIR__ .'/include/HTML_TopBottom.php');

# Doorloop alle offline-bestanden
foreach($files as $file) {
	$String = array();
	$fileID = substr($file, 0, -5);

	$json = file_get_contents($jsonDirList.$file);	
	$listingArray = json_decode($json, true);	

	$json = file_get_contents($jsonDirList.$fileID.'_prices.json');
	$priceArray = json_decode($json, true);

	$dataArray = $listingArray['data'];

	$data['id']			= $fundaID = $dataArray['global_id'];
	$data['tiny_id']	= $dataArray['tiny_id'];
	$data['url']		= $dataArray['url'];
	$data['wijk']		= $dataArray['neighbourhood'];
	$data['adres']		= $dataArray['title'];
	$data['straat']		= '';#$dataArray[''];
	$data['nummer']		= $dataArray['house_number'];
	$data['letter']		= $dataArray['house_number_ext'];
	#$data['toevoeging']	= $dataArray[''];
	$data['PC_c']		= substr($dataArray['postcode'], 0, 4);
	$data['PC_l']		= substr($dataArray['postcode'], 4, 2);
	$data['plaats']		= $dataArray['city'];
	#$data['makelaar']	= $dataArray[''];
	$data['verkocht']	= ($dataArray['status'] == 'sold' ? 1 : 0);
	$data['openhuis']	= ($dataArray['open_house'] == 'true' ? 1 : 0);
	$data['prijs']		= $dataArray['price'];
	$data['thumb']		= substr($dataArray['photo_urls'][0], 0, -4).'_360x240.jpg';

	# Na een aantal keer kan deze uit (dan is alle data wel ververst obv de JSON)
	migrateID($data['tiny_id'], $data['id']);

	$extraData['Aangeboden sinds']	= convertStr2Unix($dataArray['publication_date']);
	$extraData['descr']				= $dataArray['description'];

	$foto = array();
	foreach($dataArray['photo_urls'] as $f) {
		$foto[] = substr($f, 0, -4).'_360x240.jpg';
	}
	
	$extraData['foto']				= implode('|', $foto);

	foreach($dataArray['characteristics'] as $key => $value) {
		$extraData[$key] = trim(strip_tags($value));	
	}

	foreach($priceArray as $history) {
		if($history['badge_text'] == 'Verkocht') {
			$extraData['Verkoopdatum'] = convertStr2Unix($history['timestamp']);
		}

		if($history['badge_text'] == 'Vraagprijs') {
			$extraData['Aangeboden sinds'] = convertStr2Unix($history['timestamp']);
		}

		if($history['source'] == 'Funda') {
			updatePrice($fundaID, $history['price'], convertStr2Unix($history['timestamp']));
		}
	}

	# Als wij een huis niet kennen klopt er iets niet
	if(!knownHouse($fundaID)) {
		toLog('error', '0', $fundaID, 'Huis niet bekend');				
											
		#addHouse($data, $id)
		if(saveHouse($data, $extraData)) {
			$String[] = "<a href='". $ScriptURL ."admin/edit.php?id=". $fundaID ."'>". formatStreetAndNumber($data['id']) ."</a> blijkt nog niet te bestaan, daarom toegevoegd<br>\n";
			
			updateHouse($data, $extraData);			
			addKnowCoordinates($dataArray["coordinates"], $fundaID);
													
			if($fundaID[0] == '8') {
				$sql_slave	= "SELECT * FROM $TableHuizen WHERE $HuizenAdres like '". urlencode($data['adres']) ."' AND  $HuizenPlaats like '". urlencode($data['plaats']) ."' AND $HuizenDetails like '1' AND $HuizenID NOT LIKE ". $fundaID;
				$result_slave	= mysqli_query($db, $sql_slave);										
				
				if(mysqli_num_rows($result_slave) == 1) {
					$row_slave = mysqli_fetch_array($result_slave);
					
					if(combineMasterSlave($fundaID, $row_slave[$HuizenID])) {
						#$String[] = "-> ". $sql_huis;
						$String[] = "-> lijkt master te zijn van <a href='http://www.funda.nl/".$row_slave[$HuizenID] ."'>". $row_slave[$HuizenID] ."</a>";
					}
				}
			}
		} else {
			$String[] = "<a href='". $ScriptURL ."admin/edit.php?id=$fundaID'>". formatStreetAndNumber($data['id']) ."</a> bleek nog niet te bestaan, maar kon niet toegevoegd worden<br>\n";					
		}
		$success = false;
			
	# Meestal zal het huis wel bekend zijn
	} else {
		$String[] = "Details van <a href='". $ScriptURL ."admin/edit.php?id=". $fundaID ."'>". formatStreetAndNumber($data['id']) ."</a> ingelezen<br>\n";
		
		$oldData = getFundaData($fundaID);
		
		updateHouse($data, $extraData);
		//addCoordinates($data['adres'], $data['PC_c'], $data['plaats'], $fundaID);
		addKnowCoordinates($dataArray["coordinates"], $fundaID);
		#updatePrice($fundaID, $data['prijs'], time());
		
		# Als hij nog niet verkocht is moeten wij dat aangeven
		if($data['verkocht'] != 1) {
			if(isset($data['start']) AND $oldData['start'] > $data['start']) {
				updateAvailability($fundaID, $data['start']);
			} else {
				updateAvailability($fundaID);
			}

		# Als hij wel verkocht is moeten we de administratie daarvan even bijwerken
		} else {
			$temp = updateVerkochtDataFromPage($data, $extraData);
			$String[] = implode("<br>\n", $temp)."<br>\n";
		}
		
		# Hij heeft open huis, data invoegen in de database
		if($data['openhuis'] == 1) {
			$bestaandeTijden 	= getNextOpenhuis($fundaID);
			$tijden						= $data['oh-tijden'];
	
			if($bestaandeTijden[0] != '' AND ($tijden[0] != $bestaandeTijden[0] OR $tijden[1] != $bestaandeTijden[1])) {
				deleteOpenhuis($fundaID, $bestaandeTijden[0]);
				addOpenhuis($fundaID, $tijden);
				toLog('info', $OpdrachtID, $data['id'], 'Open Huis gewijzigd voor '. formatStreetAndNumber($fundaID));
			} elseif($bestaandeTijden[0] == '') {
				addOpenhuis($fundaID, $tijden);
				toLog('info', $OpdrachtID, $data['id'], 'Open Huis toegevoegd voor '. formatStreetAndNumber($fundaID));
			}
		}
						
		toLog('info', '0', $fundaID, 'Offline pagina van '. formatStreetAndNumber($fundaID) .' ingeladen');
		remove4Details($fundaID);				
		$success = true;
	}
	
	# Alleen als de import succesvol is verlopen mag de pagina verwijderd worden
	if($success) {
		unlink($jsonDirList.$file);
		unlink($jsonDirList.$fileID.'_prices.json');
	}
	
	$block[] = implode("\n", $String);
}


# Laat de resultaten vam de check netjes op het scherm zien.
$tweeKolom = false;
echo $HTMLHeader;
echo "<tr>\n";
echo "<td width='50%' valign='top' align='center'>\n";

foreach($block as $key => $value) {
	echo showBlock($value);
	echo '<p>';	
	if($key >= (count($block)/2 - 1) AND !$tweeKolom) {
		echo "</td><td width='50%' valign='top' align='center'>\n";
		$tweeKolom = true;
	}
}
echo "</td>\n";
echo "</tr>\n";

?>