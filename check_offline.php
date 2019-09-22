<?php
include_once(__DIR__.'/include/config.php');
include_once(__DIR__ .'/include/HTML_TopBottom.php');
include_once($cfgGeneralIncludeDirectory.'class.phpPushover.php');
$db = connect_db();

$minUserLevel = 3;
$cfgProgDir = 'auth/';
include($cfgProgDir. "secure.php");

$String = $block = array();
$success = false;
	
if ($handle = opendir($offlineDir)) {
	while (false !== ($entry = readdir($handle))) {
		if ($entry != "." && $entry != "..") {
			$files[] = $entry;
		}
	}	
	closedir($handle);
}


$debug = 0;

# Doorloop alle offline-bestanden
foreach($files as $file) {
	# Alles initialiseren
	set_time_limit (30);	
	$String = $AdressenArray = array();
	$OpdrachtID = 0;
	$succes = $detail = $verkocht = $overzicht = false;
	
	$bestand = $offlineDir.$file;
		
	$fp = fopen($bestand, 'r+');
	$contents = fread($fp, filesize($bestand));
	fclose($fp);
	
	# Achterhalen wat voor een soort pagina het is
	# 	Overzichtspagina met te koop staande huizen
	# 	Overzichtspagina met verkochte huizen
	# 	Detail pagina van een individueel huis	
	
	# in de HTML-code staat altijd de bezochte URL
	# Die moeten wij zien te vinden
	$appHeaderLink	= getString('appheader-English-link" class="app-header__link" href="', '" hreflang=', $contents, 0);
	$pageURL		= getString('/koop/', '', urldecode($appHeaderLink[0]), 0);	
	$zoekURL		= '/koop/'.$pageURL[0];
		
	$OpdrachtID	= guessOpdrachtIDFromHTML($zoekURL);
	$fundaID		= guessFundaIDFromHTML($zoekURL);

	if($OpdrachtID > 0) {
		$overzicht = true;
		$detail = false;		
	}
	
	if($fundaID > 0) {
		$detail = true;
		$overzicht = false;
	}
	
	# Als in de zoekURL de tekst /verkocht/ voorkomt gaat het over een huis wat verkocht is
	# De variabele $verkocht is dan waar
	if(strpos($zoekURL, '/verkocht/')) {
		$verkocht		= true;
	} else {
		$verkocht		= false;
	}
		
	
	# 
	# De routine als geen van beide gevonden is
	#
	if(!$overzicht AND !$detail) {
		$String[] = "Het type pagina van <a href='$bestand'>$file</a> kan niet worden bepaald<br>";
	
	
	
	# 
	# De routine als het een overzichtspagina is
	#
	} elseif($overzicht) {
		$OpdrachtData			= getOpdrachtData($OpdrachtID);
		$PushMembers			= getMembers4Opdracht($OpdrachtID, 'push');
		
		$OpdrachtURL	= $OpdrachtData['url'];
		
		if($verkocht) {
			toLog('info', $OpdrachtID, '', 'Inladen pagina verkochte huizen voor '. $OpdrachtData['naam']);
		} else {
			toLog('info', $OpdrachtID, '', 'Inladen pagina voor '. $OpdrachtData['naam']);
		}
			
		# Code opknippen zodat er een array met HTML-code voor een huis ontstaat		
		$Huizen			= explode('<div class="search-result-media">', $contents);
		$Huizen			= array_slice($Huizen, 1);		
		$NrPageHuizen		= count($Huizen);
		
		if($debug == 1) {
			$block[] = "Aantal huizen in <a href='$bestand'>$file</a> : ". $NrPageHuizen ."<br>\n";
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
					$success = false;
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
				if(!hasOpenHuis($data['id'])) {
					toLog('info', $OpdrachtID, $data['id'], 'Open Huis aangekondigd');
					
					# Aanvinken om in een later stadium de details (met daarin de openhuis data) op te vragen
					mark4Details($data['id']);										
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
					
				if($debug == 0 AND !$verkocht) {
					sendPushoverNewHouse($data['id'], $OpdrachtID);
				}
			} elseif(changedPrice($data['id'], $data['prijs'], $OpdrachtID)) {
				sendPushoverChangedPrice($data['id'], $OpdrachtID);
			}
			
			if(!$verkocht) {
				addUpdateStreetDb($data['straat'], $data['plaats']);
			}
		}
	
		$String[] = "<a href='$bestand'>Overzicht</a>". ($verkocht ? ' met verkochte huizen ' : ' ')."van <a href='$OpdrachtURL'>". $OpdrachtData['naam'] ."</a>; ". count($AdressenArray)  ." huizen gevonden<br>";
		$success = true;
			
		toLog('debug', $OpdrachtID, '', "Einde pagina (". count($AdressenArray) ." huizen)");		
	
	
	
	
	# 
	# De routine als het een detailspagina is
	#
	} elseif($detail) {
		$allData = extractFundaDataFromPage($contents);
		$data = $allData[0];
		$extraData = $allData[1];
		
		if($fundaID != $data['id']) {
			$String[] = "Klopt dit wel ?";
			$success = false;
		} else {
			$String[] = "Details van <a href='". $ScriptURL ."admin/edit.php?id=$fundaID'>". $data['adres'] ."</a> ingelezen<br>\n";
			
			# Als wij een huis niet kennen klopt er iets niet
			if(!knownHouse($fundaID)) {
				toLog('error', '', $fundaID, 'Huis niet bekend');
				$success = false;
					
			# Meestal zal het huis wel bekend zijn
			} else {
				updateHouse($data, $extraData);
				addCoordinates($data['adres'], $data['PC_c'], $data['plaats'], $fundaID);
				updatePrice($fundaID, $data['prijs'], time());
				
				# Als hij nog niet verkocht is moeten wij dat aangeven
				if($data['verkocht'] != 1) {
					updateAvailability($fundaID);
					addUpdateStreetDb($data['straat'], $data['plaats']);
		
				# Als hij wel verkocht is moeten we de administratie daarvan even bijwerken
				} else {
					$temp = updateVerkochtDataFromPage($data, $extraData);
					$String[] = implode("<br>\n", $temp)."<br>\n";
				}
				
				# Hij heeft open huis, data invoegen in de database
				if($data['openhuis'] == 1) {
					$bestaandeTijden	= getNextOpenhuis($fundaID);
					$tijden = $data['oh-tijden'];
			
					if($bestaandeTijden[0] != '' AND ($tijden[0] != $bestaandeTijden[0] OR $tijden[1] != $bestaandeTijden[1])) {
						deleteOpenhuis($fundaID, $bestaandeTijden[0]);
						addOpenhuis($fundaID, $tijden);
						toLog('info', $OpdrachtID, $data['id'], 'Open Huis gewijzigd');
					} elseif($bestaandeTijden[0] == '') {
						addOpenhuis($fundaID, $tijden);
						toLog('info', $OpdrachtID, $data['id'], 'Open Huis toegevoegd');
					}
				}
				
				toLog('info', '', $fundaID, 'Offline pagina ingeladen');
				remove4Details($fundaID);
				$success = true;
			}
		}
	}
	
	# Alleen als de import succesvol is verlopen mag de pagina verwijderd worden
	if($success) {
		unlink($bestand);
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
	if($key > (count($block)/2 - 1) AND !$tweeKolom) {
		echo "</td><td width='50%' valign='top' align='center'>\n";
		$tweeKolom = true;
	}
}
echo "</td>\n";
echo "</tr>\n";
echo $HTMLFooter;
