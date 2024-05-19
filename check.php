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
# Om te zorgen dat de pagina op wisselende tijden wordt geopend heb ik de volgende cronjob opgenomen :
#		sleep $[RANDOM\%3660] ; wget -q -O /dev/null http://example.com/funda/check.php

# Om bij te houden welke pagina van welke opdracht geopend moet worden, kijk ik in de database
# Aan het eind van dit script, schijf ik namelijk weg welke pagina volgende keer geopend moet worden.

# Alles initialiseren
set_time_limit (90);
$NewHouses = $NewAddress = array();
$String = $block = $AdressenArray = array();

# 0 = geen debug
# 1 = korte debug (alleen adressen)
# 2 = uitgebreidere debug (items)
# 3 = uitgebreidste debug (ruwe tekst)
$debug = 0;

$storeFile = false;

$nextData = getPageToLoadNext();
$OpdrachtID		= $nextData['opdracht'];
$page					= $nextData['page'];
$OpdrachtURL	= $nextData['url_opdracht'];
$PageURL			= $nextData['url_open'];
$verkocht			= $nextData['verkocht'];

$OpdrachtData			= getOpdrachtData($OpdrachtID);
#$OpdrachtMembers	= getMembers4Opdracht($OpdrachtID, 'mail');	
$PushMembers			= getMembers4Opdracht($OpdrachtID, 'push');

if($verkocht) {
	toLog('info', $OpdrachtID, '', 'Start controle verkochte huizen van '. $OpdrachtData['naam']);
} else {
	toLog('info', $OpdrachtID, '', 'Start controle pagina '. $page .' van '. $OpdrachtData['naam']);
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
$String[] = "<a href='$PageURL'>Pagina $page</a> verwerkt en ". count($AdressenArray) ." huizen gevonden :<br>";
$String[] = '<ol>';
foreach($AdressenArray as $key => $value) {
	$String[] = "<li>$value</li>";
}
$String[] = '</ol>';

$block[] = implode("\n", $String);

if($verkocht) {
	toLog('debug', $OpdrachtID, '', "Einde verkochte pagina (". count($AdressenArray) ." huizen)");
} else {
	toLog('debug', $OpdrachtID, '', "Einde pagina $page (". count($AdressenArray) ." huizen)");
}

setPageToLoadNext($OpdrachtID, $page, $verkocht, $nextPage);

/*
# Niet de laatste pagina en minder dan 15 huizen => niet goed
if(count($AdressenArray) < 15 AND $nextPage) {			
	# funda.nl laat soms wel de optie zien om naar de volgende pagina te gaan tewijl die er eigenlijk niet is
	# de volgende pagina is namelijk leeg. Mochten er dus te weinig huizen op een pagina staan,
	# dan check ik eerst even of er op de volgende pagina wel huizen staan.
	$PageURL = $OpdrachtURL.'p'.($p+1).'/';
	$contents	= file_get_contents_retry($PageURL, 5);
	
	if(!is_numeric(strpos($contents, "<h3>Geen koopwoningen gevonden die voldoen aan uw zoekopdracht</h3>"))) {
		$ErrorMessage[] = $OpdrachtData['naam'] ."; Script vond maar ". count($AdressenArray) .' huizen op pagina '. $p;
		toLog('error', $OpdrachtID, '', "script vond maar ". count($AdressenArray) ." huizen; pag. $p");
		$push = array(); $push['title'] = "Te weinig huizen gevonden voor '". $OpdrachtData['naam'] ."'"; $push['message'] = "Het aantal gevonden huizen op pagina $p klopt niet : ". $NrHuizen[0]; $push['url'] = $OpdrachtURL; $push['urlTitle'] = $OpdrachtData['naam']; $push['priority']	= $cfgPushErrorPriority;
		send2Pushover($push, array(1));
	}
}
*/


/*
# Als er een nieuw huis, een huis in prijs gedaald, open huis of een huis verkocht is moet er een mail verstuurd worden.
if((count($NewHouses) > 0 OR count($UpdatedPrice) > 0 OR count($OnderVoorbehoud) > 0 OR count($VerkochtHuis) > 0 OR count($OpenHuis) > 0 OR count($Beschikbaar) > 0) AND (count($OpdrachtMembers) > 0)) {
	$FooterText  = "Google Maps (";
	$FooterText .= "<a href='http://maps.google.nl/maps?q=". urlencode($ScriptURL."extern/showKML_mail.php?regio=$OpdrachtID") ."'>vandaag</a>, ";
	$FooterText .= "<a href='http://maps.google.nl/maps?q=". urlencode($ScriptURL."extern/showKML.php?selectie=Z$OpdrachtID&datum=1") ."'>wijk</a>, ";
	$FooterText .= "<a href='http://maps.google.nl/maps?q=". urlencode($ScriptURL."extern/showKML_prijs.php?selectie=Z$OpdrachtID&datum=1") ."'>prijs</a>) | ";
	$FooterText .= "<a href='". $ScriptURL ."admin/edit_opdrachten.php?id=$OpdrachtID'>Zoekopdracht</a> | ";
	$FooterText .= "<a href='". $ScriptURL ."admin/edit_opdrachten.php?action=remove&opdracht=$OpdrachtID'>uitschrijven</a> | ";
	$FooterText .= "<a href='$OpdrachtURL'>funda.nl</a>";
	$FooterText .= "<div class='float_rechts'>(c) 2009-". date("Y") ." Matthijs Draijer</div>";			
	include('include/HTML_TopBottom.php');
			
	if(count($NewHouses) > 0) {
		$omslag			= round(count($NewHouses)/2);
		$KolomEen		= array_slice ($NewHouses, 0, $omslag);
		$KolomTwee	= array_slice ($NewHouses, $omslag, $omslag);
		
		$HTMLMail .= "<tr>\n";
		$HTMLMail .= "<td width='50%' valign='top' align='center'>\n";
		$HTMLMail .= implode("\n<p>\n", $KolomEen);
		$HTMLMail .= "</td><td width='50%' valign='top' align='center'>\n";
		if(count($KolomTwee) > 0) {
			$HTMLMail .= implode("\n<p>\n", $KolomTwee);	
		} else {
			$HTMLMail .= "&nbsp;";	
		}
		$HTMLMail .= "</td>\n";
		$HTMLMail .= "</tr>\n";
		$HTMLMail .= "<tr>\n";
		$HTMLMail .= "	<td colspan='2' align='center'>&nbsp;</td>\n";
		$HTMLMail .= "</tr>\n";
		
		if(count($NewHouses) == 1) {
			$Subject[] = array_shift($NewAddress) .' is nieuw';
		} else {
			$Subject[] = count($NewHouses) ." nieuwe huizen";
		}
	}
	
	if(count($UpdatedPrice) > 0) {
		$omslag			= round(count($UpdatedPrice)/2);
		$KolomEen		= array_slice ($UpdatedPrice, 0, $omslag);
		$KolomTwee	= array_slice ($UpdatedPrice, $omslag, $omslag);
		
		$HTMLMail .= "<tr>\n";
		$HTMLMail .= "	<td colspan='2'><h2>In prijs gedaald</h2></td>\n";
		$HTMLMail .= "</tr>\n";			
		$HTMLMail .= "<tr>\n";
		$HTMLMail .= "<td width='50%' valign='top' align='center'>\n";
		$HTMLMail .= implode("\n<p>\n", $KolomEen);
		$HTMLMail .= "</td><td width='50%' valign='top' align='center'>\n";
		if(count($KolomTwee) > 0) {
			$HTMLMail .= implode("\n<p>\n", $KolomTwee);	
		} else {
			$HTMLMail .= "&nbsp;";	
		}
		$HTMLMail .= "</td>\n";
		$HTMLMail .= "</tr>\n";
		$HTMLMail .= "<tr>\n";
		$HTMLMail .= "	<td colspan='2' align='center'>&nbsp;</td>\n";
		$HTMLMail .= "</tr>\n";			
		
		if(count($UpdatedPrice) == 1) {
			$Subject[] = array_shift($UpdatedAddress) ." is in prijs gedaald";
		} else {
			$Subject[] = count($UpdatedPrice) ." in prijs gedaalde huizen";
		}
	}
	
	if(count($OnderVoorbehoud) > 0) {
		$omslag			= round(count($OnderVoorbehoud)/2);
		$KolomEen		= array_slice ($OnderVoorbehoud, 0, $omslag);
		$KolomTwee	= array_slice ($OnderVoorbehoud, $omslag, $omslag);
		
		$HTMLMail .= "<tr>\n";
		$HTMLMail .= "	<td colspan='2'><h2>Onder voorbehoud verkocht</h2></td>\n";
		$HTMLMail .= "</tr>\n";			
		$HTMLMail .= "<tr>\n";
		$HTMLMail .= "<td width='50%' valign='top' align='center'>\n";
		$HTMLMail .= implode("\n<p>\n", $KolomEen);
		$HTMLMail .= "</td><td width='50%' valign='top' align='center'>\n";
		if(count($KolomTwee) > 0) {
			$HTMLMail .= implode("\n<p>\n", $KolomTwee);	
		} else {
			$HTMLMail .= "&nbsp;";	
		}
		$HTMLMail .= "</td>\n";
		$HTMLMail .= "</tr>\n";
		$HTMLMail .= "<tr>\n";
		$HTMLMail .= "	<td colspan='2' align='center'>&nbsp;</td>\n";
		$HTMLMail .= "</tr>\n";
					
		if(count($OnderVoorbehoud) == 1) {
			$Subject[] = array_shift($BijnaVerkochtAddress) ." is onder voorbehoud verkocht";
		} else {
			$Subject[] = count($OnderVoorbehoud) ." onder voorbehoud verkochte huizen";
		}
	}
	
	if(count($Beschikbaar) > 0) {
		$omslag			= round(count($Beschikbaar)/2);
		$KolomEen		= array_slice ($Beschikbaar, 0, $omslag);
		$KolomTwee	= array_slice ($Beschikbaar, $omslag, $omslag);
		
		$HTMLMail .= "<tr>\n";
		$HTMLMail .= "	<td colspan='2'><h2>Weer beschikbaar</h2></td>\n";
		$HTMLMail .= "</tr>\n";			
		$HTMLMail .= "<tr>\n";
		$HTMLMail .= "<td width='50%' valign='top' align='center'>\n";
		$HTMLMail .= implode("\n<p>\n", $KolomEen);
		$HTMLMail .= "</td><td width='50%' valign='top' align='center'>\n";
		if(count($KolomTwee) > 0) {
			$HTMLMail .= implode("\n<p>\n", $KolomTwee);	
		} else {
			$HTMLMail .= "&nbsp;";	
		}
		$HTMLMail .= "</td>\n";
		$HTMLMail .= "</tr>\n";
		$HTMLMail .= "<tr>\n";
		$HTMLMail .= "	<td colspan='2' align='center'>&nbsp;</td>\n";
		$HTMLMail .= "</tr>\n";
					
		if(count($Beschikbaar) == 1) {
			$Subject[] = array_shift($beschikbaarAddress) ." is weer beschikbaar";
		} else {
			$Subject[] = count($Beschikbaar) ." weer beschikbare huizen";
		}
	}
			
	if(count($VerkochtHuis) > 0) {
		$omslag			= round(count($VerkochtHuis)/2);
		$KolomEen		= array_slice ($VerkochtHuis, 0, $omslag);
		$KolomTwee	= array_slice ($VerkochtHuis, $omslag, $omslag);
		
		$HTMLMail .= "<tr>\n";
		$HTMLMail .= "	<td colspan='2'><h2>Verkocht</h2></td>\n";
		$HTMLMail .= "</tr>\n";			
		$HTMLMail .= "<tr>\n";
		$HTMLMail .= "<td width='50%' valign='top' align='center'>\n";
		$HTMLMail .= implode("\n<p>\n", $KolomEen);
		$HTMLMail .= "</td><td width='50%' valign='top' align='center'>\n";
		if(count($KolomTwee) > 0) {
			$HTMLMail .= implode("\n<p>\n", $KolomTwee);	
		} else {
			$HTMLMail .= "&nbsp;";	
		}
		$HTMLMail .= "</td>\n";
		$HTMLMail .= "</tr>\n";
		$HTMLMail .= "<tr>\n";
		$HTMLMail .= "	<td colspan='2' align='center'>&nbsp;</td>\n";
		$HTMLMail .= "</tr>\n";
		
		if(count($VerkochtHuis) == 1) {
			$Subject[] = array_shift($VerkochtAddress) ." is verkocht";
		} else {
			$Subject[] = count($VerkochtHuis) ." verkochte huizen";
		}
	}
			
	if(count($OpenHuis) > 0) {
		$omslag			= round(count($OpenHuis)/2);
		$KolomEen		= array_slice ($OpenHuis, 0, $omslag);
		$KolomTwee	= array_slice ($OpenHuis, $omslag, $omslag);
		
		$HTMLMail .= "<tr>\n";
		$HTMLMail .= "	<td colspan='2'><h2>Open huis</h2></td>\n";
		$HTMLMail .= "</tr>\n";			
		$HTMLMail .= "<tr>\n";
		$HTMLMail .= "<td width='50%' valign='top' align='center'>\n";
		$HTMLMail .= implode("\n<p>\n", $KolomEen);
		$HTMLMail .= "</td><td width='50%' valign='top' align='center'>\n";
		if(count($KolomTwee) > 0) {
			$HTMLMail .= implode("\n<p>\n", $KolomTwee);	
		} else {
			$HTMLMail .= "&nbsp;";	
		}
		$HTMLMail .= "</td>\n";
		$HTMLMail .= "</tr>\n";
		$HTMLMail .= "<tr>\n";
		$HTMLMail .= "	<td colspan='2' align='center'>&nbsp;</td>\n";
		$HTMLMail .= "</tr>\n";
		
		if(count($OpenHuis) == 1) {
			$Subject[] = array_shift($OpenAddress) ." heeft open huis";
		} else {
			$Subject[] = count($OpenHuis) ." open huizen";
		}
	}
	
	$FinalHTMLMail = $HTMLHeader.$HTMLMail.$HTMLPreFooter.$HTMLFooter;
			
	$html = new html2text($FinalHTMLMail);
	$html->set_base_url($ScriptURL);
	$PlainText = $html->get_text();
	
	# Aan alle geintereseerde een mail versturen
	foreach($OpdrachtMembers as $memberID) {
		$MemberData = getMemberDetails($memberID);
					
		$mail = new PHPMailer;
		$mail->AddAddress($MemberData['mail'], $MemberData['naam']);
		$mail->From     = $ScriptMailAdress;
		$mail->FromName = $ScriptTitle;
		$mail->Subject	= $SubjectPrefix.implode2(', ', ' en ',  $Subject) ." voor '". $OpdrachtData['naam'] ."'";
		$mail->IsHTML(true);
		$mail->Body			= $FinalHTMLMail;
		$mail->AltBody	= $PlainText;
		
		if(!$mail->Send()) {
			echo "Versturen van mail naar ". $MemberData['mail'] ." is mislukt<br>";
			$ErrorMessage[] = "Het versturen van een mail voor ". $OpdrachtData['naam'] ." naar ". $MemberData['mail'] ." is mislukt";
			toLog('error', $OpdrachtID, '', "Kon geen mail versturen naar ". $MemberData['mail']);
			
			# Als mail versturen niet lukt dan schrijven we de inhoud weg als HTML_pagina incl. datum
			$bestandsnaam = $OpdrachtData['naam'] .' ('. date("Ymd_Hi") .')';
			$fp = fopen($bestandsnaam.'.htm', 'w');
			fwrite($fp, $FinalHTMLMail);
			fclose($fp);				
		} else {
			toLog('info', $OpdrachtID, '', "Mail verstuurd naar ". $MemberData['mail']);
		}
	}		
}
*/

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