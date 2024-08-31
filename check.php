<?php
include_once(__DIR__.'/include/config.php');
include_once('include/HTML_TopBottom.php');
include_once($cfgGeneralIncludeDirectory.'class.phpmailer.php');
include_once($cfgGeneralIncludeDirectory.'class.html2text.php');
include_once($cfgGeneralIncludeDirectory.'class.phpPushover.php');
$db = connect_db();

# Omdat deze via een cronjob door de server wordt gedraaid is deze niet beveiligd
# Iedereen kan deze pagina dus in principe openen.

# http://stackoverflow.com/questions/9049460/cron-jobs-and-random-times-within-given-hours/16289693
# Om te zorgen dat de pagina op wisselende tijden wordt geopend kan je de volgende cronjob opnemen :
#		sleep $[RANDOM\%3660] ; wget -q -O /dev/null http://example.com/funda/check.php

# Alles initialiseren
set_time_limit (90);
$ErrorMessage = array();
$NewHouses = $NewAddress = array();
$String = $block = $AdressenArray = array();

# 0 = geen debug
# 1 = korte debug (alleen adressen)
# 2 = uitgebreidere debug (items)
# 3 = uitgebreidste debug (ruwe tekst)
$debug = 0;

$storeFile = false;

# Om bij te houden welke pagina van welke opdracht geopend moet worden, kijk ik in de database
# Aan het eind van dit script, schijf ik namelijk weg welke pagina volgende keer geopend moet worden.
$nextData = getPageToLoadNext();
$OpdrachtID		= $nextData['opdracht'];
$page					= $nextData['page'];
$OpdrachtURL	= $nextData['url_opdracht'];
$PageURL			= $nextData['url_open'];
$verkocht			= $nextData['verkocht'];

$OpdrachtData			= getOpdrachtData($OpdrachtID);
$PushMembers			= getMembers4Opdracht($OpdrachtID, 'push');

if($verkocht) {
	toLog('info', $OpdrachtID, '', 'Start controle pagina '. $page .' van verkochte huizen voor '. $OpdrachtData['naam']);
} else {
	toLog('info', $OpdrachtID, '', 'Start controle pagina '. $page .' voor '. $OpdrachtData['naam']);
}	

$debug_filename = 'funda_'. $OpdrachtID .'_'. $page .($verkocht ? '_sold' : '') .'.htm';

# In debug-modus, sla pagina voor later op
if($debug == 0 OR (!file_exists($debug_filename) AND $debug > 0) OR $storeFile) {
	# Vraag de pagina op en herhaal dit het standaard aantal keer mocht het niet lukken
	$contents	= file_get_contents_retry($PageURL);
	
	if($debug > 0 OR $storeFile) {
		$fp = fopen($debug_filename, 'w');
		fwrite($fp, $contents);
		fclose($fp);
	}			
} elseif($debug > 0) {			
	$fp = fopen($debug_filename, 'r+');
	$contents = fread($fp, filesize($debug_filename));
	fclose($fp);
}
	
if(is_numeric(strpos($contents, '<button tabIndex="0" class="css-9pm8wv">Next</button>'))) {		
	$nextPage = true;
} else {
	$nextPage = false;
}

# Code opknippen zodat er een array met HTML-code voor een huis ontstaat
$tempHuizen			= explode('data-test-id="object-image-link"', $contents);
#$tempHuizen			= explode('<div class="ml-auto" data-v-058abe0b>', $contents);

# Eerste element is rubbish
$Huizen			= array_slice($tempHuizen, 1);

# $Huizen is nu een array met per huis de HTML-code
$NrPageHuizen		= count($Huizen);

if($debug > 0) {
	$block[] = "Aantal ". ($verkocht ? 'verkochte ' : '') ."huizen in <a href='$debug_filename'>pagina $page</a> van <a href='$PageURL'>". $OpdrachtData['naam'] ."</a> : ". $NrPageHuizen ."<br>\n";
}

# Doorloop nu alle gevonden huizen op de overzichtspagina
foreach($Huizen as $HuisText) {	
	# Extraheer hier adres, plaats, prijs, id etc. uit
	$data = extractFundaData($HuisText, $verkocht);
									
	$AdressenArray[] = $data['adres'];
							
	if($debug > 1) {
		if($debug > 2) {
			$block[] = $HuisText;
		}
		
		$tempItems = array();
		foreach($data as $key => $value) {
			$tempItems[] = $key .' -> '. $value;
		}
		$block[] = implode('<br>', $tempItems);		
	}
	
	# Huis is nog niet bekend bij het script, dus moet worden toegevoegd
	if(!knownHouse($data['id'])) {
		if($debug > 1)	$block[] = $data['id']." onbekend -> toegevoegd";
		
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
		# Mocht hij wel bekend zijn, dan zetten wij hem op online
		# Dit voor het geval die om wat voor een reden dan ook een keer op offline is gezet
		setOnline($data['id']);
	}

	# Huis is niet verkocht	
	if(!$verkocht) {				
		# We moeten dus aangeven dat hij nog steeds op de markt is
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
			if($debug > 1)	$block[] = "Nieuwe vraagprijs";
		}
			
		# Huis kan onder voorbehoud verkocht zijn
		if($data['vov'] > 0) {
			if(!soldHouseTentative($data['id'])) {
				$sql = "UPDATE $TableHuizen SET $HuizenVerkocht = '2' WHERE $HuizenID like '". $data['id'] ."' OR $HuizenID2 like '". $data['id'] ."'";
				mysqli_query($db, $sql);
				toLog('info', $OpdrachtID, $data['id'], 'Onder voorbehoud verkocht');
			}			
		
		# Het geval dat onder voorbehoud wordt teruggedraaid
		} elseif(soldHouseTentative($data['id']) AND $data['vov'] == 0) {
			$sql = "UPDATE $TableHuizen SET $HuizenVerkocht = '0' WHERE $HuizenID like '". $data['id'] ."' OR $HuizenID2 like '". $data['id'] ."'";
			mysqli_query($db, $sql);
			toLog('info', $OpdrachtID, $data['id'], 'Niet meer onder voorbehoud verkocht');
		}
		
		# Huis kan onder bod of onder optie zijn
		if($data['optie'] > 0) {			
			if(!soldHouseOption($data['id'])) {
				$sql = "UPDATE $TableHuizen SET $HuizenVerkocht = '3' WHERE $HuizenID like '". $data['id'] ."' OR $HuizenID2 like '". $data['id'] ."'";
				mysqli_query($db, $sql);
				toLog('info', $OpdrachtID, $data['id'], 'Onder bod');
			}			
		# Het geval dat onder voorbehoud wordt teruggedraaid
		} elseif(soldHouseOption($data['id']) AND $data['optie'] == 0) {
			$sql = "UPDATE $TableHuizen SET $HuizenVerkocht = '0' WHERE $HuizenID like '". $data['id'] ."' OR $HuizenID2 like '". $data['id'] ."'";
			mysqli_query($db, $sql);
			toLog('info', $OpdrachtID, $data['id'], 'Niet meer onder bos');
		}		
		
	}
	
	# Huis kan ook echt verkocht zijn
	if($data['verkocht'] == 1) {
		toLog('debug', $OpdrachtID, $data['id'], 'Verkocht huis geupdate');
		
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
	
		
	# Huis kan openhuis hebben
	if($data['openhuis'] == 1) {
		if(!hasOpenHuis($data['id'])) {
			setOpenHuis($data['id']);
			toLog('info', $OpdrachtID, $data['id'], 'Open Huis aangekondigd');
			
			# Aanvinken om in een later stadium de details (met daarin de openhuis data) op te vragen
			mark4Details($data['id']);
			
			# Een open huis kan alleen als het nog niet verkocht is -> pushover-bericht versturen			
			if($debug == 0 AND !$verkocht) {
				sendPushoverOpenHuis($data['id'], $OpdrachtID);
				if($debug > 1)	$block[] = 'Pushover-bericht open huis';
			}
		}
	} else {
		removeOpenHuis($data['id']);
	}	
		
				
	# Kijk of dit huis al vaker gevonden is voor deze opdracht
	if(newHouse($data['id'], $OpdrachtID)) {				
		if(!addHouse($data, $OpdrachtID)) {
			$ErrorMessage[] = "Toevoegen van ". formatStreetAndNumber($data['id']) ." aan opdracht $OpdrachtID ging niet goed";
			toLog('error', $OpdrachtID, $data['id'], 'Huis toekennen aan opdracht mislukt');
		} else {
			toLog('debug', $OpdrachtID, $data['id'], 'Huis toegekend aan opdracht');
		}
  
		$NewAddress[] = $data['adres'];
		
		# Nieuw huis, niet verkocht + niet aan het testen -> pushover-bericht versturen
		if($debug == 0 AND !$verkocht) {
			sendPushoverNewHouse($data['id'], $OpdrachtID);
			if($debug > 1)	$block[] = 'Pushover-bericht nieuw huis voor opdracht';
		}
	} elseif(changedPrice($data['id'], $data['prijs'], $OpdrachtID)) {
		# Prijsverlaging + niet aan het testen -> pushover-bericht versturen
		if($debug == 0 AND !$verkocht) {
			sendPushoverChangedPrice($data['id'], $OpdrachtID);
			if($debug > 1)	$block[] = 'Pushover-bericht prijsverlaging';
		}
	}
}

$String = array('');
$String[] = "<a href='$PageURL'>Pagina $page</a> voor ". $OpdrachtData['naam'] ." verwerkt en ". count($AdressenArray) ." huizen gevonden :<br>";
$String[] = '<ol>';
foreach($AdressenArray as $key => $value) {
	$String[] = "<li>$value</li>";
}
$String[] = '</ol>';

$block[] = implode("\n", $String);

if($verkocht) {
	toLog('debug', $OpdrachtID, '', "Einde verkochte pagina $page (". count($AdressenArray) ." huizen)");
} else {
	toLog('debug', $OpdrachtID, '', "Einde pagina $page (". count($AdressenArray) ." huizen)");
}

setPageToLoadNext($OpdrachtID, $page, $verkocht, $nextPage);

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

# Als er een error-meldingen zijn gegenereerd in het script moet er een mail de deur uit.
# Natuurlijk alleen als we niet aan het debuggen zijn
if(count($ErrorMessage) > 0 AND $debug == 0) {	
	include('include/HTML_TopBottom.php');
	$HTMLMail = $HTMLHeader;
	$HTMLMail .= showBlock(implode("<br>", $ErrorMessage));
	$HTMLMail .= $HTMLFooter;
	
	$mail = new PHPMailer;
	$mail->From     = $ScriptMailAdress;
	$mail->FromName = $ScriptTitle;
	$mail->AddAddress($ScriptMailAdress, 'Matthijs');
	$mail->Subject	= $SubjectPrefix."problemen met ".$ScriptTitle;
	$mail->IsHTML(true);
	$mail->Body			= $HTMLMail;
	$mail->Send();	
}

?>