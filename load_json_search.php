<?php
include_once(__DIR__.'/include/config.php');
include_once($cfgGeneralIncludeDirectory.'class.phpPushover.php');
$db = connect_db();

$minUserLevel = 3;
$cfgProgDir = 'auth/';
include($cfgProgDir. "secure.php");

$block = $files = array();
$success = false;
$counterSkipped = 0;
	
if ($handle = opendir($jsonDirSearch)) {
	while (false !== ($entry = readdir($handle))) {
		if ($entry != "." && $entry != "..") {
			$files[] = $entry;
		}
	}	
	closedir($handle);
}

$debug = 0;

if(count($files) > 0) {	
	$userInteraction = false;
}


include_once(__DIR__ .'/include/HTML_TopBottom.php');

# Doorloop alle offline-bestanden
if(count($files) > 0) {
	$file		= current($files);
	$OpdrachtID = substr($file, 5, -5);
	
	$AdressenArray = $String = array();
	$addNewHouses = false;

	$OpdrachtData			= getOpdrachtData($OpdrachtID);
	$PushMembers			= getMembers4Opdracht($OpdrachtID, 'push');

	if(strpos($file, 'sold')) {
		toLog('info', $OpdrachtID, '0', 'Inladen verkochte huizen voor '. $OpdrachtData['naam']);	
		$verkocht = true;
		$data['verkocht'] = 1;
	} else {
		toLog('info', $OpdrachtID, '0', 'Inladen huizen voor '. $OpdrachtData['naam']);	
		$verkocht = false;
	}
		
		
	if($OpdrachtData['type'] == 1)	$addNewHouses = true;

	$jsonHouses = file_get_contents($jsonDirSearch.$file);
	$json = explode("\n", $jsonHouses);

	# $Huizen is nu een array met per huis de HTML-code
	$NrPageHuizen		= count($json);

	$block[] = "Aantal huizen voor <a href='$file'>". $OpdrachtData['naam'] ."</a> : ". $NrPageHuizen ."<br>\n";
	
	foreach($json as $jsonHouse) {
		if($jsonHouse != '') {
			set_time_limit(10);
			$houseArray = json_decode($jsonHouse, true);
			$houseData = $houseArray['data'];

			$data['id']				= $houseData['global_id'];
			$data['url']			= $houseData['detail_url'];
			$data['adres']			= $houseData['title'];
			$data['straat']			= $houseData['street_name'];
			$data['nummer']			= $houseData['house_number'];
			$data['letter']			= $houseData['house_number_suffix'];
			$data['toevoeging']		= '';
			$data['PC_c']			= substr($houseData['postcode'], 0, 4);
			$data['PC_l']			= substr($houseData['postcode'], 4, 2);
			$data['wijk']			= $houseData['neighbourhood'];
			$data['plaats']			= $houseData['city'];
			$data['thumb']			= '';#substr($houseData['photo_urls'][0], 0, -4).'_360x240.jpg';
			$data['makelaar']		= $houseData['broker_name'];
			$data['prijs']			= $houseData['price'];
			$data['verkocht']		= 0;
			#$data['vov']			= 0;#$houseData[''];
			#$data['optie']			= 0;#$houseData[''];
			#$data['openhuis']		= 0;#$houseData[''];
			$data['tiny_id']		= substr($houseData['detail_url'], -9, -1);
			$data['begin']			= convertStr2Unix($houseData['publish_date']);

			# Na een aantal keer kan deze uit (dan is alle data wel ververst obv de JSON)
			#migrateID($data['tiny_id'], $data['id']);

			$bekendHuis = false;
			if(knownHouse($data['id']))	$bekendHuis = true;	
			
			# Hou bij welke huizen gevonden zijn						
			$AdressenArray[] = $data['adres'];
			$ids[] = $data['id'];

			# Huis is nog niet bekend bij het script, dus moet worden toegevoegd
			if(!$bekendHuis && $addNewHouses) {			
				$extraData = array();
				
				# Gegevens over het huis opslaan
				if(!saveHouse($data, $extraData)) {
					$ErrorMessage[] = "Toevoegen van ". formatStreetAndNumber($data['id']) ." aan het script ging niet goed";
					toLog('error', $OpdrachtID, $data['id'], 'Huis toevoegen aan script mislukt');
					$success = false;
				} else {					
					toLog('info', $OpdrachtID, $data['id'], 'Huis toevoegen aan script');					
				}
					
				# Prijs van het huis opslaan
				if(!updatePrice($data['id'], $data['prijs'])) {
					$ErrorMessage[] = "Toevoegen van prijs (". $data['prijs'] .") aan ". formatStreetAndNumber($data['id']) ." ging niet goed";
					toLog('error', $OpdrachtID, $data['id'], 'Prijs toevoegen mislukt');
				} else {
					toLog('debug', $OpdrachtID, $data['id'], "Prijs toegevoegd");
				}
				
				# Aanvinken om in een later stadium de details op te vragen
				mark4Details($data['id']);
			} elseif(!$bekendHuis AND !$addNewHouses) {
				toLog('debug', $OpdrachtID, $data['id'], "Nieuw huis, maar toch niet toegevoegd");
				$counterSkipped++;
			} elseif($bekendHuis) {
				# Mocht hij wel bekend zijn, dan zetten wij hem op online
				# Dit voor het geval die om wat voor een reden dan ook een keer op offline is gezet				
				setOnline($data['id']);

				# Na een aantal keer kan deze uit (dan is alle data wel ververst obv de JSON)
				#updateHouse($data, array());
			}

			# Huis is niet verkocht	
			if(!$verkocht AND $bekendHuis) {
				if(!updateAvailability($data['id'])) {
					echo "<font color='red'>Updaten van <b>". formatStreetAndNumber($data['id']) ."</b> is mislukt</font> | $sql<br>\n";
					$ErrorMessage[] = "Updaten van ". formatStreetAndNumber($data['id']) ." is mislukt";
					toLog('error', $OpdrachtID, $data['id'], "Update van huis kon niet worden gedaan");
				} else {
					toLog('debug', $OpdrachtID, $data['id'], 'Huis geupdate');
				}
						
				# Huis kan gedaald zijn in prijs
				# Dat moeten we dus controleren en indien nodig opslaan en melding van maken
				if(newPrice($data['id'], $data['prijs']) AND !$verkocht) {							
					if(!updatePrice($data['id'], $data['prijs'])) {
						echo "Toevoegen van de prijs van <b>". formatStreetAndNumber($data['id']) ."</b> is mislukt | $sql<br>\n";
						$ErrorMessage[] = "Updaten van prijs (". $data['prijs'] .") aan ". formatStreetAndNumber($data['id']) ." ging niet goed";
						toLog('error', $OpdrachtID, $data['id'], "Nieuwe prijs van ". $data['prijs'] ." kon niet worden toegevoegd");
					} else {
						toLog('debug', $OpdrachtID, $data['id'], "Nieuwe vraagprijs");
					}
				}
				
				/*
				# Huis kan onder voorbehoud verkocht zijn
				if($data['vov'] > 0) {
					if(!soldHouseTentative($data['id'])) {
						$sql = "UPDATE $TableHuizen SET $HuizenVerkocht = '2' WHERE $HuizenID like '". $data['id'] ."' OR $HuizenID2 like '". $data['id'] ."'";
						mysqli_query($db, $sql);
						toLog('info', $OpdrachtID, $data['id'], 'Onder voorbehoud verkocht');
					}
				# Het geval dat onder voorbehoud wordt teruggedraaid
				} elseif(soldHouseTentative($data['id']) AND $data['verkocht'] == 0) {
					$sql = "UPDATE $TableHuizen SET $HuizenVerkocht = '0' WHERE $HuizenID like '". $data['id'] ."' OR $HuizenID2 like '". $data['id'] ."'";
					mysqli_query($db, $sql);
					toLog('info', $OpdrachtID, $data['id'], 'Niet meer onder voorbehoud verkocht');
				}
				*/
			}

			# Kijk of dit huis al vaker gevonden is voor deze opdracht
			if(newHouse($data['id'], $OpdrachtID) AND $addNewHouses) {				
				if(!addHouse($data, $OpdrachtID)) {
					$ErrorMessage[] = "Toevoegen van ". formatStreetAndNumber($data['id']) ." aan opdracht $OpdrachtID ging niet goed";
					toLog('error', $OpdrachtID, $data['id'], 'Huis toekennen aan opdracht mislukt');
				} else {
					toLog('debug', $OpdrachtID, $data['id'], 'Huis toegekend aan opdracht');
				}
			
				$NewAddress[] = $data['adres'];
					
				if($debug == 0 AND !$verkocht) {
					sendPushoverNewHouse($data['id'], $OpdrachtID);
				}
			} elseif(changedPrice($data['id'], $data['prijs'], $OpdrachtID) AND $bekendHuis) {
				sendPushoverChangedPrice($data['id'], $OpdrachtID);
			}
		}
		$success = true;
	}

	$String[] = "<a href='$file'>Overzicht</a>". ($verkocht ? ' met verkochte huizen ' : ' ')."van <a href='". $OpdrachtData['url'] ."'>". $OpdrachtData['naam'] ."</a>; ". count($AdressenArray)  ." huizen gevonden<br>";

	if($debug == 1) {
		$block[] = implode("<br>", $AdressenArray)."\n";
	} elseif($debug == 3) {
		$handle = fopen("ids_". $OpdrachtID .".txt", "a+");
		fwrite($handle, implode("\n", $ids)."\n");
		fclose($handle);
	}

	# Alleen als de import succesvol is verlopen mag de pagina verwijderd worden
	if($success) {
		unlink($jsonDirSearch.$file);
	}


} else {
	$block[] = 'Geen bestanden gevonden';
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