<?php
include_once(__DIR__.'/include/config.php');
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

if(count($files) > 10) {
	$files			= array_slice($files, 0, 10);
	$userInteraction = false;
}

include_once(__DIR__ .'/include/HTML_TopBottom.php');

# Doorloop alle offline-bestanden
foreach($files as $file) {
	# Alles initialiseren
	set_time_limit (30);	
	$String = $Huizen = $AdressenArray = array();
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
	if(strpos($contents, '"url":"')) {
	    $appHeaderLink	= getString('"url":"', '",', $contents, 0);
	} else {	    
	    $appHeaderLink	= getString('<link rel="canonical" href="', '"', $contents, 0);	    
	}
	$pageURL		= str_replace('/koop?', '/koop/?', $appHeaderLink[0]);
	
	if(strpos($contents, '/koop/?')) {
		$pageURL		= getString('/koop/?', '', $pageURL, 0);	
		$zoekURL		= 'https://www.funda.nl/zoeken/koop/?'.$pageURL[0];
	} else {
		$zoekURL		= $pageURL;
	}
			
	$OpdrachtID	= guessOpdrachtIDFromHTML($zoekURL);
	$fundaID		= guessFundaIDFromHTML($zoekURL);

	#$OpdrachtID = 3;
	#echo $file .' -> '. $zoekURL .' -> '. $OpdrachtID ."<br>\n";
	#echo $file .' -> '. $zoekURL .' -> '. $fundaID ."<br>\n";

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
	if(strpos($zoekURL, '&availability=%5B%22unavailable%22%5D')) {
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
			toLog('info', $OpdrachtID, '0', 'Inladen pagina verkochte huizen voor '. $OpdrachtData['naam']);
		} else {
			toLog('info', $OpdrachtID, '0', 'Inladen pagina voor '. $OpdrachtData['naam']);
		}
			
		# Code opknippen zodat er een array met HTML-code voor een huis ontstaat
		# De eerste keer voor "normale" huizen
		$tempHuizen			= explode('data-test-id="object-image-link"', $contents);
								
				
		# Eerste element is rubbish
		$Huizen			= array_slice($tempHuizen, 1);
		
		# $Huizen is nu een array met per huis de HTML-code
		$NrPageHuizen		= count($Huizen);
		
		if($debug > 0) {
			$block[] = "Aantal huizen in <a href='$bestand'>$file</a> : ". $NrPageHuizen ."<br>\n";
		}
		
		# Doorloop nu alle gevonden huizen op de overzichtspagina
		foreach($Huizen as $HuisText) {
			# Extraheer hier adres, plaats, prijs, id etc. uit
			$data = extractFundaData($HuisText, $verkocht);
									
			$AdressenArray[] = $data['adres'];
							
			if($debug == 2) {
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
					$ErrorMessage[] = "Toevoegen van ". formatStreetAndNumber($data['id']) ." aan het script ging niet goed";
					toLog('error', $OpdrachtID, $data['id'], 'Huis toevoegen aan script mislukt');
					$success = false;
				} else {					
					toLog('info', $OpdrachtID, $data['id'], 'Huis toevoegen aan script');
				}
					
				# Coordinaten van het huis toevoegen
				if(!addCoordinates($data['adres'], $data['PC_c'], $data['plaats'], $data['id'])) {					
					$ErrorMessage[] = "Toevoegen van coordinaten aan ". formatStreetAndNumber($data['id']) ." ging niet goed";	
					toLog('error', $OpdrachtID, $data['id'], 'Coordinaten toevoegen mislukt');
				} else {
					toLog('debug', $OpdrachtID, $data['id'], "Coordinaten toegevoegd");
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
			} else {
				# Pagina is nog steeds beschikbaar
				setOnline($data['id']);
			}
			
			# Huis is al bekend bij het script
			# We moeten dus aangeven dat hij nog steeds op de markt is
			if(!$verkocht) {				
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
			}
			
			# Huis kan ook echt verkocht zijn
			if($data['verkocht'] == 1) {
				if(!soldHouse($data['id'])) {
					$sql = "UPDATE $TableHuizen SET $HuizenVerkocht = '1' WHERE $HuizenID like '". $data['id'] ."' OR $HuizenID2 like '". $data['id'] ."'";
					mysqli_query($db, $sql);
					toLog('info', $OpdrachtID, $data['id'], 'Verkocht');
					
					# Aanvinken om in een later stadium de details op te vragen
					mark4Details($data['id']);
				}
			# Het geval dat verkocht wordt teruggedraaid (hypothetisch)
			} elseif(soldHouse($data['id'])) {
				$sql = "UPDATE $TableHuizen SET $HuizenVerkocht = '0' WHERE $HuizenID like '". $data['id'] ."' OR $HuizenID2 like '". $data['id'] ."'";
				mysqli_query($db, $sql);
				toLog('info', $OpdrachtID, $data['id'], 'Toch niet meer verkocht');
			}
					
		#	# Huis kan openhuis hebben
		#	if($data['openhuis'] == 1) {
		#		if(!hasOpenHuis($data['id'])) {
		#			toLog('info', $OpdrachtID, $data['id'], 'Open Huis aangekondigd');
		#			
		#			# Aanvinken om in een later stadium de details (met daarin de openhuis data) op te vragen
		#			mark4Details($data['id']);										
		#		}
		#	} else {
		#		removeOpenHuis($data['id']);
		#	}	
				
						
			# Kijk of dit huis al vaker gevonden is voor deze opdracht
			if(newHouse($data['id'], $OpdrachtID)) {				
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
			} elseif(changedPrice($data['id'], $data['prijs'], $OpdrachtID)) {
				sendPushoverChangedPrice($data['id'], $OpdrachtID);
			}
			
		#	if(!$verkocht) {
		#		addUpdateStreetDb($data['straat'], $data['plaats']);
		#	}
		}
	  
		$String[] = "<a href='$bestand'>Overzicht</a>". ($verkocht ? ' met verkochte huizen ' : ' ')."van <a href='$OpdrachtURL'>". $OpdrachtData['naam'] ."</a>; ". count($AdressenArray)  ." huizen gevonden<br>";
					
		#toLog('debug', $OpdrachtID, '0', "Einde pagina (". count($AdressenArray) ." huizen)");		
	  
		if($debug == 1) {
			$block[] = implode("<br>", $AdressenArray)."\n";
		}
		
		$success = true;
	
	
	
	# 
	# De routine als het een detailspagina is
	#
		} elseif($detail) {
		$allData = extractFundaDataFromPage($contents);
		$data = $allData[0];
		$extraData = $allData[1];
		
		#var_dump($extraData);
		
		if($fundaID != $data['id']) {
			$String[] = "Klopt dit wel ?";
			$success = false;
		} else {						
			# Als wij een huis niet kennen klopt er iets niet
			if(!knownHouse($fundaID)) {
				toLog('error', '0', $fundaID, 'Huis niet bekend');				
											
				#addHouse($data, $id)
				if(saveHouse($data, $extraData)) {
					$String[] = "<a href='". $ScriptURL ."admin/edit.php?id=$fundaID'>". formatStreetAndNumber($data['id']) ."</a> blijkt nog niet te bestaan, daarom toegevoegd<br>\n";
															
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
					$String[] = "<a href='". $ScriptURL ."admin/edit.php?id=$fundaID'>". formatStreetAndNumber($data['id']) ."</a> bleek nog niet te bestaan, maar kon niet toegevoegd worden<br>\n";
					$success = false;
				}				
					
			# Meestal zal het huis wel bekend zijn
			} else {
				$String[] = "Details van <a href='". $ScriptURL ."admin/edit.php?id=$fundaID'>". formatStreetAndNumber($data['id']) ."</a> ingelezen<br>\n";
				
				$oldData = getFundaData($fundaID);
				
				updateHouse($data, $extraData);
				//addCoordinates($data['adres'], $data['PC_c'], $data['plaats'], $fundaID);
				updatePrice($fundaID, $data['prijs'], time());
				
				# Als hij nog niet verkocht is moeten wij dat aangeven
				if($data['verkocht'] != 1) {
					if($oldData['start'] > $data['start']) {
						updateAvailability($fundaID, $data['start']);
					} else {
						updateAvailability($fundaID);
					}
					addUpdateStreetDb($data['straat'], $data['plaats']);
					addUpdateWijkDb($data['wijk'], $data['plaats']);
		
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
	if($key >= (count($block)/2 - 1) AND !$tweeKolom) {
		echo "</td><td width='50%' valign='top' align='center'>\n";
		$tweeKolom = true;
	}
}
echo "</td>\n";
echo "</tr>\n";
