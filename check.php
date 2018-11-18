<?php
include_once(__DIR__.'/include/config.php');
include_once('include/HTML_TopBottom.php');
include_once($cfgGeneralIncludeDirectory.'class.phpPushover.php');
connect_db();

$straatRun = $opdrachtRun = false;

# Omdat deze via een cronjob door de server wordt gedraaid is deze niet beveiligd
# Iedereen kan deze pagina dus in principe openen.

# http://stackoverflow.com/questions/9049460/cron-jobs-and-random-times-within-given-hours/16289693
# Om te zorgen dat de pagina op wisselende tijden wordt geopend heb ik de volgende cronjob opgenomen :
#		sleep $[RANDOM\%3660] ; wget -q -O /dev/null http://example.com/funda/check.php

# Als er een OpdrachtID is meegegeven hoeft alleen die uitgevoerd te worden.
# In alle andere gevallen gewoon alle actieve zoekopdrachten
if(isset($_REQUEST['OpdrachtID'])) {
	$Opdrachten = array($_REQUEST['OpdrachtID']);
	$opdrachtRun = true;
	$iMax = 1;
} elseif(date('i') > 5) {
	$straatRun = true;
	$iMax = 1;
	$Straten = getStreet2Check($iMax);
} else {
	$Opdrachten = getZoekOpdrachten('', date('G'));
	$opdrachtRun = true;
	$iMax = count($Opdrachten);
}

$block = array();

# Doorloop alle zoekopdrachten
for($i=0 ; $i < $iMax ; $i++) {
	# Alles initialiseren
	if($opdrachtRun) {
		$OpdrachtID			= $Opdrachten[$i];
		$OpdrachtData		= getOpdrachtData($OpdrachtID);
		
		toLog('info', $OpdrachtID, '', 'Start controle '. $OpdrachtData['naam']);
	} else {		
		$straatID = $Straten[$i];
		$straatData = getStreetByID($straatID);
		$OpdrachtData['url'] = 'http://www.funda.nl/koop/'.convert2FundaStyle($straatData['plaats']) ."/straat-". $straatData['straat'] ."/";
		//toLog('info', '', '', 'Start controle '. $straatData['leesbaar'] .' in '. $straatData['plaats']);
	}
	
	$OpdrachtURL	= "http://partnerapi.funda.nl/feeds/Aanbod.svc/rss/?type=koop&zo=". str_replace ("http://www.funda.nl/koop", "", $OpdrachtData['url']);
	$content			= file_get_contents_retry($OpdrachtURL);

	if($opdrachtRun) {
		$String[] = "<a href='$OpdrachtURL'>RSS</a> -> <a href='". $OpdrachtData['url'] ."'>". $OpdrachtData['naam'] ."</a>";
		toLog('debug', $OpdrachtID, '', $OpdrachtURL);
	} else {
		$String[] = "<a href='$OpdrachtURL'>RSS</a> -> <a href='". $OpdrachtData['url'] ."'>". $straatData['leesbaar'] ."</a> (". $straatData['plaats'] .")";
		toLog('debug', '', '', $OpdrachtURL);
	}
		
	$Huizen = explode('<item>', $content);
	array_shift($Huizen);
			
	foreach($Huizen as $huis) {
		$data			= RSS2Array($huis);
		$fundaID	= $data['id'];
				
		$String[] = "<a href='". $data['link'] ."'>". $data['adres'] ."</a> ($fundaID)";
		
		if($straatRun) {
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
				toLog('error', $OpdrachtID, $fundaID, 'Huis toevoegen aan script mislukt');
			} else {					
				toLog('info', $OpdrachtID, $fundaID, 'Huis toevoegen aan script');
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
		} elseif(changedPrice($fundaID, $data['prijs'], $OpdrachtID) AND $opdrachtRun) sendPushoverChangedPrice($fundaID, $OpdrachtID);
		if($opdrachtRun)	addUpdateStreetDb($data['straat'], $data['plaats']);
	}
	
	$block[] = implode("<br>\n", $String);
	$String = array();
		
	if($straatRun) {
		$sql_update = "UPDATE $TableStraten SET ". ($knownHouses > 0 ? "$StratenLastCheck = '". time() ."'" : "$StratenActive = '0'") ." WHERE $StratenID = ". $straatID;
		mysql_query($sql_update);
		toLog('debug', '', '', $sql_update);
		toLog('info', '', '', $straatData['leesbaar'] .' in '. $straatData['plaats'] . "; $knownHouses ". ($knownHouses == 1 ? 'huis' : 'huizen') ." van ". count($Huizen));
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
