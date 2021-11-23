<?php
# Omdat deze via een cronjob door de server wordt gedraaid is deze niet beveiligd
# Iedereen kan deze pagina dus in principe openen.

# Site (drimble.nl) laat 200 requests per dag toe
# Als dit script dus elke uur, elke 13 minuten (*/13) draait, heb je in een dag 192 requests

include_once(__DIR__.'/../include/config.php');
include_once('../include/HTML_TopBottom.php');

$db = connect_db();

if(isset($_REQUEST['id'])) {
	$sql = "SELECT $HuizenID FROM $TableHuizen WHERE $HuizenID like ". $_REQUEST['id'];
	$opschonen = true;
} else {
	$sql = "SELECT $HuizenID FROM $TableHuizen h WHERE NOT EXISTS (SELECT $WOZFundaID FROM $TableWOZ w WHERE h.$HuizenID = w.$WOZFundaID) AND $HuizenDetails = '0' ORDER BY $HuizenEind DESC LIMIT 0,2";
	$opschonen = false;		
}
$result = mysqli_query($db, $sql);

# Als er om wat voor een reden dan ook geen resultaten zijn
# Waarschijnlijk omdat er geen ontbrekende huizen zijn
# Gaan wij gewoon de oude data verversen
if(mysqli_num_rows($result) == 0) {
	$sql = "SELECT $WOZFundaID FROM $TableWOZ ORDER BY $WOZLastCheck ASC LIMIT 0,2";	
	$result = mysqli_query($db, $sql);
	
	$opschonen = true;
	$refresh = true;
}

if($row = mysqli_fetch_array($result)) {
	do {
		if(isset($refresh)) {
			$fundaID = $row[$WOZFundaID];
		} else {
			$fundaID = $row[$HuizenID];
		}
		$data = getFundaData($fundaID);		
		$WOZwaardes = extractWOZwaarde($fundaID);
		
		$WOZ = current($WOZwaardes);
		$vraagprijs = getHuidigePrijs($fundaID);
		
		$string = array();
		$string[] = "<a href='http://www.funda.nl/$fundaID'>". $data['adres'] .'</a>';
		$string[] = 'WOZ : '.formatPrice($WOZ);
		$string[] = 'Vraagprijs :'. formatPrice($vraagprijs).' ('. round(100*$vraagprijs/$WOZ, 1).'%)';
		$string[] = '';
		
		# Array met waardes teruggekregen
		if(is_array($WOZwaardes)) {			
			# Mocht er om wat voor een reden dan ook keer een 0 zijn weggeschreven
			# Dan die nu verwijderen
			if($opschonen)	mysqli_query($db, "DELETE FROM $TableWOZ WHERE $WOZFundaID = $fundaID AND $WOZJaar = 0");
			
			foreach($WOZwaardes as $jaar => $waarde) {
				# Oude data verwijderen
				if($opschonen)	mysqli_query($db, "DELETE FROM $TableWOZ WHERE $WOZFundaID = $fundaID AND $WOZJaar = $jaar");
				
				$sql_insert = "INSERT INTO $TableWOZ ($WOZFundaID, $WOZJaar, $WOZPrijs, $WOZLastCheck) VALUES ($fundaID, $jaar, $waarde, ". time() .")";
				if(mysqli_query($db, $sql_insert)) {
					toLog('debug', '0', $fundaID, 'WOZ-waarde '. (isset($refresh) ? 'ververst' : 'toegevoegd') .'; '. $jaar .':'.$waarde);
				} else {
					toLog('error', '0', $fundaID, 'Kon WOZ-waarde niet wegschrijven; '. $jaar .':'.$waarde);
				}
			}
			
		# Geen array, maar boolean false teruggekregen
		} elseif(!$WOZwaardes) {
			# Oude data verwijderen
			if($opschonen)	mysqli_query($db, "DELETE FROM $TableWOZ WHERE $WOZFundaID = $fundaID AND $WOZJaar = 0");
			
			$sql_onbekend = "INSERT INTO $TableWOZ ($WOZFundaID, $WOZLastCheck) VALUES ($fundaID, ". time() .")";
					
			if(mysqli_query($db, $sql_onbekend)) {
				toLog('info', '0', $fundaID, 'WOZ-waarde kon niet '. (isset($refresh) ? 'ververst' : 'opgevraagd') .' worden');
			} else {
				toLog('error', '0', $fundaID, 'Wegschrijven van fout bij WOZ-waarde ging zelf ook fout');
			}
			
			# Stel, de WOZ-waarde is niet meer te vinden terwijl dat vorige keer nog wel zo was.
			# Dan moet de tijd van die check aangepast worden, anders blijft hij daar op hangen
			if($opschonen)	mysqli_query($db, "UPDATE $TableWOZ SET $WOZLastCheck = ". time() ." WHERE $WOZFundaID = $fundaID");
		}
				
		sleep(3);
		$block[] = implode('<br>', $string);
		
	} while($row = mysqli_fetch_array($result));
}

echo $HTMLHeader;
echo "<tr>\n";
echo "<td width='30%'>&nbsp;</td>";
echo "<td width='40%' valign='top' align='center'>";

foreach($block as $subBlock) {
	echo showBlock($subBlock);
	echo '<p>';
}

echo "</td>";
echo "<td width='30%'>&nbsp;</td>";
echo "</tr>\n";
echo $HTMLFooter;

?>
