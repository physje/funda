<?php
include_once(__DIR__.'/include/config.php');
include_once('include/HTML_TopBottom.php');
include_once($cfgGeneralIncludeDirectory.'class.phpPushover.php');
$db = connect_db();

for($i=0; $i<2 ; $i++) {
	$String = $block = array();
	
	if($i == 0) {
		$pageDir = $offlineDir.'koop/';
		$verkocht = false;
	} else {
		$pageDir = $offlineDir.'verkocht/';
		$verkocht = true;
	}
		
	if ($handle = opendir($pageDir)) {
		while (false !== ($entry = readdir($handle))) {
			if ($entry != "." && $entry != "..") {
				$files[] = $entry;
			}
	  }
		closedir($handle);
	}
	
	$debug = 0;

	# Doorloop alle zoekopdrachten
	foreach($files as $file) {
		# Alles initialiseren
		set_time_limit (30);
		$bestand = $pageDir.$file;
		$AdressenArray = array();
			
		# Bestandsnaam is opgebouwd volgens
		#		$debug_filename = 'funda_'. $OpdrachtID .'_'. $p .'.htm';
		$delen = explode('_', $file);
		$OpdrachtID = $delen[1];
		$p = substr($delen[2], 0, -5);
		
		$OpdrachtData			= getOpdrachtData($OpdrachtID);
		$PushMembers			= getMembers4Opdracht($OpdrachtID, 'push');
		
		$OpdrachtURL	= $OpdrachtData['url'];
		
		if($verkocht) {
			toLog('info', $OpdrachtID, '', 'Start inladen verkochte huizen voor '. $OpdrachtData['naam'] .", pagina $p");
		} else {
			toLog('info', $OpdrachtID, '', 'Start inladen huizen voor  '. $OpdrachtData['naam'] .", pagina $p");
		}
		
		//$String[] = "<a href='$OpdrachtURL'>". $OpdrachtData['naam'] ."</a><br>\n";
		//$block[] = implode("\n", $String);		
			
		$fp = fopen($bestand, 'r+');
		$contents = fread($fp, filesize($bestand));
		fclose($fp);
		
		# Code opknippen zodat er een array met HTML-code voor een huis ontstaat		
		$Huizen			= explode('<div class="search-result-media">', $contents);
		$Huizen			= array_slice($Huizen, 1);		
		$NrPageHuizen		= count($Huizen);
		
		if($debug == 1) {
			$block[] = "Aantal huizen in <a href='bestand'>pagina $p</a> : ". $NrPageHuizen ."<br>\n";
		}
		
		# Doorloop nu alle gevonden huizen op de overzichtspagina
		foreach($Huizen as $HuisText) {			
			# Extraheer hier adres, plaats, prijs, id etc. uit
			$data = extractFundaData($HuisText, $verkocht);
			$AdressenArray[] = $data['adres'];
							
			if($debug == 1) {
				$tempItems = array();
				foreach($data as $key => $value) {
					$tempItems[] = $key .' -> '. $value;
				}
				$block[] = implode('<br>', $tempItems);
			}
			
			# Huis is nog niet bekend bij het script, dus moet worden toegevoegd
			if(!knownHouse($data['id'])) {
				$extraData = array();
				
				# Gegevens over het huis opslaan
				if(!saveHouse($data, $extraData)) {
					$ErrorMessage[] = "Toevoegen van ". $data['adres'] ." aan het script ging niet goed";
					toLog('error', $OpdrachtID, $data['id'], 'Huis toevoegen aan script mislukt');
				} else {					
					toLog('info', $OpdrachtID, $data['id'], 'Huis toevoegen aan script');
				}
					
				# Coordinaten van het huis toevoegen
				if(!addCoordinates($data['adres'], $data['PC_c'], $data['plaats'], $data['id'])) {					
					$ErrorMessage[] = "Toevoegen van coordinaten aan ". $data['adres'] ." ging niet goed";	
					toLog('error', $OpdrachtID, $data['id'], 'Coordinaten toevoegen mislukt');
				} else {
					toLog('debug', $OpdrachtID, $data['id'], "Coordinaten toegevoegd");
				}
					
				# Prijs van het huis opslaan
				if(!updatePrice($data['id'], $data['prijs'])) {
					$ErrorMessage[] = "Toevoegen van prijs (". $data['prijs'] .") aan ". $data['adres'] ." ging niet goed";
					toLog('error', $OpdrachtID, $data['id'], 'Prijs toevoegen mislukt');
				} else {
					toLog('debug', $OpdrachtID, $data['id'], "Prijs toegevoegd");
				}
				
				# Aanvinken om in een later stadium de details op te vragen
				mark4Details($data['id']);
			}
			
			# Huis is al bekend bij het script
			# We moeten dus aangeven dat hij nog steeds op de markt is
			if(!$verkocht) {
				if(!updateAvailability($data['id'])) {
					echo "<font color='red'>Updaten van <b>". $data['adres'] ."</b> is mislukt</font> | $sql<br>\n";
					$ErrorMessage[] = "Updaten van ". $data['adres'] ." is mislukt";
					toLog('error', $OpdrachtID, $data['id'], "Update van huis kon niet worden gedaan");
				} else {
					toLog('debug', $OpdrachtID, $data['id'], 'Huis geupdate');
				}
						
				# Huis kan gedaald zijn in prijs
				# Dat moeten we dus controleren en indien nodig opslaan en melding van maken
				if(newPrice($data['id'], $data['prijs']) AND !$verkocht) {							
					if(!updatePrice($data['id'], $data['prijs'])) {
						echo "Toevoegen van de prijs van <b>". $data['adres'] ."</b> is mislukt | $sql<br>\n";
						$ErrorMessage[] = "Updaten van prijs (". $data['prijs'] .") aan ". $data['adres'] ." ging niet goed";
						toLog('error', $OpdrachtID, $data['id'], "Nieuwe prijs van ". $data['prijs'] ." kon niet worden toegevoegd");
					} else {
						toLog('debug', $OpdrachtID, $data['id'], "Nieuwe vraagprijs");
					}
				}
					
				# Huis kan onder voorbehoud verkocht zijn
				if($data['vov'] > 0) {
					if(!soldHouseTentative($data['id'])) {
						$sql = "UPDATE $TableHuizen SET $HuizenVerkocht = '2' WHERE $HuizenID like '". $data['id'] ."'";
						mysqli_query($db, $sql);
						toLog('info', $OpdrachtID, $data['id'], 'Onder voorbehoud verkocht');
					}
				# Het geval dat onder voorbehoud wordt teruggedraaid
				} elseif(soldHouseTentative($data['id']) AND $data['verkocht'] == 0) {
					$sql = "UPDATE $TableHuizen SET $HuizenVerkocht = '0' WHERE $HuizenID like '". $data['id'] ."'";
					mysqli_query($db, $sql);
					toLog('info', $OpdrachtID, $data['id'], 'Niet meer onder voorbehoud verkocht');
				}
			}
			
			# Huis kan ook echt verkocht zijn
			if($data['verkocht'] == 1) {
				if(!soldHouse($data['id'])) {
					$sql = "UPDATE $TableHuizen SET $HuizenVerkocht = '1' WHERE $HuizenID like '". $data['id'] ."'";
					mysqli_query($db, $sql);
					toLog('info', $OpdrachtID, $data['id'], 'Verkocht');
					
					# Aanvinken om in een later stadium de details op te vragen
					mark4Details($data['id']);
				}
			# Het geval dat verkocht wordt teruggedraaid (hypotetisch)
			} elseif(soldHouse($data['id'])) {
				$sql = "UPDATE $TableHuizen SET $HuizenVerkocht = '0' WHERE $HuizenID like '". $data['id'] ."'";
				mysqli_query($db, $sql);
				toLog('info', $OpdrachtID, $data['id'], 'Toch niet meer verkocht');
			}
					
			# Huis kan openhuis hebben
			if($data['openhuis'] == 1) {
				# data online vergelijken met data in de database
				$changedOpenHuis	= false;
				$tijden			= extractOpenHuisData($data['id']);
				$bestaandeTijden	= getNextOpenhuis($data['id']);
			
				if($tijden[0] != $bestaandeTijden[0] OR $tijden[1] != $bestaandeTijden[1]) {
					$sql = "DELETE FROM $TableCalendar WHERE $CalendarHuis like ". $data['id'] ." AND $CalendarStart like ". $bestaandeTijden[0] ." AND $CalendarEnd like ". $bestaandeTijden[1];
					mysqli_query($db, $sql);
					$changedOpenHuis = true;
					toLog('info', $OpdrachtID, $data['id'], 'Open Huis gewijzigd');
				}
	
				if(!hasOpenHuis($data['id']) OR $changedOpenHuis) {
					toLog('info', $OpdrachtID, $data['id'], 'Open Huis aangekondigd');
					
					#	toevoegen aan de Google Calendar						
					$sql = "INSERT INTO $TableCalendar ($CalendarHuis, $CalendarStart, $CalendarEnd) VALUES (". $data['id'] .", ". $tijden[0] .", ". $tijden[1] .")";
					mysqli_query($db, $sql);
											
					#	opnemen in de eerst volgende mail						
					$sql = "UPDATE $TableHuizen SET $HuizenOpenHuis = '1' WHERE $HuizenID like '". $data['id'] ."'";
					mysqli_query($db, $sql);
				}
			} else {
				removeOpenHuis($data['id']);
			}				
						
			# Kijk of dit huis al vaker gevonden is voor deze opdracht
			if(newHouse($data['id'], $OpdrachtID)) {				
				if(!addHouse($data, $OpdrachtID)) {
					$ErrorMessage[] = "Toevoegen van ". $data['adres'] ." aan opdracht $OpdrachtID ging niet goed";
					toLog('error', $OpdrachtID, $data['id'], 'Huis toekennen aan opdracht mislukt');
				} else {
					toLog('debug', $OpdrachtID, $data['id'], 'Huis toegekend aan opdracht');
				}
		
				$NewAddress[] = $data['adres'];
					
				if($debug == 0 AND !$verkocht)	sendPushoverNewHouse($data['id'], $OpdrachtID);
			} elseif(changedPrice($data['id'], $data['prijs'], $OpdrachtID))	sendPushoverChangedPrice($data['id'], $OpdrachtID);
			
			if(!$verkocht)	addUpdateStreetDb(extractStreetFromAdress($data['adres']), $data['plaats']);
		}
	
		$String[] = "<a href='$bestand'>Pagina $p</a> van <a href='$OpdrachtURL'>". $OpdrachtData['naam'] ."</a> verwerkt ($file) en ". count($AdressenArray)  ." huizen gevonden<br>";
			
		toLog('debug', $OpdrachtID, '', "Einde pagina $p (". count($AdressenArray) ." huizen)");
		unlink($bestand);
	}
}
$block[] = implode("\n", $String);


# Laat de resultaten vam de check netjes op het scherm zien.
$tweeKolom = false;
echo $HTMLHeader;
echo "<tr>\n";
echo "<td width='50%' valign='top' align='center'>\n";

foreach($block as $key => $value) {
	echo showBlock($value);
	echo '<p>';	
	if($key > (count($block)/2 - 1) AND !$tweeKolom) {
		echo "</td><td width='50%' valign='top' align='center'>\n";
		$tweeKolom = true;
	}
}
echo "</td>\n";
echo "</tr>\n";
echo $HTMLFooter;