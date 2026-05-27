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
	$addNewHouses = false;
	$AdressenArray = $String = array();

	$file		= current($files);
	$OpdrachtID = substr($file, 5, -5);
		
	$OpdrachtData			= getOpdrachtData($OpdrachtID);
	$PushMembers			= getMembers4Opdracht($OpdrachtID, 'push');

	if($OpdrachtData['type'] == 1)	$addNewHouses = true;
	
	toLog('info', $OpdrachtID, '0', 'Inladen huizen voor '. $OpdrachtData['naam']);	

	$jsonHouses = file_get_contents($jsonDirSearch.$file);
	$json = explode("\n", $jsonHouses);

	# $Huizen is nu een array met per huis de HTML-code
	$NrPageHuizen		= count($json);

	$block[] = "Aantal huizen voor <a href='$file'>". $OpdrachtData['naam'] ."</a> : ". $NrPageHuizen ."<br>\n";
	
	foreach($json as $jsonHouse) {
		if($jsonHouse != '') {
			$newHouse	= false;
			$verkocht	= false;
			$vov		= false;
			$bod		= false;

			set_time_limit(10);
			$houseData = json_decode($jsonHouse, true);
			
			$data['id']				= $houseData['global_id'];
			$data['straat']			= $houseData['street_name'];
			$data['nummer']			= $houseData['house_number'];
			$data['letter']			= $houseData['house_number_suffix'];
			$data['plaats']			= $houseData['city'];		
			$data['prijs']			= $houseData['price'];
			$data['makelaar']		= $houseData['broker_name'];
			$data['tiny_id']		= $houseData['tiny_id'];
			$data['begin']			= convertStr2Unix($houseData['publish_date']);
			$data['openhuis']		= $houseData['open_house'];
			$data['status']			= $houseData['status'];

			switch ($data['status']) {
				case "Beschikbaar":
					$data['verkocht'] = 0;			
					break;	
				case "Verkocht":
					$data['verkocht'] = 1;
					$verkocht = true;
					break;
				case "Verkocht onder voorbehoud":
					$data['verkocht'] = 2;
					$vov = true;
					break;
				case "Onder optie":
					$data['verkocht'] = 3;
					$bod = true;
					break;
				case "Onder bod":
					$data['verkocht'] = 4;
					$bod = true;
					break;
				default:
					$data['verkocht'] = 0;
					break;
			}

			# Na een aantal keer kan deze uit (dan is alle data wel ververst obv de JSON)
			migrateID($data['tiny_id'], $data['id']);

			$bekendHuis = false;
			if(knownHouse($data['id']))	$bekendHuis = true;	
			
			# Hou bij welke huizen gevonden zijn						
			#$AdressenArray[] = $data['adres'];
			$AdressenArray[] = $data['straat'].' '.$data['nummer'];
			$ids[] = $data['id'];

			#
			# Dit deel hieronder is algemeen, gaat over het huis in de huizen-tabel 
			#

			# Huis is nog niet bekend bij het script, dus moet worden toegevoegd
			if(!$bekendHuis && $addNewHouses) {			
				# Boolean dat dit een nieuw huis is op True zetten
				$newHouse = true;				
				
				# Gegevens over het huis opslaan
				if(!storeHouse($data)) {
					$ErrorMessage[] = "Toevoegen van ". formatStreetAndNumber($data['id']) ." aan het script ging niet goed";
					toLog('error', $OpdrachtID, $data['id'], 'Huis toevoegen aan script mislukt');
					$success = false;
				} else {					
					toLog('info', $OpdrachtID, $data['id'], 'Huis toevoegen aan script');									
				}

				# Aanvinken om in een later stadium de details op te vragen
				mark4Details($data['id']);

				# Prijs toevoegen / updaten
				updatePrice($data['id'], $data['prijs']);
			}

			# Bij een bekend huis of nieuw huis data bijwerken
			elseif($bekendHuis || $newHouse) {
				# Mocht hij wel bekend zijn, dan zetten wij hem op online
				# Dit voor het geval die om wat voor een reden dan ook een keer op offline is gezet				
				setOnline($data['id']);

				# Mocht hij niet verkocht zijn
				if(!$verkocht) {
					# Dan aangeven dat hij nog beschikbaar is
					if(!updateAvailability($data['id'], $data['begin'])) {
						echo "<font color='red'>Updaten van <b>". formatStreetAndNumber($data['id']) ."</b> is mislukt</font> | $sql<br>\n";
						$ErrorMessage[] = "Updaten van ". formatStreetAndNumber($data['id']) ." is mislukt";
						toLog('error', $OpdrachtID, $data['id'], "Update van huis kon niet worden gedaan");
					} else {
						toLog('debug', $OpdrachtID, $data['id'], 'Huis geupdate');
					}

					# Huis kan gedaald zijn in prijs					
					if(newPrice($data['id'], $data['prijs'])) {
						if(!updatePrice($data['id'], $data['prijs'])) {
							echo "Toevoegen van de prijs van <b>". formatStreetAndNumber($data['id']) ."</b> is mislukt | $sql<br>\n";
							$ErrorMessage[] = "Updaten van prijs (". $data['prijs'] .") aan ". formatStreetAndNumber($data['id']) ." ging niet goed";
							toLog('error', $OpdrachtID, $data['id'], "Nieuwe prijs van ". $data['prijs'] ." kon niet worden toegevoegd");
						} else {
							toLog('debug', $OpdrachtID, $data['id'], "Nieuwe vraagprijs");
						}
					}

					# De verkoopsstatus is gewijzigd
					if(getSoldState($data['id']) != $data['verkocht']) {
						$status = array (
							0 => 'Beschikbaar',
							1 => 'Verkocht',
							2 => 'Verkocht onder voorbehoud',
							3 => 'Onder optie',
							4 => 'Onder bod'
						);
						$oldStatus = getSoldState($data['id']);

						toLog('info', $OpdrachtID, $data['id'], 'Verkoop-status aangepast van '. $status[$oldStatus] .' naar '. $data['status']);
						changeSoldState($data['id'], $data['verkocht']);
					}


				# Mocht hij wel verkocht zijn maar niet als zodanig in de database staan, markeer huis dan als verkocht
				# en vink aan om in een later stadium de details op te vragen
				} elseif($verkocht && getSoldState($data['id']) != 1) {
					changeSoldState($data['id'], $data['verkocht']);
					mark4Details($data['id']);
				}


			}
			
			# Huis is nog niet bekend bij het script, maar hoeft niet te worden toegevoegd
			elseif(!$bekendHuis AND !$addNewHouses) {
				toLog('debug', $OpdrachtID, $data['id'], "Nieuw huis, maar toch niet toegevoegd");
				$counterSkipped++;
			}

			# Na een aantal keer kan deze uit (dan is alle data wel ververst obv de JSON)
			updateHouse($data, array());
			
			#
			# Dit deel hieronder is opdracht specifiek 
			#			

			# Kijk of dit huis al vaker gevonden is voor deze opdracht
			if(newHouse($data['id'], $OpdrachtID) AND $addNewHouses) {				
				if(!addHouse($data, $OpdrachtID)) {
					$ErrorMessage[] = "Toevoegen van ". formatStreetAndNumber($data['id']) ." aan opdracht $OpdrachtID ging niet goed";
					toLog('error', $OpdrachtID, $data['id'], 'Huis toekennen aan opdracht mislukt');
				} else {
					toLog('debug', $OpdrachtID, $data['id'], 'Huis toegekend aan opdracht');
				}
			
				$NewAddress[] = $data['straat'].' '.$data['nummer'];
					
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
	} else {
		$block[] = implode("<br>", $String)."\n";
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