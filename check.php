<?php
include_once(__DIR__.'/include/config.php');
include_once('include/HTML_TopBottom.php');
include_once($cfgGeneralIncludeDirectory.'class.phpPushover.php');
$db = connect_db();

# Omdat deze via een cronjob door de server wordt gedraaid is deze niet beveiligd
# Iedereen kan deze pagina dus in principe openen.
$straatRun = $wijkRun = $opdrachtRun = false;

# Als er een OpdrachtID is meegegeven hoeft alleen die uitgevoerd te worden.
# In alle andere gevallen gewoon alle actieve zoekopdrachten
if(isset($_REQUEST['OpdrachtID'])) {
	$Opdrachten = array($_REQUEST['OpdrachtID']);
	$opdrachtRun = true;
	$iMax = 1;
} elseif(date('i') > 2) {
	# Hoeveel straten zijn er te controleren
	$sql = "SELECT * FROM $TableStraten WHERE $StratenActive = '1'";
	$result = mysqli_query($db, $sql);
	$aantalStraten = mysqli_num_rows($result);
	
	# Hoeveel wijken zijn er te controleren
	$sql = "SELECT * FROM $TableWijken WHERE $WijkenActive = '1'";
	$result = mysqli_query($db, $sql);
	$aantalWijken = mysqli_num_rows($result);
	
	# Bepaal grens
	$grens = $aantalWijken/($aantalWijken+$aantalStraten)*100;
	
	# Beetje willekeurig verdelen of een straat of wijk wordt gecheckt
	$dice = rand (0, 100);
	if($dice < $grens) {
	    $checkStreets = false;
	} else {
	    $checkStreets = true;
	}
	
	$iMax = 1;  	
	if($checkStreets) {
		$straatRun = true;
		$Straten = getStreet2Check($iMax);
	} else {
		$wijkRun = true;
		$Wijken = getWijk2Check($iMax);
	}	
} else {
	$Opdrachten = getZoekOpdrachten('', date('G'));
	$opdrachtRun = true;
	$iMax = count($Opdrachten);
}

$block = array();

# Doorloop alle zoekopdrachten
for($i=0 ; $i < $iMax ; $i++) {
	# Alles initialiseren		
	$knownHouses = 0;
	
	if($opdrachtRun) {
		$OpdrachtID			= $Opdrachten[$i];
		$OpdrachtData		= getOpdrachtData($OpdrachtID);
		
		toLog('info', $OpdrachtID, '', 'Start controle '. $OpdrachtData['naam']);
	} elseif($straatRun) {
		$straatID = $Straten[$i];
		$straatData = getStreetByID($straatID);
		$OpdrachtData['url'] = 'http://www.funda.nl/koop/'.convert2FundaStyle($straatData['plaats']) ."/straat-". $straatData['straat'] ."/";
	} else {
		$wijkID = $Wijken[$i];
		$wijkData = getWijkByID($wijkID);
		$OpdrachtData['url'] = 'http://www.funda.nl/koop/'.convert2FundaStyle($wijkData['plaats']) ."/". $wijkData['wijk'] ."/";
	}
	
	$OpdrachtURL	= "http://partnerapi.funda.nl/feeds/Aanbod.svc/rss/?type=koop&zo=". getSearchString($OpdrachtData['url'], true);
	$content			= file_get_contents_retry($OpdrachtURL);

	if($opdrachtRun) {
		$String[] = "<a href='$OpdrachtURL'>RSS</a> -> <a href='". $OpdrachtData['url'] ."'>". $OpdrachtData['naam'] ."</a>";
		toLog('debug', $OpdrachtID, '', $OpdrachtURL);
	} elseif($straatRun) {
		$String[] = "<a href='$OpdrachtURL'>RSS</a> -> <a href='". $OpdrachtData['url'] ."'>". $straatData['leesbaar'] ."</a> (". $straatData['plaats'] .")";
		toLog('debug', '', '', $OpdrachtURL);
	} else {
		$String[] = "<a href='$OpdrachtURL'>RSS</a> -> <a href='". $OpdrachtData['url'] ."'>". $wijkData['leesbaar'] ."</a> (". $wijkData['plaats'] .")";
		toLog('debug', '', '', $OpdrachtURL);
	}
		
	$Huizen = explode('<item>', $content);
	array_shift($Huizen);
			
	foreach($Huizen as $huis) {
		$data			= RSS2Array($huis);
		$fundaID	= $data['id'];
				
		$String[] = formatPrice($data['prijs']) ." : <a href='". $data['link'] ."'>". $data['adres'] ."</a> ($fundaID)";
				
		if($straatRun OR $wijkRun) {
			$opdrachten = getOpdrachtenByFundaID($fundaID);
			$OpdrachtID = $opdrachten[0];
			$OpdrachtData		= getOpdrachtData($OpdrachtID);
		}
		
		# Huis nog niet bekend in systeem
		# Maar alleen bij opdrachtRun (dus niet bij straten)
		if(!knownHouse($fundaID) AND $opdrachtRun) {
			
			# Gegevens over het huis opslaan
			if(!saveHouseRSS($data)) {
				$ErrorMessage[] = "Toevoegen van ". $data['adres'] ." aan het script ging niet goed";
				toLog('error', $OpdrachtID, $fundaID, $data['adres'] .' toevoegen aan script mislukt');
			} else {					
				toLog('info', $OpdrachtID, $fundaID,  $data['adres'] .' toegevoegd aan script');
			}
			
			# Coordinaten van het huis toevoegen
			if(!addCoordinates($data['adres'], '', $data['plaats'], $fundaID)) {					
				$ErrorMessage[] = "Toevoegen van coordinaten aan ". $data['adres'] ." ging niet goed";	
				toLog('error', $OpdrachtID, $data['id'], 'Coordinaten toevoegen mislukt');
			} else {
				toLog('debug', $OpdrachtID, $data['id'], 'Coordinaten toegevoegd');
			}
				
			# Prijs van het huis opslaan
			if(!updatePrice($fundaID, $data['prijs'])) {
				$ErrorMessage[] = "Toevoegen van prijs (". $data['prijs'] .") aan ". $data['adres'] ." ging niet goed";
				toLog('error', $OpdrachtID, $fundaID, 'Prijs toevoegen mislukt');
			} else {
				toLog('debug', $OpdrachtID, $fundaID, 'Prijs ('. $data['prijs'] .') toegevoegd');
			}
			
			# Aanvinken om in een later stadium de details op te vragen
			mark4Details($fundaID);			
			
		} elseif(knownHouse($fundaID)) {
			# Huis is al bekend bij het script
			# We moeten dus aangeven dat hij nog steeds op de markt is
			if(!updateAvailability($fundaID, $data['begin'])) {
				echo "<font color='red'>Updaten van <b>". $data['adres'] ."</b> is mislukt</font> | $sql<br>\n";
				$ErrorMessage[] = "Updaten van ". $data['adres'] ." is mislukt";
				toLog('error', $OpdrachtID, $fundaID, "Update van huis kon niet worden gedaan");
			} else {
				toLog('debug', $OpdrachtID, $fundaID, 'Huis geupdate');
			}

			# Huis kan gedaald zijn in prijs
			# Dat moeten we dus controleren en indien nodig opslaan en melding van maken
			if(newPrice($fundaID, $data['prijs'])) {							
				if(!updatePrice($fundaID, $data['prijs'])) {
					echo "Toevoegen van de prijs van <b>". $data['adres'] ."</b> is mislukt | $sql<br>\n";
					$ErrorMessage[] = "Updaten van prijs (". $data['prijs'] .") aan ". $data['adres'] ." ging niet goed";
					toLog('error', $OpdrachtID, $fundaID, 'Nieuwe prijs van '. $data['prijs'] .' kon niet worden toegevoegd');
				} else {
					toLog('debug', $OpdrachtID, $fundaID, 'Nieuwe vraagprijs ('. $data['prijs'] .')');
				}
			}
			$knownHouses++;
		}
		
		if(newHouse($fundaID, $OpdrachtID) AND $opdrachtRun) {
			if(!addHouse($data, $OpdrachtID)) {
				$ErrorMessage[] = "Toevoegen van ". $data['adres'] ." aan opdracht $OpdrachtID ging niet goed";
				toLog('error', $OpdrachtID, $fundaID, 'Huis toekennen aan opdracht mislukt');
			} else {
				toLog('debug', $OpdrachtID, $fundaID, 'Huis toegekend aan opdracht');
			}
			
			# Pushover-bericht opstellen
			sendPushoverNewHouse($fundaID, $OpdrachtID);
		}
		
		# Bij een straatopdracht even opzoeken welke opdrachten daarbij horen
		if($straatRun OR $wijkRun) {
		    $opdrachtArray = getOpdrachtenByFundaID($fundaID);
		
		    
		# Bij een 'echte' opdracht indien nodig de straat + plaats toevoegen aan straten-lijst
		# En de array met opdrachten gelijkstellen aan de opdrachtID
		} else {
		    addUpdateStreetDb($data['straat'], $data['plaats']);
		    $opdrachtArray = array($OpdrachtID);
		}
		
		# Doorloop alle opdrachten om te kijken of er een push-melding uit moet
		foreach($opdrachtArray as $OpdrachtID) {
			if(knownHouse($fundaID) AND changedPrice($fundaID, $data['prijs'], $OpdrachtID) AND is_numeric($data['prijs']) AND $data['prijs'] > 0) {
				sendPushoverChangedPrice($fundaID, $OpdrachtID);
			}
		}
	}
	
	$block[] = implode("<br>\n", $String);
	$String = array();
		
	if($straatRun) {
		if($knownHouses > 0) {
			setStreetSeen($straatID);
			toLog('info', '', '', $straatData['leesbaar'].' in '.$straatData['plaats']." [$knownHouses/".count($Huizen).']');
		} else {
			inactivateStreet($straatID); 
			toLog('info', '', '', $straatData['leesbaar'].' in '.$straatData['plaats'].' niet meer actief');
		}
	}
		
	if($wijkRun) {
		if($knownHouses > 0) {
			setWijkSeen($wijkID);
			toLog('info', '', '', 'De wijk '. $wijkData['leesbaar'].' in '.$wijkData['plaats']." [$knownHouses/".count($Huizen).']');
		} else {
			inactivateWijk($wijkID); 
			toLog('info', '', '', 'De wijk '. $wijkData['leesbaar'].' in '.$wijkData['plaats'].' niet meer actief');
		}
	}	
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

?>
