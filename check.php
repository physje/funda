<?php
include_once(__DIR__.'/include/config.php');
include_once('include/HTML_TopBottom.php');
include_once($cfgGeneralIncludeDirectory.'class.phpmailer.php');
include_once($cfgGeneralIncludeDirectory.'class.html2text.php');
include_once($cfgGeneralIncludeDirectory.'class.phpPushover.php');
connect_db();

# Omdat deze via een cronjob door de server wordt gedraaid is deze niet beveiligd
# Iedereen kan deze pagina dus in principe openen.

# Als er een OpdrachtID is meegegeven hoeft alleen die uitgevoerd te worden.
# In alle andere gevallen gewoon alle actieve zoekopdrachten
if(isset($_REQUEST[OpdrachtID])) {
	$Opdrachten = array($_REQUEST[OpdrachtID]);
	$enkeleOpdracht = true;
} else {
	$Opdrachten = getZoekOpdrachten('', date('H'));
	$enkeleOpdracht = false;
}

$block = array();

# Doorloop alle zoekopdrachten
foreach($Opdrachten as $OpdrachtID) {
	# Alles initialiseren
	$NewHouses = $NewAddress = $UpdatedPrice = $UpdatedAddress = $VerkochtHuis = $VerkochtAddress = $OnderVoorbehoud = $BijnaVerkochtAddress = $OpenHuis = $OpenAddress = $Beschikbaar = $beschikbaarAddress = $Subject = $sommatie = array();
	$HTMLMail = "";
	$nextPage = true;
	$p = 0;

	$OpdrachtData			= getOpdrachtData($OpdrachtID);
	$OpdrachtMembers	= getMembers4Opdracht($OpdrachtID, 'mail');	
	$PushMembers			= getMembers4Opdracht($OpdrachtID, 'push');
	
	$OpdrachtURL	= $OpdrachtData['url'];
	toLog('info', $OpdrachtID, '', 'Start controle '. $OpdrachtData['naam']);
	
	# Vraag de pagina op en herhaal dit het standaard aantal keer mocht het niet lukken
	$contents	= file_get_contents_retry($OpdrachtURL);
	
	$NrHuizen	= getString('<span data-instant-search-output="total">', ' resulta', $contents, 0);

	if(!is_numeric($NrHuizen[0]) AND !strpos($contents, '<div class="no-results">')) {
		$ErrorMessage[] = $OpdrachtData['naam'] ."; Het totaal aantal huizen klopt niet : ". $NrHuizen[0];	
		toLog('error', $OpdrachtID, '', 'Ongeldig aantal huizen');
		$push = array(); $push['title'] = "Ongeldig aantal huizen voor '". $OpdrachtData['naam'] ."'"; $push['message'] = "Het totaal aantal huizen klopt niet : ". $NrHuizen[0]; $push['url'] = $OpdrachtURL; $push['urlTitle'] = $OpdrachtData['naam']; $push['priority']	= $cfgPushErrorPriority;
		send2Pushover($push, array(1));
	}
	
	$String = array('');
	$String[] = "<a href='$OpdrachtURL'>". $OpdrachtData['naam'] ."</a> -> ". $NrHuizen[0] ." huizen<br>\n";
	
	if($enkeleOpdracht) {
		$block[] = implode("\n", $String);		
	}
	
	# Omdat funda.nl niet standaard 15 'echte' huizen op een pagina zet, is het aantal pagina's niet te bepalen
	# op basis van het aantal gevonden huizen ($NrHuizen).
	# Door te kijken of 'next page' op een pagina voorkomt weet ik dat ik nog een pagina verder moet
	while($nextPage) {
		set_time_limit (30);
		$AdressenArray = $VerlopenArray = array();
		$p++;
		
		$PageURL	= $OpdrachtURL.'p'.$p.'/';
		$contents	= file_get_contents_retry($PageURL, 5);
		
		# if(is_numeric(strpos($contents, '<a title="Volgende pagina')) AND $debug == 0) {
		if(is_numeric(strpos($contents, '<span class="pagination-next-label">Volgende</span>')) AND $debug == 0) {
			$nextPage = true;
		} else {
			$nextPage = false;
		}

		# Code opknippen zodat er een array met HTML-code voor een huis ontstaat		
		$Huizen			= explode('<div class="search-result-media">', $contents);
		$Huizen			= array_slice($Huizen, 1);		
		$NrPageHuizen		= count($Huizen);
				
		foreach($verlopenHuizen as $HuisText) {
			$verlopenAdres = getString('<h3>', '<a class=', $HuisText, 0);
			$VerlopenArray[] = $verlopenAdres[0];
		}
				
		if($debug == 1) {
			$block[] = "Aantal huizen op <a href='$PageURL'>pagina $p</a> : ". $NrPageHuizen ."<br>\n";
		}
		
		# Doorloop nu alle gevonden huizen op de overzichtspagina
		foreach($Huizen as $HuisText) {			
			# Extraheer hier adres, plaats, prijs, id etc. uit
			$data = extractFundaData($HuisText);
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
				$allData = extractDetailedFundaData("http://www.funda.nl". $data['url'], false);				
				$data	= array_merge($data, $allData[0]);
				$extraData = $allData[1];
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
			} else {				
				# Huis is al bekend bij het script
				# We moeten dus aangeven dat hij nog steeds op de markt is
				if(!updateAvailability($data['id'])) {
					echo "<font color='red'>Updaten van <b>". $data['adres'] ."</b> is mislukt</font> | $sql<br>\n";
					$ErrorMessage[] = "Updaten van ". $data['adres'] ." is mislukt";
					toLog('error', $OpdrachtID, $data['id'], "Update van huis kon niet worden gedaan");
				} else {
					toLog('debug', $OpdrachtID, $data['id'], 'Huis geupdate');
				}

				# Huis kan gedaald zijn in prijs
				# Dat moeten we dus controleren en indien nodig opslaan en melding van maken
				if(newPrice($data['id'], $data['prijs'])) {							
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
						mysql_query($sql);
						toLog('info', $OpdrachtID, $data['id'], 'Onder voorbehoud verkocht');
					}
				# Het geval dat onder voorbehoud wordt teruggedraaid
				} elseif(soldHouseTentative($data['id'])) {
					$sql = "UPDATE $TableHuizen SET $HuizenVerkocht = '0' WHERE $HuizenID like '". $data['id'] ."'";
					mysql_query($sql);
					toLog('info', $OpdrachtID, $data['id'], 'Niet meer onder voorbehoud verkocht');
				}
				
				# Huis kan openhuis hebben
				if($data['openhuis'] == 1) {
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
			}
			
			# Kijk of dit huis al vaker gevonden is voor deze opdracht
			if(newHouse($data['id'], $OpdrachtID)) {				
				if(!addHouse($data, $OpdrachtID)) {
					$ErrorMessage[] = "Toevoegen van ". $data['adres'] ." aan opdracht $OpdrachtID ging niet goed";
					toLog('error', $OpdrachtID, $data['id'], 'Huis toekennen aan opdracht mislukt');
				} else {
					toLog('debug', $OpdrachtID, $data['id'], 'Huis toegekend aan opdracht');
				}
								
				$fundaData	= getFundaData($data['id']);
				$kenmerken	= getFundaKenmerken($data['id']);
				$fotos			= explode('|', $kenmerken['foto']);
				
				$soldBefore			= soldBefore($data['id']);
				$alreadyOnline	= alreadyOnline($data['id']);
				$onlineBefore		= onlineBefore($data['id']);
				
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
				$Item[] = "	<td colspan='2' align='center'><h1><a href='". $ScriptURL ."extern/redirect.php?id=". $data['id'] ."'>". $data['adres'] ."</a></h1><br>". $fundaData['wijk'] ."<br>\n<br></td>";
				$Item[] = "</tr>";
				$Item[] = "<tr>";
				$Item[] = "	<td align='center' width='55%' rowspan='2'><a href='http://funda.nl/". $data['id'] ."'><img src='". str_replace ('_klein.jpg', '_middel.jpg',  $fundaData['thumb']) ."' alt='klik hier om naar funda.nl te gaan' border='0'></a></td>";
				$Item[] = "	<td align='left' width='45%'>";
				$Item[] = "  ". $fundaData['PC_c'] ." ". $fundaData['PC_l'] ." ". $fundaData['plaats'] ."<br>";
				$Item[] = "  ". $kenmerken['Aantal kamers'] ."<br>";
				$Item[] = "  ". $kenmerken['Wonen (= woonoppervlakte)'] ." (". $kenmerken['Perceeloppervlakte'] .'/'. $kenmerken['Inhoud'] .")<br>";
				$Item[] = "  <b>". formatPrice($data['prijs']) ."</b></td>";
				$Item[] = "</tr>";
				$Item[] = "<tr>";
				$Item[] = "	<td align='left'><i>$extraString</i></td>";
				$Item[] = "</tr>";
				$Item[] = "<tr>";
				$Item[] = "	<td colspan='2'>&nbsp;</td>";
				$Item[] = "</tr>";
				$Item[] = "<tr>";
				$Item[] = "	<td colspan='2'>". makeTextBlock($kenmerken['descr'], 750) ."</td>";
				$Item[] = "</tr>";
				$Item[] = "<tr>";
				$Item[] = "	<td colspan='2'>&nbsp;</td>";
				$Item[] = "</tr>";
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
				$Item[] = "</table>";
					
				$NewHouses[] = showBlock(implode("\n", $Item));				
				$NewAddress[] = $data['adres'];
				
				if($debug == 0) {
					# Pushover-bericht opstellen
					$push = array();
					$push['title']		= "Nieuw huis voor '". $OpdrachtData['naam'] ."'";
					$push['message']	= $data['adres'] .' is te koop voor '. formatPrice($data['prijs']);
					$push['url']			= 'http://funda.nl/'. $data['id'];
					$push['urlTitle']	= $data['adres'];				
					send2Pushover($push, $PushMembers);
				}
			} elseif(changedPrice($data['id'], $data['prijs'], $OpdrachtID)) {
				$fundaData			= getFundaData($data['id']);
				$PriceHistory		= getFullPriceHistory($data['id']);
				$prijzen_array	= $PriceHistory[0];
				$prijzen_perc 	= $PriceHistory[3];
				end($prijzen_array);	# De pointer op de laatste waarde (=laatste prijs) zetten
				
				$Item  = "<table width='100%'>\n";
				$Item .= "<tr>\n";
				$Item .= "	<td align='center'><img src='". $data['thumb'] ."'></td>\n";
				$Item .= "	<td align='center'><a href='http://funda.nl/". $data['id'] ."'>". $data['adres'] ."</a>, ". $data['plaats'] ."<br>\n";
				$Item .= 		$data['PC_c'].$data['PC_l'] ." (". $fundaData['wijk'] .")<br>\n";
				$Item .= "<b>". formatPrice(prev($prijzen_array)) ."</b> -> <b>". formatPrice(end($prijzen_array)) ."</b> (". formatPercentage(end($prijzen_perc)) .")\n";
				$Item .= "</tr>\n";
				$Item .= "</table>\n";
				
				$UpdatedPrice[] = showBlock($Item);
				$UpdatedAddress[] = $data['adres'];
				
				# Pushover-bericht opstellen
				$push = array();
				$push['title']		= $data['adres'] ." is in prijs verlaagd voor '". $OpdrachtData['naam'] ."'";
				$push['message']	= "Van ". formatPrice(prev($prijzen_array)) .' voor '. formatPrice(end($prijzen_array));
				$push['url']			= 'http://funda.nl/'. $data['id'];
				$push['urlTitle']	= $data['adres'];				
				send2Pushover($push, $PushMembers);
			}			
		}
		if($enkeleOpdracht) {
			$String = array('');
		}
		$String[] = "<a href='$PageURL'>Pagina $p</a> verwerkt en ". (count($AdressenArray) + count($VerlopenArray))  ." huizen gevonden". ($enkeleOpdracht ? ' :' : '') ."<br>";
		
		if($enkeleOpdracht) {
			$String[] = '<ol>';
			
			foreach($AdressenArray as $key => $value) {
				$String[] = "<li>$value</li>";
			}
			
			$String[] = '</ol>';
			$String[] = '<ul>';
						
			foreach($VerlopenArray as $key => $value) {
				$String[] = "<li>$value</li>";
			}
			
			$String[] = '</ul>';
		}
		
		if($enkeleOpdracht) {
			$block[] = implode("\n", $String);
		}		
		toLog('debug', $OpdrachtID, '', "Einde pagina $p (". count($AdressenArray) ." huizen)");
		
		# Niet de laatste pagina en minder dan 15 huizen => niet goed
		if((count($AdressenArray) + count($AdressenArray)) < 15 AND $nextPage) {			
			# funda.nl laat soms wel de optie zien om naar de volgende pagina te gaan tewijl die er eigenlijk niet is
			# de volgende pagina is namelijk leeg. Mochten er dus te weinig huizen op een pagina staan,
			# dan check ik eerst even of er op de volgende pagina wel huizen staan.
			$PageURL = $OpdrachtURL.'p'.($p+1).'/';
			$contents	= file_get_contents_retry($PageURL, 5);
			
			if(!is_numeric(strpos($contents, "<h3>Geen koopwoningen gevonden die voldoen aan uw zoekopdracht</h3>"))) {
				$ErrorMessage[] = $OpdrachtData['naam'] ."; Script vond maar ". (count($AdressenArray) + count($VerlopenArray)) .' huizen op pagina '. $p;
				toLog('error', $OpdrachtID, '', "script vond maar ". (count($AdressenArray) + count($VerlopenArray)) ." huizen; pag. $p");
				$push = array(); $push['title'] = "Te weinig huizen gevonden voor '". $OpdrachtData['naam'] ."'"; $push['message'] = "Het aantal gevonden huizen op pagina $p klopt niet : ". $NrHuizen[0]; $push['url'] = $OpdrachtURL; $push['urlTitle'] = $OpdrachtData['naam']; $push['priority']	= $cfgPushErrorPriority;
				send2Pushover($push, array(1));
			}
		}
		
		# Om funda.nl niet helemaal murw te beuken wachten we even 3 seconden voordat we de volgende pagina opvragen
		sleep(3);	
	}
	
	if(!$enkeleOpdracht) {
		$block[] = implode("\n", $String);
	}		
	
	# Verkochte ($data['verkocht'] = 1) en onder voorbehoud ($data['verkocht'] = 2) verkochte huizen
	$sql_verkocht = "SELECT $TableHuizen.$HuizenID FROM $TableResultaat, $TableHuizen WHERE $TableHuizen.$HuizenVerkocht NOT LIKE $TableResultaat.$ResultaatVerkocht AND $TableResultaat.$ResultaatZoekID like '$OpdrachtID' AND $TableResultaat.$ResultaatID = $TableHuizen.$HuizenID";
	$result = mysql_query($sql_verkocht);
	
	if($row = mysql_fetch_array($result)) {
		do {
			$fundaID	= $row[$HuizenID];
			$data			= getFundaData($fundaID);
			
			$OorspronkelijkeVraagprijs	= getOrginelePrijs($fundaID);
			$LaatsteVraagprijs					= getHuidigePrijs($fundaID);
			
			if($data['verkocht'] == '1') {							
				$Item  = "<table width='100%'>\n";
				$Item .= "<tr>\n";
				$Item .= "	<td align='center'><img src='". changeThumbLocation($data['thumb']) ."'></td>\n";
				$Item .= "	<td align='center'><a href='http://funda.nl/". $fundaID ."'>". $data['adres'] ."</a>, ". $data['plaats'] ."<br>\n";
				$Item .= 		$data['PC_c'].$data['PC_l'] ." (". $data['wijk'] .")<br>\n";
				$Item .= "	". date("d-m-y", $data['start']) .' t/m '. date("d-m-y", $data['eind']) ." (". getDoorloptijd($fundaID) .")<br>\n";
				if($LaatsteVraagprijs != $OorspronkelijkeVraagprijs) { $Item .= '<b>'. formatPrice($OorspronkelijkeVraagprijs) .'</b> -> '; }
				$Item .= '	<b>'. formatPrice($LaatsteVraagprijs) ."</b></td>\n";
				$Item .= "</tr>\n";
				$Item .= "</table>\n";
								
				$VerkochtHuis[] = showBlock($Item);
				$VerkochtAddress[] = $data['adres'];
				
				# Pushover-bericht opstellen
				$push = array();
				$push['title']		= $data['adres'] ." is verkocht voor '". $OpdrachtData['naam'] ."'";
				$push['message']	= 'Na '. getDoorloptijd($fundaID) .' verkocht voor '. formatPrice($LaatsteVraagprijs);
				if($LaatsteVraagprijs != $OorspronkelijkeVraagprijs) {
					$push['message']	.= '<br>Oorspronkelijke vraagprijs was '. formatPrice($OorspronkelijkeVraagprijs);
				}
				$push['url']			= 'http://funda.nl/'. $data['id'];
				$push['urlTitle']	= $data['adres'];				
				send2Pushover($push, $PushMembers);			
			} elseif($data['verkocht'] == '2') {
				$Item  = "<table width='100%'>\n";
				$Item .= "<tr>\n";
				$Item .= "	<td align='center'><img src='". $data['thumb'] ."'></td>\n";
				$Item .= "	<td align='center'><a href='http://funda.nl/". $fundaID ."'>". $data['adres'] ."</a>, ". $data['plaats'] ."<br>\n";
				$Item .= 		$data['PC_c'].$data['PC_l'] ." (". $data['wijk'] .")<br>\n";
				$Item .= '	<b>'. formatPrice($LaatsteVraagprijs) ."</b></td>\n";
				$Item .= "</tr>\n";
				$Item .= "</table>\n";
				
				$OnderVoorbehoud[] = showBlock($Item);
				$BijnaVerkochtAddress[] = $data['adres'];
				
				# Pushover-bericht opstellen
				$push = array();
				$push['title']		= $data['adres'] ." is onder voorbehoud verkocht voor '". $OpdrachtData['naam'] ."'";
				$push['message']	= 'Na '. getDoorloptijd($fundaID) .' met een prijs van '. formatPrice($LaatsteVraagprijs);
				if($LaatsteVraagprijs != $OorspronkelijkeVraagprijs) {
					$push['message']	.= '<br>Oorspronkelijke vraagprijs was '. formatPrice($OorspronkelijkeVraagprijs);
				}
				$push['url']			= 'http://funda.nl/'. $data['id'];
				$push['urlTitle']	= $data['adres'];				
				send2Pushover($push, $PushMembers);
			} elseif($data['verkocht'] == '0') {
				$Item  = "<table width='100%'>\n";
				$Item .= "<tr>\n";
				$Item .= "	<td align='center'><img src='". $data['thumb'] ."'></td>\n";
				$Item .= "	<td align='center'><a href='http://funda.nl/". $fundaID ."'>". $data['adres'] ."</a>, ". $data['plaats'] ."<br>\n";
				$Item .= 		$data['PC_c'].$data['PC_l'] ." (". $data['wijk'] .")<br>\n";
				$Item .= '	<b>'. formatPrice($LaatsteVraagprijs) ."</b></td>\n";
				$Item .= "</tr>\n";
				$Item .= "</table>\n";
				$Beschikbaar[] = showBlock($Item);
				$beschikbaarAddress[] = $data['adres'];
				
				# Pushover-bericht opstellen
				$push = array();
				$push['title']		= $data['adres'] ." is weer beschikbaar voor '". $OpdrachtData['naam'] ."'";
				$push['message']	= 'Weer op de markt voor '. formatPrice($LaatsteVraagprijs);
				$push['url']			= 'http://funda.nl/'. $data['id'];
				$push['urlTitle']	= $data['adres'];				
				send2Pushover($push, $PushMembers);
			} else {
				$ErrorMessage[] = $OpdrachtData['naam'] ."; Zoeken van verkochte huizen geeft ongeldig resultaat";
			}
			
			# Bijhouden dat mail verstuurd is met verkochte huis
			$sql_update_verkocht = "UPDATE $TableResultaat SET $ResultaatVerkocht = '". $data['verkocht'] ."' WHERE $ResultaatZoekID like '$OpdrachtID' AND $ResultaatID like '$fundaID'";
			mysql_query($sql_update_verkocht);
		} while($row = mysql_fetch_array($result));
	}
	
	# Open huizen ($data['openhuis'] = 1)
	$sql_open = "SELECT $TableHuizen.$HuizenID FROM $TableResultaat, $TableHuizen WHERE $TableHuizen.$HuizenOpenHuis = '1' AND $TableResultaat.$ResultaatOpenHuis = '0' AND $TableResultaat.$ResultaatZoekID like '$OpdrachtID' AND $TableResultaat.$ResultaatID = $TableHuizen.$HuizenID";
	$result = mysql_query($sql_open);
	
	if($row = mysql_fetch_array($result)) {
		do {
			$fundaID	= $row[$HuizenID];
			$data			= getFundaData($fundaID);
			$open			= getNextOpenhuis($fundaID);
			
			$Item  = "<table width='100%'>\n";
			$Item .= "<tr>\n";
			$Item .= "	<td align='center'><img src='". $data['thumb'] ."'></td>\n";
			$Item .= "	<td align='center'><a href='http://funda.nl/". $fundaID ."'>". $data['adres'] ."</a>, ". $data['plaats'] ."<br>\n";
			$Item .= 		$data['PC_c'].$data['PC_l'] ." (". $data['wijk'] .")<br>\n";
			$Item .= '	<b>'. strftime("%a %e %b %k:%M", $open[0]) ." - ". strftime("%k:%M", $open[1]) ."</b> (<a href='". $ScriptURL ."admin/makeCalendar.php?id=". $fundaID ."'>iCal</a>)</td>\n";
			$Item .= "</tr>\n";
			$Item .= "</table>\n";
			
			$OpenHuis[] = showBlock($Item);
			$OpenAddress[] = $data['adres'];
			
			# Pushover-bericht opstellen
			$push = array();
			$push['title']		= $data['adres'] ." heeft open huis voor '". $OpdrachtData['naam'] ."'";
			$push['message']	= $data['adres'] ." heeft open huis op ". strftime("%a %e %b", $open[0]) ." van ". strftime("%k:%M", $open[0]) ." tot ". strftime("%k:%M", $open[1]);
			$push['url']			= 'http://funda.nl/'. $data['id'];
			$push['urlTitle']	= $data['adres'];				
			send2Pushover($push, $PushMembers);
			
			# Bijhouden dat mail verstuurd is met open huis
			$sql_update_open = "UPDATE $TableResultaat SET $ResultaatOpenHuis = '1' WHERE $ResultaatZoekID like '$OpdrachtID' AND $ResultaatID like '$fundaID'";
			mysql_query($sql_update_open);				
		} while($row = mysql_fetch_array($result));
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
