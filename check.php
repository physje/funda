<?php
include_once('../general_include/general_functions.php');
include_once('../general_include/general_config.php');
include_once('../general_include/class.phpmailer.php');
include_once('include/functions.php');
include_once('include/config.php');
include_once('include/HTML_TopBottom.php');

connect_db();

# Als er een OpdrachtID is meegegeven hoeft alleen die uitgevoerd te worden.
# In alle andere gevallen gewoon alle actieve zoekopdrachten
if(isset($_REQUEST[OpdrachtID])) {
	$Opdrachten = array($_REQUEST[OpdrachtID]);
	$enkeleOpdracht = true;
} else {
	$Opdrachten = getZoekOpdrachten(1);
	$enkeleOpdracht = false;
}

# Doorloop alle zoekopdrachten
foreach($Opdrachten as $OpdrachtID) {
	$OpdrachtData = getOpdrachtData($OpdrachtID);
	$OpdrachtURL	= $OpdrachtData['url'];
	toLog('info', $OpdrachtID, '', 'Start controle '. $OpdrachtData['naam']);
	
	# Vraag de pagina op en herhaal dit het standaard aantal keer mocht het niet lukken
	$contents	= file_get_contents_retry($OpdrachtURL);
	
	$NrHuizen	= getString('<span class="hits"> (', ')', $contents, 0);

	if(!is_numeric($NrHuizen[0])) {
		$ErrorMessage[] = $OpdrachtData['naam'] ."; Het totaal aantal huizen klopt niet : ". $NrHuizen[0];	
		toLog('error', $OpdrachtID, '', 'Ongeldig aantal huizen');
	}
	
	$block[] = "<a href='$OpdrachtURL'>". $OpdrachtData['naam'] ."</a> -> ". $NrHuizen[0] ." huizen<br>\n";
	
	# Alles initialiseren
	$HTMLMessage = $UpdatePrice = $Subject = $sommatie = array();
	$nextPage = true;
	$p = 0;
	
	# Omdat funda.nl niet standaard 15 'echte' huizen op een pagina zet, is het aantal pagina's niet te bepalen
	# op basis van het aantal gevonden huizen ($NrHuizen).
	# Door te kijken of 'next page' op een pagina voorkomt weet ik dat ik nog een pagina verder moet
	while($nextPage) {
		$AdressenArray = $VerlopenArray = array();
		$p++;
		
		$PageURL	= $OpdrachtURL.'p'.$p.'/';
		$contents	= file_get_contents_retry($PageURL, 5);
		
		if(is_numeric(strpos($contents, "paging next")) AND $debug == 0) {
			$nextPage = true;
		} else {
			$nextPage = false;
		}
		
		# Op funda.nl staan huizen van verschillende makkelaars-organisaties (NVM, VBO, etc.)
		# Voor elke organisatie wordt een andere class uit de style-sheet gebruikt
		# Deze class geeft precies het begin van een nieuw huis op de overzichtspagina aan
		# Om zeker te zijn dat ik alle huizen vind doe ik eerst alsof álle huizen van NVM zijn,
		# dan of álle huizen van VBO zijn, etc.
		$HuizenNVM		= explode(' nvm" >', $contents);			array_shift($HuizenNVM);
		$HuizenNVMlst	= explode(' nvm lst" >', $contents);	array_shift($HuizenNVMlst);
		$HuizenVBO		= explode(' vbo" >', $contents);			array_shift($HuizenVBO);
		$HuizenVBOlst	= explode(' vbo lst" >', $contents);	array_shift($HuizenVBOlst);
		$HuizenLMV		= explode(' lmv" >', $contents);			array_shift($HuizenLMV);
		$HuizenLMVlst	= explode(' lmv lst" >', $contents);	array_shift($HuizenLMVlst);
		$HuizenExt		= explode(' ext" >', $contents);			array_shift($HuizenExt);
		$HuizenExtlst	= explode(' ext lst" >', $contents);	array_shift($HuizenExtlst);
		$HuizenProject= explode('closed" >', $contents);		array_shift($HuizenProject);
		$Huizen				= array_merge($HuizenNVM, $HuizenNVMlst, $HuizenVBO, $HuizenVBOlst, $HuizenLMV, $HuizenLMVlst, $HuizenExt, $HuizenExtlst, $HuizenProject);
		$NrPageHuizen	= count($Huizen);
		
		# funda.nl heeft sinds 18-02-2013 de gekke gewoonte om ook verkochte huizen op te nemen.
		# Op deze manier wordt de teller van gevonden huizen wel kloppend gehouden.
		$HuizenExpNVM	= explode(' nvm exp" >', $contents);		array_shift($HuizenExpNVM);
		$HuizenExpVBO	= explode(' vbo exp" >', $contents);		array_shift($HuizenExpVBO);
		$HuizenExpLMV	= explode(' lmv exp" >', $contents);		array_shift($HuizenExpLMV);		
		$verlopenHuizen			= array_merge($HuizenExpNVM, $HuizenExpVBO, $HuizenExpLMV);
		
		foreach($verlopenHuizen as $HuisText) {
			$verlopenAdres = getString('<h3>', '<a class=', $HuisText, 0);
			$VerlopenArray[] = $verlopenAdres[0];
		}
				
		if($debug == 1) {
			$block[] = "Aantal huizen op <a href='$PageURL'>pagina $p</a> : ". $NrPageHuizen ."<br>\n";;
			$NrPageHuizen = 7;
		}
		
		# Doorloop nu alle gevonden huizen op de overzichtspagina
		foreach($Huizen as $HuisText) {
			# Extraheer hier adres, plaats, prijs, id etc. uit
			$data = extractFundaData($HuisText);
			$AdressenArray[] = $data['adres'];
						
			# Huis is nog niet bekend bij het script, dus moet worden toegevoegd
			if(!knownHouse($data['id'])) {
				$extraData = extractDetailedFundaData("http://www.funda.nl". $data['url']);				
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
						toLog('debug', $OpdrachtID, $data['id'], "Prijs geupdate");
					}
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

				$Item = array();				
				$Item[] = "<table width='100%'>";
				$Item[] = "<tr>";
				$Item[] = "	<td colspan='2' align='center'><h1><a href='". $ScriptURL ."extern/redirect.php?id=". $data['id'] ."'>". $data['adres'] ."</a></h1><br>". $fundaData['wijk'] ."<br>\n<br></td>";
				$Item[] = "</tr>";
				$Item[] = "<tr>";
				$Item[] = "	<td align='center' width='60%'><a href='http://www.funda.nl". $fundaData['url'] ."'><img src='". str_replace ('_klein.jpg', '_middel.jpg',  $fundaData['thumb']) ."' alt='klik hier om naar funda.nl te gaan' border='0'></a></td>";
				$Item[] = "	<td align='left' width='40%'>";
				$Item[] = "  ". $fundaData['PC_c'] ." ". $fundaData['PC_l'] ." ". $fundaData['plaats'] ."<br>";
				$Item[] = "  ". $kenmerken['Aantal kamers'] ."<br>";
				$Item[] = "  ". $kenmerken['Perceeloppervlakte'] ." (". $kenmerken['Inhoud'] .")<br>";
				$Item[] = "  <b>". formatPrice($data['prijs']) ."</b></td>";
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
					$Item[] = "		<td><a href='http://www.funda.nl". $data['url'] ."fotos/#groot&foto-". ($key + 1) ."'><img src='$foto' border='0'></a></td>";
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
					
				$HTMLMessage[] = showBlock(implode("\n", $Item));					
			} else {
				if(changedPrice($data['id'], $data['prijs'], $OpdrachtID)) {
					$PriceHistory = getFullPriceHistory($data['id']);
					//$prijzen = getPriceHistory($data['id']);
					//
					//array_shift($prijzen);	# De eerste prijs is al de huidige prijs. Die moeten we dus vergeten
					//
					//// Als er nog maar 1 prijs over is, is de beginprijs en de vorige prijs dus hetzelfde :)
					//if(count($prijzen) == 1) {
					//	$vorigePrijs = $beginPrijs = array_shift($prijzen);
					//} else {
					//	$vorigePrijs = array_shift($prijzen);
					//	$beginPrijs = array_pop($prijzen);
					//}
          //
					//$percentageAll	= 100*($data['prijs'] - $beginPrijs)/$beginPrijs;
					//$percentageNu		= 100*($data['prijs'] - $vorigePrijs)/$vorigePrijs;
					
					$percentageNu		= current($PriceHistory[2]);
					$percentageAll	= $PriceHistory[4];
					
					$Item = array();				
					$Item[] = "<img src='". $data['thumb'] ."'><br>";
					$Item[] = "<a href='http://www.funda.nl". $data['url'] ."'>". $data['adres'] ."</a>, van ". formatPrice($vorigePrijs) ." naar ". formatPrice($data['prijs']) ." (". formatPercentage($percentageNu[0]) ."/". formatPercentage($percentageAll) .").";
				
					$UpdatePrice[] = implode("\n", $Item);
				}
			}
		}
		$String = array('');
		$String[] = "<a href='$PageURL'>Pagina $p</a> verwerkt en ". (count($AdressenArray) + count($VerlopenArray))  ." huizen gevonden :<br>";
		
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
		
		$block[] = implode("\n", $String);
		
		toLog('debug', $OpdrachtID, '', "Einde pagina $p (". count($AdressenArray) ." huizen)");
		
		// Dus niet de laatste pagina en minder dan 15 => niet goed
		if((count($AdressenArray) + count($AdressenArray)) < 15 AND $nextPage) {
			$ErrorMessage[] = $OpdrachtData['naam'] ."; Script vond maar ". (count($AdressenArray) + count($VerlopenArray)) .' huizen op pagina '. $p;
			toLog('error', $OpdrachtID, '', "script vond maar ". (count($AdressenArray) + count($VerlopenArray)) ." huizen; pag. $p");
		}
		
		# Om funda.nl niet helemaal murw te beuken wachten we even 3 seconden voordat we de volgende pagina opvragen
		sleep(3);	
	}
	
	//echo "<br>\n<br>\n";
	
	# Als er een nieuw huis is of een huis in prijs gedaald is moet er een mail verstuurd worden.
	if(count($HTMLMessage) > 0 OR count($UpdatePrice) > 0) {		
		$FooterText  = "Google Maps (";
		$FooterText .= "<a href='http://maps.google.nl/maps?q=". urlencode($ScriptURL."extern/showKML_mail.php?regio=$OpdrachtID") ."'>vandaag</a>, ";
		$FooterText .= "<a href='http://maps.google.nl/maps?q=". urlencode($ScriptURL."extern/showKML.php?selectie=Z$OpdrachtID&datum=1") ."'>wijk</a>, ";
		$FooterText .= "<a href='http://maps.google.nl/maps?q=". urlencode($ScriptURL."extern/showKML_prijs.php?selectie=Z$OpdrachtID&datum=1") ."'>prijs</a>) | ";
		$FooterText .= "<a href='". $ScriptURL ."admin/edit_opdrachten.php?id=$OpdrachtID'>Zoekopdracht</a> | ";
		$FooterText .= "<a href='$OpdrachtURL'>funda.nl</a>";
		
		include('include/HTML_TopBottom.php');
		
		$HTMLMail = $HTMLHeader;
		
		if(count($HTMLMessage) > 0) {
			$omslag = round(count($HTMLMessage)/2);
			$KolomEen = array_slice ($HTMLMessage, 0, $omslag);
			$KolomTwee = array_slice ($HTMLMessage, $omslag, $omslag);
			
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
			
			$Subject[] = count($HTMLMessage) ." ". (count($HTMLMessage) == 1 ? 'nieuw huis' : 'nieuwe huizen');
		}
		
		if(count($UpdatePrice) > 0) {
			$HTMLMail .= "<tr>\n";
			$HTMLMail .= "<td colspan='2' align='center'>". showBlock("De volgende huizen zijn in prijs gedaald :<p>". implode("<p>", $UpdatePrice)) ."</td>\n";
			$HTMLMail .= "<tr>\n";			
			$HTMLMail .= "<tr>\n";
			$HTMLMail .= "	<td colspan='2' align='center'>&nbsp;</td>\n";
			$HTMLMail .= "</tr>\n";
			
			$Subject[] = count($UpdatePrice) ." ". (count($UpdatePrice) == 1 ? 'huis' : 'huizen') . " in prijs gedaald";
		}
		
		$HTMLMail .= $HTMLPreFooter;
		$HTMLMail .= $HTMLFooter;
		
		if($OpdrachtData['mail'] == 1 AND $debug == 0) {
			$adressen = explode(';', $OpdrachtData['adres']);
			
			foreach($adressen as $key => $adres) {	
				$mail = new PHPMailer;				
				$mail->AddAddress($adres);
				$mail->From     = $ScriptMailAdress;
				$mail->FromName = $ScriptTitle;
				$mail->Subject	= $SubjectPrefix.implode(' en ', $Subject) ." voor '". $OpdrachtData['naam'] ."'";
				$mail->IsHTML(true);
				$mail->Body			= $HTMLMail;
		
				if(!$mail->Send()) {
					echo "Versturen van mail naar $adres is mislukt<br>";
					$ErrorMessage[] = "Het versturen van een mail voor ". $OpdrachtData['naam'] ." is mislukt";
					toLog('error', $OpdrachtID, '', "Kon geen mail versturen naar $adres");
				} else {
					toLog('info', $OpdrachtID, '', "Mail verstuurd naar $adres");
				}
			}
		}
	}
}

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
	//$mail->WordWrap = 90;
	$mail->AddAddress($ScriptMailAdress, 'Matthijs');
	$mail->Subject	= $SubjectPrefix."problemen met ".$ScriptTitle;
	$mail->IsHTML(true);
	$mail->Body			= $HTMLMail;
	$mail->Send();	
}
?>