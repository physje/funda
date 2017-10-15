<?php
include_once(__DIR__.'/include/config.php');
include_once('include/HTML_TopBottom.php');
include_once($cfgGeneralIncludeDirectory.'class.phpmailer.php');
include_once($cfgGeneralIncludeDirectory.'class.html2text.php');
include_once($cfgGeneralIncludeDirectory.'class.phpPushover.php');
connect_db();

# Omdat deze via een cronjob door de server wordt gedraaid is deze niet beveiligd
# Iedereen kan deze pagina dus in principe openen.

# http://stackoverflow.com/questions/9049460/cron-jobs-and-random-times-within-given-hours/16289693
# Om te zorgen dat de pagina op wisselende tijden wordt geopend heb ik de volgende cronjob opgenomen :
#		sleep $[RANDOM\%3660] ; wget -q -O /dev/null http://example.com/funda/check.php

# Als er een OpdrachtID is meegegeven hoeft alleen die uitgevoerd te worden.
# In alle andere gevallen gewoon alle actieve zoekopdrachten
if(isset($_REQUEST[OpdrachtID])) {
	$Opdrachten = array($_REQUEST[OpdrachtID]);
	$enkeleOpdracht = true;
} else {
	$Opdrachten = getRandomOpdracht();
	$enkeleOpdracht = false;
}

$block = array();

# Doorloop alle zoekopdrachten
foreach($Opdrachten as $OpdrachtID) {
	# Alles initialiseren
	$NewHouses = $NewAddress = $UpdatedPrice = $UpdatedAddress = $VerkochtHuis = $VerkochtAddress = $OnderVoorbehoud = $BijnaVerkochtAddress = $OpenHuis = $OpenAddress = $Beschikbaar = $beschikbaarAddress = $Subject = $sommatie = array();
	$HTMLMail = "";
	
	$OpdrachtData			= getOpdrachtData($OpdrachtID);
	$OpdrachtMembers	= getMembers4Opdracht($OpdrachtID, 'mail');	
	$PushMembers			= getMembers4Opdracht($OpdrachtID, 'push');
	
	$OpdrachtURL	= "http://partnerapi.funda.nl/feeds/Aanbod.svc/json/$fundaAPI/?type=koop&zo=". str_replace ("http://www.funda.nl/koop", "", $OpdrachtData['url']) ."&pagesize=15";
	toLog('info', $OpdrachtID, '', 'Start controle '. $OpdrachtData['naam']);
	
	$NrPaginas = 1;
	
	for($p=1 ; $p <= $NrPaginas ; $p++) {
		set_time_limit (30);
		$AdressenArray = $VerlopenArray = $String = array();
		
		$PageURL	= $OpdrachtURL.'&page='.$p;
	
		# Vraag de pagina op en herhaal dit het standaard aantal keer mocht het niet lukken
		$contents	= file_get_contents_retry($PageURL);
	
		$JSONArray = json_decode($contents, true);
	
		// = $JSONArray['AccountStatus'];
		// = $JSONArray['EmailNotConfirmed'];
		// = $JSONArray['ValidationFailed'];
		// = $JSONArray['ValidationReport'];
		// = $JSONArray['Website'];
		// = $JSONArray['Metadata'];
		$Huizen = $JSONArray['Objects'];
		$Paginas = $JSONArray['Paging'];
		$NrHuizen = $JSONArray['TotaalAantalObjecten'];
		
		$NrPageHuizen	= count($Huizen);
		$NrPaginas = $Paginas['AantalPaginas'];
				
		if($p == 1) {			
			$block[] = "<a href='$OpdrachtURL'>". $OpdrachtData['naam'] ."</a> -> <a href='". $OpdrachtData['url'] ."'>". $NrHuizen ." huizen</a><br>\n";
		}
	
		if($debug == 1) {
			$String[] = "Aantal huizen op <a href='$PageURL'>pagina $p</a> : ". $NrPageHuizen ."<br>\n";
		}
		
		foreach($Huizen as $huis) {
			$mappen 	= explode("/", $huis['URL']);
			$key			= $mappen[5];			
			$key_parts	= explode("-", $key);
			$fundaID	= $key_parts[1];
	
			$String[] = "<a href='". $huis['URL'] ."'>". $huis['Adres'] ."</a> ($fundaID)";
						
			# Kijken of huis bestaat in de database, vraagprijs opslaan, onder voorbehoud verkocht, open huis bijhouden
			if(!knownHouse($fundaID)) {
				#																					#
				# HUIS IS NOG NIET BEKEND BIJ HET SCRIPT	#
				#
				
				# Gegevens over het huis opslaan
				if(!saveHouseJSON($huis, $fundaID)) {
					$ErrorMessage[] = "Toevoegen van ". $huis['Adres'] ." aan het script ging niet goed";
					toLog('error', $OpdrachtID, $fundaID, 'Huis toevoegen aan script mislukt');
				} else {					
					toLog('info', $OpdrachtID, $fundaID, 'Huis toevoegen aan script');
				}
				
				# Coordinaten van het huis toevoegen
				if(!addKnowCoordinates(array($huis['WGS84_Y'], $huis['WGS84_X']), $fundaID)) {					
					$ErrorMessage[] = "Toevoegen van coordinaten aan ". $huis['Adres'] ." ging niet goed";	
					toLog('error', $OpdrachtID, $fundaID, 'Coordinaten toevoegen mislukt');
				} else {
					toLog('debug', $OpdrachtID, $fundaID, "Coordinaten toegevoegd");
				}
				
				# Prijs van het huis opslaan
				if(!updatePrice($fundaID, $huis['Koopprijs'])) {
					$ErrorMessage[] = "Toevoegen van prijs (". $huis['Koopprijs'] .") aan ". $huis['Adres'] ." ging niet goed";
					toLog('error', $OpdrachtID, $fundaID, 'Prijs toevoegen mislukt');
				} else {
					toLog('debug', $OpdrachtID, $fundaID, "Prijs toegevoegd");
				}
			} else {				
				#																		#
				# HUIS IS AL BEKEND BIJ HET SCRIPT	#
				#																		#
					
				# We moeten dus aangeven dat hij nog steeds op de markt is
				//if(!updateAvailability($fundaID, $huis['PublicatieDatum'])) {
				if(!updateHouseJSON($huis, $fundaID)) {
					$ErrorMessage[] = "Updaten van ". $huis['Adres'] ." is mislukt";
					toLog('error', $OpdrachtID, $fundaID, "Update van huis kon niet worden gedaan");
				} else {
					toLog('debug', $OpdrachtID, $fundaID, 'Huis geupdate');
				}
				
				
				# Huis kan gedaald zijn in prijs
				# Dat moeten we dus controleren en indien nodig opslaan en melding van maken
				if(newPrice($fundaID, $huis['Koopprijs'])) {							
					if(!updatePrice($fundaID, $huis['Koopprijs'])) {
						$ErrorMessage[] = "Updaten van prijs (". $huis['Koopprijs'] .") aan ". $huis['Adres'] ." ging niet goed";
						toLog('error', $OpdrachtID, $fundaID, "Nieuwe prijs van ". $huis['Koopprijs'] ." kon niet worden toegevoegd");
					} else {
						toLog('debug', $OpdrachtID, $fundaID, "Nieuwe vraagprijs");
					}
				}
				
				
				# Huis kan onder voorbehoud verkocht zijn
				if($huis['VerkoopStatus'] == 'Verkocht onder voorbehoud') {
					if(!soldHouseTentative($fundaID)) {
						$sql = "UPDATE $TableHuizen SET $HuizenVerkocht = '2' WHERE $HuizenID like '$fundaID'";
						mysql_query($sql);
						toLog('info', $OpdrachtID, $fundaID, 'Onder voorbehoud verkocht');
					}
				# Het geval dat onder voorbehoud wordt teruggedraaid
				} elseif(soldHouseTentative($fundaID)) {
					$sql = "UPDATE $TableHuizen SET $HuizenVerkocht = '0' WHERE $HuizenID like '$fundaID'";
					mysql_query($sql);
					toLog('info', $OpdrachtID, $fundaID, 'Niet meer onder voorbehoud verkocht');
				}
				
				/*
				nog een keer naar kijken als er meer huizen met open huis zijn
				
				# Huis kan openhuis hebben
				if($huis['OpenHuis'] != '') {
					# data online vergelijken met data in de database
					$changedOpenHuis	= false;
					$tijden			= extractOpenHuisData($data['id']);
					$bestaandeTijden	= getNextOpenhuis($data['id']);

					if($tijden[0] != $bestaandeTijden[0] OR $tijden[1] != $bestaandeTijden[1]) {
						$sql = "DELETE FROM $TableCalendar WHERE $CalendarHuis like ". $data['id'] ." AND $CalendarStart like ". $bestaandeTijden[0] ." AND $CalendarEnd like ". $bestaandeTijden[1];
						mysql_query($sql);
						$changedOpenHuis = true;
						toLog('info', $OpdrachtID, $data['id'], 'Open Huis gewijzigd');
					}

					if(!hasOpenHuis($data['id']) OR $changedOpenHuis) {
						toLog('info', $OpdrachtID, $data['id'], 'Open Huis aangekondigd');
						
						#	toevoegen aan de Google Calendar						
						$sql = "INSERT INTO $TableCalendar ($CalendarHuis, $CalendarStart, $CalendarEnd) VALUES (". $data['id'] .", ". $tijden[0] .", ". $tijden[1] .")";
						mysql_query($sql);
												
						#	opnemen in de eerst volgende mail						
						$sql = "UPDATE $TableHuizen SET $HuizenOpenHuis = '1' WHERE $HuizenID like '". $data['id'] ."'";
						mysql_query($sql);
					}
				} else {
					removeOpenHuis($data['id']);
				}
				*/
			}
			
			# Kijk of dit huis al vaker gevonden is voor deze opdracht
			if(newHouse($fundaID, $OpdrachtID)) {				
				if(!addHouse(array('id' => $fundaID, 'prijs' => $huis['Koopprijs']), $OpdrachtID)) {
					$ErrorMessage[] = "Toevoegen van ". $huis['Adres'] ." aan opdracht $OpdrachtID ging niet goed";
					toLog('error', $OpdrachtID, $fundaID, 'Huis toekennen aan opdracht mislukt');
				} else {
					toLog('debug', $OpdrachtID, $fundaID, 'Huis toegekend aan opdracht');
				}
								
				//$fundaData	= getFundaData($fundaID);
				//$kenmerken	= getFundaKenmerken($fundaID);
				//$fotos			= explode('|', $kenmerken['foto']);
				
				$soldBefore			= soldBefore($fundaID);
				$alreadyOnline	= alreadyOnline($fundaID);
				$onlineBefore		= onlineBefore($fundaID);
				
				if(is_numeric($soldBefore)) {
					$extraData = getFundaData($soldBefore);
					$extraString = "<a href='http://funda.nl/$soldBefore'>Verkocht op ". date("d-m-Y", $extraData['eind']) ."</a>";
				} elseif(is_numeric($alreadyOnline)) {
					$extraData = getFundaData($alreadyOnline);
					$extraString = "<a href='http://funda.nl/$alreadyOnline'>Al online bij ". $extraData['makelaar'] ."</a>";
				} elseif(is_numeric($onlineBefore)) {
					$extraData = getFundaData($onlineBefore);
					$extraString = implode(" & ", getTimeBetween($extraData['eind'], time())) ." geleden offline gegaan";
				} else {
					$extraString = '&nbsp;';
				}
				
				# Mail opstellen
				$Item = array();				
				$Item[] = "<table width='100%'>";
				$Item[] = "<tr>";
				//$Item[] = "	<td colspan='2' align='center'><h1><a href='". $ScriptURL ."extern/redirect.php?id=$fundaID'>". $fundaData['adres'] ."</a></h1><br>". $fundaData['wijk'] ."<br>\n<br></td>";
				$Item[] = "	<td colspan='2' align='center'><h1><a href='". $ScriptURL ."extern/redirect.php?id=$fundaID'>". $huis['Adres'] ."</a></h1></td>";
				$Item[] = "</tr>";
				$Item[] = "<tr>";
				$Item[] = "	<td align='center' width='55%' rowspan='2'><a href='http://funda.nl/$fundaID'><img src='". $huis['FotoMedium'] ."' alt='klik hier om naar funda.nl te gaan' border='0'></a></td>";
				$Item[] = "	<td align='left' width='45%'>";
				$Item[] = "  ". $huis['Postcode'] ." ". $huis['Woonplaats'] ."<br>";
				$Item[] = "  ". $huis['AantalKamers'] ."<br>";
				$Item[] = "  ". $huis['Woonoppervlakte'] ." (". $huis['Perceeloppervlakte'] .")<br>";
				$Item[] = "  <b>". formatPrice($huis['Koopprijs']) ."</b></td>";
				$Item[] = "</tr>";
				$Item[] = "<tr>";
				$Item[] = "	<td align='left'><i>$extraString</i></td>";
				$Item[] = "</tr>";
				$Item[] = "<tr>";
				$Item[] = "	<td colspan='2'>&nbsp;</td>";
				$Item[] = "</tr>";
				//$Item[] = "<tr>";
				//$Item[] = "	<td colspan='2'>". makeTextBlock($kenmerken['descr'], 750) ."</td>";
				//$Item[] = "</tr>";
				$Item[] = "<tr>";
				$Item[] = "	<td colspan='2'>&nbsp;</td>";
				$Item[] = "</tr>";
				/*
				$Item[] = "<tr>";
				$Item[] = "	<td colspan='2' align='center'>";
					
				if(is_array($fotos)) { $selectie = array_slice ($fotos, 0, (($colPhoto * $rowPhoto)-1)); } else { $selectie = array(); }
					
				$Item[] = "	<table>";
				$Item[] = "	<tr>";
					
				foreach($selectie as $key => $foto) {
					$foto_resize = str_replace ('_1440x960.jpg', '_180x120.jpg', $foto);
					$Item[] = "		<td><a href='http://www.funda.nl". $data['url'] ."fotos/#groot&foto-". ($key + 1) ."'><img src='$foto_resize' border='0'></a></td>";
					if(fmod($key, $colPhoto) == ($colPhoto - 1)) {
						$Item[] = "	</tr>";
						$Item[] = "	<tr>";
					}
				}
					
				if (count($fotos) > (($colPhoto * $rowPhoto)-1)) {
					$Item[] = "		<td align='center'><a href='http://www.funda.nl". $data['url'] ."fotos/#groot&foto-$key'>bekijk<br>meer<br>foto's</a></td>";
				}				
				$Item[] = "	</tr>";
				$Item[] = "	</table>";				
				$Item[] = "	</td>";
				$Item[] = "</tr>";
				*/
				$Item[] = "</table>";
					
				$NewHouses[] = showBlock(implode("\n", $Item));				
				$NewAddress[] = $huis['Adres'];
				
				if($debug == 0) {
					# Pushover-bericht opstellen
					$push = array();
					$push['title']		= "Nieuw huis voor '". $OpdrachtData['naam'] ."'";
					$push['message']	= $huis['Adres'] .' is te koop voor '. formatPrice($huis['Koopprijs']);
					$push['url']			= 'http://funda.nl/'. $fundaID;
					$push['urlTitle']	= $huis['Adres'];				
					send2Pushover($push, $PushMembers);
				}
			} elseif(changedPrice($fundaID, $huis['Koopprijs'], $OpdrachtID)) {
				//$fundaData			= getFundaData($data['id']);
				$PriceHistory		= getFullPriceHistory($fundaID);
				$prijzen_array	= $PriceHistory[0];
				$prijzen_perc 	= $PriceHistory[3];
				end($prijzen_array);	# De pointer op de laatste waarde (=laatste prijs) zetten
				
				$Item  = "<table width='100%'>\n";
				$Item .= "<tr>\n";
				$Item .= "	<td align='center'><img src='". $huis['FotoMedium'] ."'></td>\n";
				$Item .= "	<td align='center'><a href='http://funda.nl/$fundaID'>". $huis['Adres'] ."</a>, ". $huis['Woonplaats'] ."<br>\n";
				//$Item .= 		$data['PC_c'].$data['PC_l'] ." (". $fundaData['wijk'] .")<br>\n";
				$Item .= $huis['Postcode'] ."<br>\n";
				$Item .= "<b>". formatPrice(prev($prijzen_array)) ."</b> -> <b>". formatPrice(end($prijzen_array)) ."</b> (". formatPercentage(end($prijzen_perc)) .")\n";
				$Item .= "</tr>\n";
				$Item .= "</table>\n";
				
				$UpdatedPrice[] = showBlock($Item);
				$UpdatedAddress[] = $huis['Adres'];
				
				if($debug == 0) {
					# Pushover-bericht opstellen
					$push = array();
					$push['title']		= $huis['Adres'] ." is in prijs verlaagd voor '". $OpdrachtData['naam'] ."'";
					$push['message']	= "Van ". formatPrice(prev($prijzen_array)) .' voor '. formatPrice(end($prijzen_array));
					$push['url']			= 'http://funda.nl/'. $fundaID;
					$push['urlTitle']	= $huis['Adres'];				
					send2Pushover($push, $PushMembers);
				}
			}
		}
		
		if($enkeleOpdracht) {
			$block[] = implode("<br>\n", $String);
		}	
	}
	
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
				
		$html =& new html2text($FinalHTMLMail);
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
