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

if(isset($_POST['huizen'])) {
	header("Expires: Mon, 26 Jul 2001 05:00:00 GMT");
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false); 
	header("Pragma: no-cache");
	header("Cache-control: private");
	header('Content-type: application/csv');
	header('Content-Disposition: attachment; filename="'.  str_replace(' ', '_', strftime ('%Y.%m.%d-%H.%M')) .'-'. $_POST['Name'] .'.txt"');
	echo createXLS($_POST['kenmerk'], $_POST['prefix'], $_POST['huizen']);	
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
	
	$HTML[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
	$HTML[] = "<input type='hidden' name='Name' value='$Name'>";
	foreach($huizen as $huisID) {	
		$HTML[] = "<input type='hidden' name='huizen[]' value='$huisID'>";
	}
	
	$HTML[] = "<table border=0>";
	$HTML[] = "<tr>";
	$counter = 1;
	
	foreach($cfgPrefixExport as $dummy => $kenmerk) {		
		$HTML[] = "	<td><input type='checkbox' name='prefix[]' value='$kenmerk' checked> $kenmerk</td>";
		
		if($counter > 1) {
			$HTML[] = "</tr>";
			$HTML[] = "<tr>";
			$counter = 0;
		}
		$counter++;
	}
	
	foreach($kolom as $waarde => $dummy) {		
		$HTML[] = "	<td><input type='checkbox' name='kenmerk[]' value='$waarde'". (in_array($waarde, $cfgCSVExport) ? ' checked' : '') ."> $waarde</td>";
		
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