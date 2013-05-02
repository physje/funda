<?php
include_once('../../general_include/general_functions.php');
include_once('../../general_include/general_config.php');
include_once('../../general_include/class.phpmailer.php');
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
connect_db();

# Omdat deze via een cronjob door de server wordt gedraaid is deze niet beveiligd
# Iedereen kan deze pagina dus in principe openen.

// Als hij een pagina opvraagt die niet bestaat krijg je veel errors/warnings.
// Die wil ik onderdrukken
error_reporting(0);

if(!isset($_REQUEST['keuze']) AND !isset($_REQUEST['regio']) AND !isset($_REQUEST['id'])) {
	$HTML[] = "<a href='?keuze=1'>Huizen die afgelopen maand niet meer online gezien zijn</a><br>\n";
	$HTML[] = "<a href='?keuze=2'>Huizen die afgelopen jaar niet meer online gezien zijn</a><br>\n";
	$HTML[] = "<p>\n";
	
	$opdrachten = getZoekOpdrachten(1);		
	foreach($opdrachten as $opdracht) {
		$OpdrachtData = getOpdrachtData($opdracht);
		$HTML[] = "<a href='?regio=$opdracht'>". $OpdrachtData['naam'] ."</a><br>\n";
	}
} else {
	if(isset($_REQUEST['id'])) {
		$sql_array[] = "SELECT * FROM $TableHuizen WHERE $HuizenID like ". $_REQUEST['id'];
		$HTML[] = '<h1>'. $_REQUEST['id'] ."</h1><br>\n";
	} elseif(isset($_REQUEST['keuze']) OR isset($_REQUEST['regio'])) {
		$sql_array[] = "SELECT";
		$sql_array[] = "$TableHuizen.$HuizenID, $TableHuizen.$HuizenURL, $TableHuizen.$HuizenAdres, $TableHuizen.$HuizenPlaats, $TableHuizen.$HuizenStart, $TableHuizen.$HuizenEind";
		$sql_array[] = "FROM";
		$sql_array[] = "$TableResultaat, $TableHuizen, $TableZoeken";
		$sql_array[] = "WHERE";
		$sql_array[] = "$TableResultaat.$ResultaatID = $TableHuizen.$HuizenID AND";
		$sql_array[] = "$TableResultaat.$ResultaatZoekID = $TableZoeken.$ZoekenKey AND";
		$sql_array[] = "$TableZoeken.$ZoekenActive like '1' AND";
		$sql_array[] = "$TableHuizen.$HuizenVerkocht NOT like '1' AND";
		$sql_array[] = "$TableHuizen.$HuizenOffline like '0' AND";
		
		if($_REQUEST['keuze'] == 1) {
			// Huizen die de laatste maand (muv vandaag) niet meer online gezien zijn 
			$beginGrens = mktime(date('H'), date('i'), date('s'), date('m')-1, date('d'), date('Y'));
		} elseif($_REQUEST['keuze'] == 2) {
			// Huizen die het laatste jaar (muv vandaag) niet meer online gezien zijn 
			$beginGrens = mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y')-1);
		}		
		
		if(isset($_REQUEST['keuze'])){
			$eindGrens	= mktime(date('H'), date('i'), date('s'), date('m'), date('d')-1, date('Y'));
			
			$sql_array[] = "(($TableHuizen.$HuizenEind BETWEEN $beginGrens AND $eindGrens))";
			$HTML[] = '<h1>Huizen voor het laatst gezien tussen '. date('d-m-y H:i', $beginGrens) .' en '. date('d-m-y H:i', $eindGrens) ."</h1><br>\n";
		}
		
		if(isset($_REQUEST['regio'])) {
			// Alle huizen van een bepaalde zoekopdracht
			$sql_array[] = "$TableZoeken.$ZoekenKey like '". $_REQUEST['regio'] ."'";
			$OpdrachtData = getOpdrachtData($_REQUEST['regio']);
			
			$HTML[] = '<h1>Huizen voor '. $OpdrachtData['naam'] ."</h1><br>\n";
		}
		
		$sql_array[] = "GROUP BY $TableHuizen.$HuizenID";
	}
	
	$sql = implode(" ", $sql_array);
	$Debug[] = $sql ."<br>\n";
	
	$result	= mysql_query($sql);	
	if($row = mysql_fetch_array($result)) {
		do {
			$url			= "http://www.funda.nl". urldecode($row[$HuizenURL]);
			$HTML[] = '<b>'. urldecode($row[$HuizenAdres]) ."</b> (<a href='$url'>url</a>, ". urldecode($row[$HuizenPlaats]) .")<br>";
			$HTML[] = "[van ". date("d-m-Y", $row[$HuizenStart]) ." tot ". date("d-m-Y", $row[$HuizenEind]) ."]<br>";
			
			$HTML_temp = extractAndUpdateVerkochtData($row[$HuizenID]);
			$HTML = array_merge($HTML, $HTML_temp);			
/*		
			# Alles weer opnieuw initialiseren.
			unset($prijs, $naam);
			unset($Aanmelddatum, $Verkoopdatum, $AangebodenSinds, $startdata);
			unset($OorspronkelijkeVraagprijs, $LaatsteVraagprijs, $Vraagprijs);
			$verkocht = false;
			
			$fundaID	= $row[$HuizenID];
			$url			= "http://www.funda.nl". urldecode($row[$HuizenURL]);
			$HTML[] = '<b>'. urldecode($row[$HuizenAdres]) ."</b> (<a href='$url'>url</a>, ". urldecode($row[$HuizenPlaats]) .")<br>";
			$HTML[] = "[van ". date("d-m-Y", $row[$HuizenStart]) ." tot ". date("d-m-Y", $row[$HuizenEind]) ."]<br>";
	
			# Via de kenmerkenpagina		
			$data			= extractDetailedFundaData($url);
					
			# Als de array 'data' groter is dan 3 is er data gevonden in de kenmerken-pagina
			if(count($data) > 3) {
				# Reeds verkochte huizen
				if($data['Aanmelddatum'] != '') {
					$guessStartDatum	= guessDate($data['Aanmelddatum']);
					$startDatum	= explode("-", $guessStartDatum);
					$Aanmelddatum = mktime(0, 0, 1, $startDatum[1], $startDatum[0], $startDatum[2]);
				}
							
				if($data['Verkoopdatum'] != '') {
					$guessVerkoopDatum = guessDate($data['Verkoopdatum']);
					$verkoopDatum	= explode("-", $guessVerkoopDatum);
					$Verkoopdatum = mktime(23, 59, 59, $verkoopDatum[1], $verkoopDatum[0], $verkoopDatum[2]);
				}			

				if($data['Laatste vraagprijs'] != '') {
					$prijzen		= explode(" ", $data['Laatste vraagprijs']);				
					$LaatsteVraagprijs	= str_ireplace('.', '' , substr($prijzen[0], 5));
				}
									
				# Huizen die nog niet verkocht zijn
				if($data['Aangeboden sinds'] != '') {
					if($data['Aangeboden sinds'] == '5 maanden') {
						$AangebodenSinds = mktime(date('H'), date('i'), date('s'), date('m')-5, date('d'), date('Y'));
					} elseif($data['Aangeboden sinds'] == '4 maanden') {
						$AangebodenSinds = mktime(date('H'), date('i'), date('s'), date('m')-4, date('d'), date('Y'));
					} elseif($data['Aangeboden sinds'] == '3 maanden') {
						$AangebodenSinds = mktime(date('H'), date('i'), date('s'), date('m')-3, date('d'), date('Y'));
					} elseif($data['Aangeboden sinds'] == '2 maanden') {
						$AangebodenSinds = mktime(date('H'), date('i'), date('s'), date('m')-2, date('d'), date('Y'));
					} elseif($data['Aangeboden sinds'] == '8 weken') {
						$AangebodenSinds = mktime(date('H'), date('i'), date('s'), date('m'), date('d')-56, date('Y'));
					} elseif($data['Aangeboden sinds'] == '7 weken') {
						$AangebodenSinds = mktime(date('H'), date('i'), date('s'), date('m'), date('d')-49, date('Y'));
					} elseif($data['Aangeboden sinds'] == '6 weken') {
						$AangebodenSinds = mktime(date('H'), date('i'), date('s'), date('m'), date('d')-42, date('Y'));
					} elseif($data['Aangeboden sinds'] == '5 weken') {
						$AangebodenSinds = mktime(date('H'), date('i'), date('s'), date('m'), date('d')-35, date('Y'));
					} elseif($data['Aangeboden sinds'] == '4 weken') {
						$AangebodenSinds = mktime(date('H'), date('i'), date('s'), date('m'), date('d')-28, date('Y'));
					} elseif($data['Aangeboden sinds'] == '3 weken') {
						$AangebodenSinds = mktime(date('H'), date('i'), date('s'), date('m'), date('d')-21, date('Y'));
					} elseif($data['Aangeboden sinds'] == '2 weken') {
						$AangebodenSinds = mktime(date('H'), date('i'), date('s'), date('m'), date('d')-14, date('Y'));
					} elseif($data['Aangeboden sinds'] == '6+ maanden') {
						$AangebodenSinds = mktime(date('H'), date('i'), date('s'), date('m')-7, date('d'), date('Y'));
					} else {
						$guessDatum = guessDate($data['Aangeboden sinds']);
						$AangebodenDatum	= explode("-", $guessDatum);
						$AangebodenSinds = mktime(0, 0, 1, $AangebodenDatum[1], $AangebodenDatum[0], $AangebodenDatum[2]);
					}
				}
							
				if($data['Oorspronkelijke vraagprijs'] != '') {
					$prijzen		= explode(" ", $data['Oorspronkelijke vraagprijs']);
					$OorspronkelijkeVraagprijs = str_ireplace('.', '' , substr($prijzen[0], 5));
				}
							
				if($data['Vraagprijs'] != '') {
					$prijzen						= explode(" ", $data['Vraagprijs']);				
					$Vraagprijs	= str_ireplace('.', '' , substr($prijzen[0], 5));
				}			
			} else {
				# De "standaard"-pagina... maar daar staat niet alles op.
				# Aan de andere kant, de kenmerken-pagina werkt niet overal
				$contents = file_get_contents_retry($url);
				
				if(strpos($contents, 'item-sold')) {
					$prop_transaction = getString('<div class="prop-transaction">', '</div>', $contents, 0);
					
					$transaction_date = getString('<span class="transaction-date">', '</span>', $prop_transaction[0], 0);
					$tempAanmelddatum			= getString('<strong>', '</strong>', $transaction_date[0], 0);
														
					$transaction_date_lst = getString('transaction-date lst', '</span>', $contents, 0);
					$tempVerkoopdatum			= getString('<strong>', '</strong>', $transaction_date_lst[0], 0);
										
					//$transaction_price	= getString('transaction-price', '', $prop_transaction[0], 0);
					$tempLaatstevraagprijs			= getString('<span class="price-wrapper">', '</span>', $contents, 0);
	    		
					$sDatum				= explode("-", $tempAanmelddatum[0]);
					$Aanmelddatum = mktime(0, 0, 1, $sDatum[1], $sDatum[0], $sDatum[2]);
	    		
					$eDatum				= explode("-", $tempVerkoopdatum[0]);
					$Verkoopdatum = mktime(23, 59, 59, $eDatum[1], $eDatum[0], $eDatum[2]);
	    		
					$prijzen						= explode(" ", strip_tags($tempLaatstevraagprijs[0]));
					$LaatsteVraagprijs	= str_ireplace('.', '' , substr($prijzen[0], 12));
				}
			}
			
			# Van de 3 bekende data de laagste opzoeken
			if($Aanmelddatum > 10)		{ $startdata[] = $Aanmelddatum;	}
			if($AangebodenSinds > 10)	{ $startdata[] = $AangebodenSinds; }						
																	$startdata[] = $row[$HuizenStart];
			$startDatum = min($startdata);
			
			# Soms wordt een huis erafgehaald en dan paar dagen later er weer opgezet.
			# Om te zorgen dat de 'valse' informatie op de site de data in de dB niet overschrijft wordt de check gedaan.
			if($OorspronkelijkeVraagprijs > 0 AND $Aanmelddatum  == $startDatum) {
				//echo date('d-m-Y', $Aanmelddatum ) .' : '. $OorspronkelijkeVraagprijs ."<br>\n";
				$tijdstip = $Aanmelddatum;
				$prijs[$tijdstip]	= $OorspronkelijkeVraagprijs;
				$naam[$tijdstip]	= 'Oorspronkelijke vraagprijs';
			}

			# Bij aangeboden sinds gaat men niet verder dan 6 maanden.
			# Om te zorgen dat bij een huis wat al twee jaar te koop staat en 9 maanden geleden in prijs is gedaald,
			# niet de oorspronkelijke vraagprijs wordt ingevoerd even de check.
			if($OorspronkelijkeVraagprijs > 0 AND $AangebodenSinds  == $startDatum) {
				//echo date('d-m-Y', $AangebodenSinds ) .' : '. $OorspronkelijkeVraagprijs ."<br>\n";
				$tijdstip = $AangebodenSinds;
				$prijs[$tijdstip]	= $OorspronkelijkeVraagprijs;
				$naam[$tijdstip]	= 'Oorspronkelijke vraagprijs';
			}
			
			# We gaan er vanuit dat de laatste vraagprijs ook de verkoopdatum is
			if($LaatsteVraagprijs > 0 AND $Verkoopdatum > 10) {
				//echo date('d-m-Y', $Verkoopdatum) .' : '. $LaatsteVraagprijs ."<br>\n";
				$tijdstip = $Verkoopdatum;
				$prijs[$tijdstip]	= $LaatsteVraagprijs;
				$naam[$tijdstip]	= 'Laatste vraagprijs';				
			}			
			
			# Sommige huizen verdwijnen van de radar, als ze nog wel online zijn het prijsverloop monitoren.
			if($Vraagprijs > 0) {
				//echo date('d-m-Y') .' : '. $Vraagprijs ."<br>\n";
				$tijdstip = time();
				$prijs[$tijdstip]	= $Vraagprijs;
				$naam[$tijdstip]	= 'Vraagprijs';	
			}

			# Alle gevonden prijzen incl. tijdstippen invoeren			
			foreach($prijs as $key => $value) {				
				if(updatePrice($fundaID, $value, $key)) {
					$HTML[] = " -> ". $naam[$key] ." toegevoegd ($value / ". date("d-m-y", $key) .")<br>";
					toLog('debug', '', $fundaID, $naam[$key] ." toegevoegd");
				} else {
					toLog('error', '', $fundaID, "Error met toevoegen $value als ". $naam[$key]);
				}
			}
			
			# Als er een startdatum gevonden is die verder terugligt dan die bekend was => invoegen
			if($startDatum != $row[$HuizenStart]) {
				$sql_update = "UPDATE $TableHuizen SET $HuizenStart = $startDatum WHERE $HuizenID like $fundaID";
				
				if(mysql_query($sql_update)) {
					$HTML[] = " -> begintijd aangepast<br>";
				} else {
					toLog('error', '', $fundaID, "Error met verwerken begintijd");
				}				
			}
			
			# Als er geen verkoopdatum bekend is, is hij niet verkocht en dus nog online
			if($Verkoopdatum == '') {
				$sql_update = "UPDATE $TableHuizen SET $HuizenEind = ". time() ." WHERE $HuizenID like $fundaID";
				
				if(mysql_query($sql_update)) {
					$HTML[] = " -> eindtijd aangepast<br>";
				} else {
					toLog('error', '', $fundaID, "Error met verwerken begintijd");
				}				
			}			
			
			# Als er een verkoopdatum bekend is => die datum als eindtijd invoeren
			if($Verkoopdatum > 10) {
				$sql_update = "UPDATE $TableHuizen SET $HuizenStart = $startDatum, $HuizenEind = $Verkoopdatum, $HuizenVerkocht = '1' WHERE $HuizenID like $fundaID";
				
				if(mysql_query($sql_update)) {
					$HTML[] = " -> begin- en eindtijd aangepast (verkocht)<br>";
					toLog('info', '', $fundaID, "Huis is verkocht");
					$verkocht = true;
				} else {
					toLog('error', '', $fundaID, "Error met verwerken verkocht huis");
				}			
			}
*/				
		} while($row = mysql_fetch_array($result));
	}
}

echo $HTMLHeader;
echo "<tr>\n";
echo "<td width='50%' valign='top' align='center'>\n";
echo showBlock(implode("\n", $HTML));
echo "</td>";
echo "<td width='50%' valign='top' align='center'>\n";
echo showBlock(implode("\n", $Debug));
echo "</td>";
echo "</tr>\n";
echo $HTMLFooter;

?>