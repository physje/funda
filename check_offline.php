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

$opdrachten = getZoekOpdrachten($_SESSION['account'], '', false);

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
	$pageURL		= getString('/koop/', '', $appHeaderLink[0], 0);	
	$zoekURL		= '/koop/'.$pageURL[0];
	
	# Als in de zoekURL de tekst /verkocht/ voorkomt gaat het over een huis wat verkocht is
	# De variabele $verkocht is dan waar
	if(strpos($zoekURL, '/verkocht/')) {
		$verkocht		= true;
	} else {
	    $verkocht		= false;
	}
	
	# Aantal filters in de URL hebben 'geen' waarde en mogen er dus uit
	$cleanZoekString = str_replace('/verkocht/', '/', $zoekURL);
	$cleanZoekString = str_replace('/sorteer-afmelddatum-af/', '/', $cleanZoekString);
	
	# Door hem op te knippen zien wij welke filters er actief zijn
	$filtersZoeken = explode('/', $cleanZoekString);	
	
	# Doorloop alle opdrachten op zoek naar een match met de filters
	foreach($opdrachten as $opdracht) {
		$opdrachtData = getOpdrachtData($opdracht);
		$cleanOpdrachtURL = str_replace('http://www.funda.nl', '', $opdrachtData['url']);
		$filtersOpdracht = explode('/', $cleanOpdrachtURL);
		
		# Wij gaan gevonden filters verwijderen dus maken even een kopie van het orgineel
		$kopieFiltersZoeken = $filtersZoeken;
		
		# Wij lopen 2x de filters door
		# 1x kijken wij welke filters uit de HTML-pagina voorkomen in de zoekopdracht
		# 1x kijken wij welke filters uit de zoekopdracht voorkomen in de HTML-pagin
		# Die combi die op beide het beste scoort is met redelijke zekerheid de zoekopdracht
		foreach($filtersOpdracht as $key => $value) {
			if(in_array($value, $kopieFiltersZoeken)) {
				$index = array_search ($value, $kopieFiltersZoeken);
				unset($kopieFiltersZoeken[$index]);
			}				
		}
		
		foreach($filtersZoeken as $key => $value) {
			if(in_array($value, $filtersOpdracht)) {
				$index = array_search ($value, $filtersOpdracht);
				unset($filtersOpdracht[$index]);
			}
		}
		
		//echo $opdracht .' -> '. count($kopieFiltersZoeken) .'|'. count($filtersOpdracht) .' = '. (count($kopieFiltersZoeken)+count($filtersOpdracht)) .'<br>';
		//echo implode('|', $kopieFiltersZoeken) .' niet gevonden<br>';
		//echo '<br>';
		
		$score_zoek[$opdracht] = count($kopieFiltersZoeken);
		$score_opdracht[$opdracht] = count($filtersOpdracht);
		$score_tot[$opdracht] = count($kopieFiltersZoeken)+count($filtersOpdracht);
	}
	
	# Als de laagste totale score 0 of 1 is, is het aannemelijk dat we een match hebben
	if(min($score_tot) < 2) {
		$OpdrachtID = array_search (min($score_tot), $score_tot);
		$overzicht = true;
	} else {
		$overzicht = false;
	}

	
	# Het lijkt geen overzichtspagina te zijn
	# Onderzoek de optie detailpagina
	if(!$overzicht) {
		$mappen = explode("/", $zoekURL);
		 
		if($verkocht) {
			$delen 		= explode("-", $mappen[4]);
		} else {
			$delen 		= explode("-", $mappen[3]);
		}
		
		$fundaID	= $delen[1];
		
		if(is_numeric($fundaID) AND count($mappen) > 4 AND count($delen) > 3) {
			$detail = true;
		}
	}	
	
	/*
	if($verkocht) {
		if($overzicht) {
			$String[] = $file .' -> VERKOCHT | '. $OpdrachtID .'<br>';
		} else {
			$String[] = $file .' -> VERKOCHT | '. $fundaID .'<br>';
		}
	} else {
		if($overzicht) {
			$String[] = $file .' -> '. $OpdrachtID .'<br>';
		} else {
			$String[] = $file .' -> '. $fundaID .'<br>';
		}
	}
	*/
	
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
				updateHouse($data, $allData[1]);
				addCoordinates($data['adres'], $data['PC_c'], $data['plaats'], $fundaID);
				updatePrice($fundaID, $data['prijs'], time());
				
				# Als hij nog niet verkocht is moeten wij dat aangeven
				if($data['verkocht'] != 1) {
					updateAvailability($fundaID);
					addUpdateStreetDb($data['straat'], $data['plaats']);
		
				# Als hij wel verkocht is moeten we de administratie daarvan even bijwerken
				} else {
					$temp = updateVerkochtDataFromPage($data, $allData[1]);
					$String[] = implode("<br>\n", $temp)."<br>\n";
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
