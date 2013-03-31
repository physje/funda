<?php
include_once('../../general_include/general_functions.php');
include_once('../../general_include/general_config.php');
include_once('../../general_include/class.phpmailer.php');
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
connect_db();

// Als hij een pagina opvraagt die niet bestaat krijg je veel errors/warnings.
// Die wil ik onderdrukken
error_reporting(0);

if(!isset($_REQUEST['keuze']) AND !isset($_REQUEST['regio']) AND !isset($_REQUEST['id'])) {
	$HTML[] = "<a href='?keuze=1'>Huizen die de laatste dag niet meer online gezien zijn</a><br>\n";
	$HTML[] = "<a href='?keuze=2'>Alle huizen van het laatste jaar</a><br>\n";
	$HTML[] = "<p>\n";
	
	$opdrachten = getZoekOpdrachten(1);		
	foreach($opdrachten as $opdracht) {
		$OpdrachtData = getOpdrachtData($opdracht);
		$HTML[] = "<a href='?regio=$opdracht'>". $OpdrachtData['naam'] ."</a><br>\n";
	}
} else {
	if(isset($_REQUEST['id'])) {
		$sql		= "SELECT * FROM $TableHuizen WHERE $HuizenID like ". $_REQUEST['id'];
		$HTML[] = '<h1>'. $_REQUEST['id'] ."</h1><br>\n";
	} elseif(isset($_REQUEST['keuze']) OR isset($_REQUEST['regio'])) {
		$sql		= "SELECT $TableHuizen.$HuizenID, $TableHuizen.$HuizenURL, $TableHuizen.$HuizenAdres, $TableHuizen.$HuizenPlaats, $TableHuizen.$HuizenStart FROM $TableResultaat, $TableHuizen, $TableZoeken WHERE $TableResultaat.$ResultaatID = $TableHuizen.$HuizenID AND $TableResultaat.$ResultaatZoekID = $TableZoeken.$ZoekenKey AND $TableZoeken.$ZoekenActive like '1' AND $TableHuizen.$HuizenVerkocht like '0' AND $TableHuizen.$HuizenOffline like '0' AND ";
		
		if($_REQUEST['keuze'] == 1) {
			// Huizen die de laatste maand (muv vandaag) niet meer online gezien zijn 
			$beginGrens = mktime(date('H'), date('i'), date('s'), date('m')-1, date('d'), date('Y'));
		} elseif($_REQUEST['keuze'] == 2) {
			// Huizen die het laatste jaar (muv vandaag) niet meer online gezien zijn 
			$beginGrens = mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y')-1);
		}		
		
		if(isset($_REQUEST['keuze'])){
			$eindGrens	= mktime(date('H'), date('i'), date('s'), date('m'), date('d')-1, date('Y'));
			
			$sql		.= "(($TableHuizen.$HuizenEind BETWEEN $beginGrens AND $eindGrens) OR ($TableHuizen.$HuizenStart > $eindGrens))";
			$HTML[] = '<h1>Huizen voor het laatst gezien tussen '. date('d-m-y H:i', $beginGrens) .' en '. date('d-m-y H:i', $eindGrens) ."</h1><br>\n";
		}
		
		if(isset($_REQUEST['regio'])) {
			// Alle huizen van een bepaalde zoekopdracht
			$sql		.= "$TableZoeken.$ZoekenKey like '". $_REQUEST['regio'] ."'";
			$OpdrachtData = getOpdrachtData($_REQUEST['regio']);
			
			$HTML[] = '<h1>Huizen voor '. $OpdrachtData['naam'] ."</h1><br>\n";
		}
		
		$sql		.= " GROUP BY $TableHuizen.$HuizenID";
	}
	
	$Debug[] = $sql."<br>\n";
	
	$result	= mysql_query($sql);
	if($row = mysql_fetch_array($result)) {
		do {
			unset($prijs, $naam);
			unset($Aanmelddatum, $Verkoopdatum, $AangebodenSinds);
			unset($OorspronkelijkeVraagprijs, $LaatsteVraagprijs, $Vraagprijs);
			$verkocht = false;
			
			$fundaID	= $row[$HuizenID];
			$url			= "http://www.funda.nl". urldecode($row[$HuizenURL]);
			$HTML[] = '<b>'. urldecode($row[$HuizenAdres]) ."</b> (<a href='$url'>url</a>, ". urldecode($row[$HuizenPlaats]) .")<br>";
	
			// Via de kenmerkenpagina		
			$data			= extractDetailedFundaData($url);
					
			// Data gevonden in de kenmerken-pagina
			if(count($data) > 3) {
				// Reeds verkochte huizen
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
									
				// Huizen die nog niet verkocht zijn
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
						//$tijd_temp = array();
						//$tijd_temp[] = time() - 7*$maand;
						//$tijd_temp[] = $start_tijd-$dag;
						//$tijd = min($tijd_temp);
						$AangebodenSinds = mktime(date('H'), date('i'), date('s'), date('m')-7, date('d'), date('Y'));
					} else {
						$guessDatum = guessDate($data['Aangeboden sinds']);
						$AangebodenDatum	= explode("-", $guessDatum);
						$AangebodenSinds = mktime(0, 0, 1, $AangebodenDatum[1], $AangebodenDatum[0], $AangebodenDatum[2]);
						//toLog('error', '', $fundaID, "Aangebode sinds ". $AangebodenSinds ." onbekend");
						//echo 'ERROR : '. $data['Aangeboden sinds'] .'|'. $AangebodenDatum[0].'|'. $AangebodenDatum[1].'|'. $AangebodenDatum[2];					
					}
				}
							
				if($data['Oorspronkelijke vraagprijs'] != '' AND $data['Aanmelddatum'] != '') {
					$tijdstip		= $Aanmelddatum;				
					$prijzen		= explode(" ", $data['Oorspronkelijke vraagprijs']);
					$OorspronkelijkeVraagprijs = str_ireplace('.', '' , substr($prijzen[0], 5));
					$prijs[$tijdstip]	= $OorspronkelijkeVraagprijs;
					$naam[$tijdstip]	= 'Oorspronkelijke vraagprijs';
				}
							
				if($data['Oorspronkelijke vraagprijs'] != '' AND $AangebodenSinds != '') {
					$tijdstip					= $AangebodenSinds;
					$prijzen					= explode(" ", $data['Oorspronkelijke vraagprijs']);
					$OorspronkelijkeVraagprijs = str_ireplace('.', '' , substr($prijzen[0], 5));
					$prijs[$tijdstip]	= $OorspronkelijkeVraagprijs;
					$naam[$tijdstip]	= 'Oorspronkelijke vraagprijs';
				}
							
				if($data['Laatste vraagprijs'] != '' AND $data['Verkoopdatum'] != '') {
					$tijdstip		= $Verkoopdatum;
					$prijzen		= explode(" ", $data['Laatste vraagprijs']);				
					$LaatsteVraagprijs	= str_ireplace('.', '' , substr($prijzen[0], 5));
					$prijs[$tijdstip]		= $LaatsteVraagprijs;
					$naam[$tijdstip]		= 'Laatste vraagprijs';
				}
								
				if($data['Vraagprijs'] != '' AND $AangebodenSinds != '') {
					$tijdstip					= $AangebodenSinds;
					$prijzen					= explode(" ", $data['Vraagprijs']);				
					$Vraagprijs	= str_ireplace('.', '' , substr($prijzen[0], 5));
					$prijs[$tijdstip]		= $Vraagprijs;
					$naam[$tijdstip]		= 'Vraagprijs';
				}				
							
				if($data['Vraagprijs'] != ''  AND $AangebodenSinds == '') {
					$tijdstip						= time();
					$prijzen						= explode(" ", $data['Vraagprijs']);				
					$Vraagprijs	= str_ireplace('.', '' , substr($prijzen[0], 5));
					$prijs[$tijdstip]		= $Vraagprijs;
					$naam[$tijdstip]		= 'Vraagprijs';
				}
			} else {
				// De "standaard"-pagina... maar daar staat niet alles op.
				// Aan de andere kant, de kenmerken-pagina werkt niet overal
				$contents = file_get_contents_retry($url);
				
				if(strpos($contents, 'transaction-price')) {
					$prop_transaction = getString('<div class="prop-transaction">', '</div>', $contents, 0);
					
					$transaction_date = getString('<span class="transaction-date">', '</span>', $prop_transaction[0], 0);
					$tempAanmelddatum			= getString('<strong>', '</strong>', $transaction_date[0], 0);
					
					$transaction_date_lst = getString('<span class="transaction-date lst">', '</span>', $prop_transaction[0], 0);
					$tempVerkoopdatum			= getString('<strong>', '</strong>', $transaction_date_lst[0], 0);
					
					$transaction_price	= getString('<span class="transaction-price">', '', $prop_transaction[0], 0);
					$tempLaatstevraagprijs			= getString('<span class="price-wrapper">', '</span>', $transaction_price[0], 0);
	    		
					$startDatum		= explode("-", $tempAanmelddatum[0]);
					$Aanmelddatum = mktime(0, 0, 1, $startDatum[1], $startDatum[0], $startDatum[2]);
	    		
					$eindDatum		= explode("-", $tempVerkoopdatum[0]);
					$Verkoopdatum = mktime(23, 59, 59, $eindDatum[1], $eindDatum[0], $eindDatum[2]);
	    		
					$tijdstip						= $Verkoopdatum;
					$prijzen						= explode(" ", strip_tags($tempLaatstevraagprijs[0]));
					$LaatsteVraagprijs	= str_ireplace('.', '' , substr($prijzen[0], 12));
					$prijs[$tijdstip]		= $LaatsteVraagprijs;
					$naam[$tijdstip]		= 'Laatste vraagprijs';
				}
			}
			
			foreach($prijs as $key => $value) {				
				if(updatePrice($fundaID, $value, $key)) {
					$HTML[] = " -> ". $naam[$key] ." toegevoegd ($value / ". date("d-m-y", $key) .")<br>";
					toLog('debug', '', $fundaID, $naam[$key] ." toegevoegd");
				} else {
					toLog('error', '', $fundaID, "Error met toevoegen $value als ". $naam[$key]);
				}
			}
			
			if(($Aanmelddatum > 1000 AND $Verkoopdatum > $Aanmelddatum) OR ($AangebodenSinds > 1000 AND $Verkoopdatum > $AangebodenSinds)) {
				if($Aanmelddatum > 1000) {
					$startDatum = $Aanmelddatum;				
				} else {
					$startDatum = $AangebodenSinds;
				}
				$sql_update = "UPDATE $TableHuizen SET $HuizenStart = $startDatum, $HuizenEind = $Verkoopdatum, $HuizenVerkocht = '1' WHERE $HuizenID like $fundaID";
							
				if(mysql_query($sql_update)) {
					$HTML[] = " -> begin- en eindtijd aangepast<br>";
					toLog('info', '', $fundaID, "Huis is verkocht");
					$verkocht = true;
				} else {
					toLog('error', '', $fundaID, "Error met verwerken verkocht huis");
				}
			} elseif($AangebodenSinds > 1000 AND $AangebodenSinds < $row[$HuizenStart]) {
				$sql_update = "UPDATE $TableHuizen SET $HuizenStart = $AangebodenSinds WHERE $HuizenID like $fundaID";
				
				if(mysql_query($sql_update)) {
					$HTML[] = " -> begintijd aangepast<br>";
				} else {
					toLog('error', '', $fundaID, "Error met verwerken begintijd");
				}
			}
			
			$HTML[] = "\n<br>\n";
					
			//$Debug[] = urldecode($row[$HuizenAdres]) .' -> '. $data['Aanmelddatum'] .'='. date("d-m-y", $begin_tijd) .'|'. $HuisPrijs .'|'. $data['Verkoopdatum'] .'='. date("d-m-y", $eind_tijd) .'|'. $url ."<br>\n";
			
			if($verkocht) {
				$data = getFundaData($fundaID);
				$Item  = "<table width='100%'>";
				$Item .= "<tr>";
				$Item .= "	<td align='center'><img src='". changeThumbLocation(urldecode($data['thumb'])) ."'></td>";
				$Item .= "	<td align='center'><a href='http://www.funda.nl". $data['url'] ."'>". urldecode($data['adres']) ."</a>, ". urldecode($data['plaats']) ."<br>";
				$Item .= "	". date("d-m-y", $startDatum) .' t/m '. date("d-m-y", $Verkoopdatum) ." (". getDoorloptijd($fundaID) .")<br>";
				if($OorspronkelijkeVraagprijs != "") { $Item .= '<b>€ '. number_format($OorspronkelijkeVraagprijs,0,',','.') .'</b> -> '; }
				$Item .= '	<b>€ '. number_format($LaatsteVraagprijs,0,',','.') ."</b></td>";
				$Item .= "</tr>";
				$Item .= "</table>";
				
				$HTMLMessage[] = showBlock($Item);
			}
					
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

//echo '['. count($HTMLMessage) .']';

if(count($HTMLMessage) > 0 AND !isset($_REQUEST['id'])) {
	$FooterText = "<a href='http://www.funda.nl/'>funda.nl</a>";
	include('include/HTML_TopBottom.php');
			
	$omslag = round(count($HTMLMessage)/2);
	$KolomEen = array_slice ($HTMLMessage, 0, $omslag);
	$KolomTwee = array_slice ($HTMLMessage, $omslag, $omslag);
	
	$HTMLMail = $HTMLHeader;
	
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
	
	$HTMLMail .= $HTMLPreFooter;
	$HTMLMail .= $HTMLFooter;
		
	$mail = new PHPMailer;
	$mail->From     = $ScriptMailAdress;
	$mail->FromName = $ScriptTitle;
	$mail->WordWrap = 90;
	$mail->AddAddress($ScriptMailAdress, 'Matthijs');
	$mail->Subject	= $SubjectPrefix. count($HTMLMessage) ." ". (count($HTMLMessage) == 1 ? 'verkocht huis' : 'verkochte huizen');
	$mail->IsHTML(true);
	$mail->Body			= $HTMLMail;
	
	if(!$mail->Send()) {
		echo "Versturen van mail is mislukt<br>";
		toLog('error', '', '', "Fout met mail nav verkochte huizen");			
	} else {
		toLog('info', '', '', "Mail nav verkochte huizen verstuurd");
	}
}


?>