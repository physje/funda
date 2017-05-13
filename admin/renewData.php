<?php
include_once(__DIR__.'/../include/config.php');
include_once('../include/HTML_TopBottom.php');
setlocale(LC_ALL, 'nl_NL');
$minUserLevel = 3;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");
connect_db();

if(isset($_REQUEST['id'])) {
	$dataset = array($_REQUEST['id']);
	$showDetails = true;
} elseif(isset($_REQUEST['selectie'])) {
	$showDetails = false;
	$groep	= substr($_REQUEST['selectie'], 0, 1);
	$id			= substr($_REQUEST['selectie'], 1);	
	
	if($groep == 'Z') {		
		$opdrachtData	= getOpdrachtData($id);
		$Name					= $opdrachtData['naam'];
		$dataset			= getHuizen($id, false, true);
	} else {
		$LijstData		= getLijstData($id);
		$Name					= $LijstData['naam'];
		$dataset			= getLijstHuizen($id, false, true);
	}
}

foreach($dataset as $fundaID) {
	set_time_limit (60);
	$oldData			= getFundaData($fundaID);
	$oldExtraData = getFundaKenmerken($fundaID);
	
	if($oldData['verkocht'] == 1) {
		$URL					= "http://www.funda.nl". changeURLLocation($oldData['url']);
	} else {
		$URL					= "http://www.funda.nl". trim($oldData['url']);
	}
		
	$allData			= extractDetailedFundaData($URL, true);
	
	$newData			= $allData[0];
	$newExtraData	= $allData[1];
	$newData['id'] = $fundaID;
	
	$HTML[] = "<a href='$URL'>". $oldData['adres'] ."</a><br>";
			
	if($showDetails) {		
		$HTML[] = "<table>";
		$HTML[] = "<tr><td width=10%>&nbsp;</td><td width=45%><b>Oud</b></td><td width=45%><b>Nieuw</b></td></tr>";	
		
		foreach($oldData as $key => $value) {
			$HTML[] = "<tr><td valign='top'>$key</td><td valign='top'>$value</td><td valign='top'>". ($newData[$key] == $value ? $newData[$key] : '<b>'.$newData[$key].'</b>') ."</td></tr>";
		}
		
		$HTML[] = "<tr><td colspan=3>&nbsp;</td></tr>";
		
		foreach($oldExtraData as $key => $value) {
			$HTML[] = "<tr><td valign='top'>$key</td><td valign='top'>$value</td><td valign='top'>". ($newExtraData[$key] == $value ? $newExtraData[$key] : '<b>'.$newExtraData[$key].'</b>') ."</td></tr>";
		}
		$HTML[] = "</table>";
	}
	
	updateHouse($newData, $newExtraData);
	addCoordinates($newData['adres'], $newData['PC_c'], $newData['plaats'], $newData['id']);
	updatePrice($newData['id'], $newData['prijs'], time());
	if($newData['verkocht'] != 1) {
		updateAvailability($newData['id']);
	}
	toLog('info', '', $fundaID, 'Data opnieuw ingeladen');
}

echo $HTMLHeader;
echo "<tr>\n";
echo "<td width='8%'>&nbsp;</td>\n";
echo "<td width='84%' valign='top' align='center'>\n";
echo showBlock(implode("\n", $HTML));
echo "</td>\n";
echo "<td width='8%'>&nbsp;</td>\n";
echo "</tr>\n";
echo "</tr>\n";
echo $HTMLFooter;