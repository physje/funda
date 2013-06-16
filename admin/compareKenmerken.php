<?php
include_once('../../general_include/general_functions.php');
include_once('../../general_include/general_config.php');
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
$minUserLevel = 1;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");
connect_db();

if(isset($_POST['kolom'])) {	
	# Maak de de eerste regel aan
	$CSV_kop = array('');
	
	foreach($_POST['prefix'] as $prefix => $dummy) {
		$CSV_kop[] = $prefix;
	}
	
	foreach($_POST['kolom'] as $kenmerk => $dummy) {
		if($kenmerk == 'Achtertuin' || $kenmerk == 'Voortuin' || $kenmerk == 'Plaats') {
			$CSV_kop[] = $kenmerk;
			$CSV_kop[] = $kenmerk .' (diep)';
			$CSV_kop[] = $kenmerk .' (breed)';
		} else {
			$CSV_kop[] = $kenmerk;
		}
	}	
	$CSV[] = implode(';', $CSV_kop);
	
	# Doorloop alle huizen en geef de waarde van het kenmerk weer
	foreach($_POST['huizen'] as $huisID) {
		$data				= getFundaData($huisID);
		$kenmerken	= getFundaKenmerken($huisID);
		
		if($data['verkocht'] == 1) {
			$status = 'verkocht';
		} elseif($data['verkocht'] == 2) {
			$status = 'onder voorbehoud';
		} elseif($data['offline'] == 1) {
			$status = 'offline';	
		} else {
			$status = 'beschikbaar';
		}
		
		$CSV_regel = array($data['adres']);		
		if(array_key_exists('ID', $_POST['prefix']))							$CSV_regel[] = $huisID;
		if(array_key_exists('url', $_POST['prefix']))							$CSV_regel[] = 'http://www.funda.nl'.$data['url'];
		if(array_key_exists('Huidige Prijs', $_POST['prefix']))		$CSV_regel[] = getHuidigePrijs($huisID);
		if(array_key_exists('Orginele Prijs', $_POST['prefix']))	$CSV_regel[] = getOrginelePrijs($huisID);
		if(array_key_exists('Status', $_POST['prefix']))					$CSV_regel[] = $status;
		if(array_key_exists('Wijk', $_POST['prefix']))						$CSV_regel[] = $data['wijk'];
		
		foreach($_POST['kolom'] as $kenmerk => $dummy) {				
			$string = $kenmerken[$kenmerk];
			$string = str_replace('&nbsp;m&sup2;', '', $string);
			$string = str_replace('&nbsp;m&sup3;', '', $string);
			$string = str_replace('&#235;', 'ë', $string);
			$string = str_replace('&amp;', '&', $string);
			$string = html_entity_decode($string);
			
			if($kenmerk == 'Achtertuin' || $kenmerk == 'Voortuin' || $kenmerk == 'Plaats') {
				if(strlen($string) > 10) {
					$string = str_replace(' mÂ²', '', $string);
					$temp = getString('', '(', $string, 0);						$CSV_regel[] = trim($temp[0]);
					$temp = getString('(', 'm diep', $string, 0);			$CSV_regel[] = trim($temp[0]);
					$temp = getString('en ', 'm breed', $string, 0);	$CSV_regel[] = trim($temp[0]);
				} else {
					$CSV_regel[] = '';
					$CSV_regel[] = '';
					$CSV_regel[] = '';
				}
			} else {					
				$string = str_replace('m²', '', $string);
				$string = str_replace('m³', '', $string);
				$CSV_regel[] = trim($string);
			}
			
		}
		$CSV[] = implode(';', $CSV_regel);
	}
	
	header("Expires: Mon, 26 Jul 2001 05:00:00 GMT");
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false); 
	header("Pragma: no-cache");
	header("Cache-control: private");
	header('Content-type: application/csv');
	header('Content-Disposition: attachment; filename="'.  str_replace(' ', '_', strftime ('%Y.%m.%d-%H.%M')) .'-'. $_POST['Name'] .'.txt"');
	echo implode("\n", $CSV);
	
} elseif(isset($_REQUEST['selectie'])) {
	$groep	= substr($_REQUEST['selectie'], 0, 1);
	$id			= substr($_REQUEST['selectie'], 1);
	
	if($groep == 'Z') {		
		$opdrachtData	= getOpdrachtData($id);
		$Name					= $opdrachtData['naam'];
		$dataset			= getHuizen($id);
	} else {
		$LijstData		= getLijstData($id);
		$Name					= $LijstData['naam'];
		$dataset			= getLijstHuizen($id);
	}
		
	# Doorloop alle huizen
	foreach($dataset as $huisID) {
		$kenmerken = getFundaKenmerken($huisID);
		
		# Sommige huizen hebben maar weinig (lees : geen) kenmerken in de database.
		# Die toevoegen in het overzicht is zinloos
		if(count($kenmerken) > 2) {
			$huizen[] = $huisID;
			
			# Doorloop alle kenmerken van het huis
			# Een aantal kenmerken worden uitgesloten omdat die slechts een moment-opname zijn
			foreach($kenmerken as $key => $value) {
				$kolom[$key] = 1;
			}
		}
	}	
	
	# Zaken die wel belangrijk zijn om te weten, anders dan de kenmerken
	$prefix['ID'] = 1;
	$prefix['url'] = 1;
	$prefix['Huidige Prijs'] = 1;
	$prefix['Orginele Prijs'] = 1;
	$prefix['Status'] = 1;
	$prefix['Wijk'] = 1;
	
	$HTML[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
	$HTML[] = "<input type='hidden' name='Name' value='$Name'>";
	foreach($huizen as $huisID) {	
		$HTML[] = "<input type='hidden' name='huizen[]' value='$huisID'>";
	}
	
	$HTML[] = "<table border=0>";
	$HTML[] = "<tr>";
	$counter = 1;
	
	foreach($prefix as $kenmerk => $waarde) {		
		$HTML[] = "	<td><input type='checkbox' name='prefix[$kenmerk]' value='1' checked> $kenmerk</td>";
		
		if($counter > 1) {
			$HTML[] = "</tr>";
			$HTML[] = "<tr>";
			$counter = 0;
		}
		$counter++;
	}
	
	foreach($kolom as $kenmerk => $waarde) {		
		$HTML[] = "	<td><input type='checkbox' name='kolom[$kenmerk]' value='1'". (in_array($kenmerk, $cfgCSVExport) ? ' checked' : '') ."> $kenmerk</td>";
		
		if($counter > 1) {
			$HTML[] = "</tr>";
			$HTML[] = "<tr>";
			$counter = 0;
		}
		$counter++;
	}
	
	$HTML[] = "</tr>";
	$HTML[] = "<tr>";
	$HTML[] = "	<td colspan='2' align='center'><input type='submit' name='submit' value='Weergeven'></td>";
	$HTML[] = "</tr>";
	$HTML[] = "</table>";
	$HTML[] = "</form>";	
} else {
	$HTML[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
	$HTML[] = "<table>";
	$HTML[] = "<tr>";
	$HTML[] = "	<td>Selectie</td>";	
	$HTML[] = "	<td>&nbsp;</td>";
	$HTML[] = "	<td>". makeSelectionSelection(isset($_REQUEST['addHouses']), false) ."</td>";
	$HTML[] = "</tr>";
	$HTML[] = "<tr>";
	$HTML[] = "	<td colspan='3' align='center'><input type='submit' name='submit' value='Weergeven'></td>";
	$HTML[] = "</tr>";
	$HTML[] = "</table>";
	$HTML[] = "</form>";
}

# Als er HTML-informatie te tonen is, doet dat dan
if(isset($HTML)) {
	echo $HTMLHeader;
	echo "<tr>\n";
	echo "<td width='15%' valign='top' align='center'>&nbsp;</td>\n";
	echo "<td width='80%' valign='top' align='center'>\n";
	echo showBlock(implode("\n", $HTML));
	echo "</td>\n";
	echo "<td width='15%' valign='top' align='center'>&nbsp;</td>\n";
	echo "</tr>\n";
	echo $HTMLFooter;
}
?>