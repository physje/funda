<?php
include_once(__DIR__.'/../include/config.php');
include_once('../include/HTML_TopBottom.php');
$minUserLevel = 3;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");
connect_db();

if(!isset($_REQUEST['id'])) {
	$deel_1[] = "Onvoldoende gegevens bekend";
} elseif(isset($_REQUEST['save'])) {
	$huisID = $_REQUEST['id'];
	$opdrachten = $_REQUEST['opdracht'];

	$sql = "SELECT * FROM $TableResultaat WHERE $ResultaatID = $huisID";
	$result = mysql_query($sql);
	$row = mysql_fetch_array($result);
		
	do {
		$zoekID = $row[$ResultaatZoekID];
		if(!array_key_exists($zoekID, $opdrachten)) {
			$OpdrachtData = getOpdrachtData($zoekID);
			$deel_1[] = $OpdrachtData['naam'] .' verwijderd<br>';
			$sql_delete = "DELETE FROM $TableResultaat WHERE $ResultaatID = $huisID AND $ResultaatZoekID = $zoekID";
			mysql_query($sql_delete);
		}
		
		unset($opdrachten[$zoekID]);
	} while($row = mysql_fetch_array($result));
	
	foreach($opdrachten as $zoekID => $dummy) {
		$OpdrachtData = getOpdrachtData($zoekID);
		$deel_1[] = $OpdrachtData['naam'] .' toegevoegd<br>';
		$sql_insert = "INSERT INTO $TableResultaat ($ResultaatID, $ResultaatZoekID) VALUES ($huisID, $zoekID)";
		mysql_query($sql_insert);
	}	
} else {
	$data = getFundaData($_REQUEST['id']);
	$Opdrachten = getZoekOpdrachten($_SESSION['account'], '', false);
	
	$deel_1[] = 'Vink de opdrachten aan waar '. $data['adres'].' bij hoort <br>';
	$deel_1[] = "<form method='post'>";
	$deel_1[] = "<input type='hidden' name='id' value='". $_REQUEST['id'] ."'>";
	
	foreach($Opdrachten as $key) {
		$OpdrachtData = getOpdrachtData($key);
		$deel_1[] = "<input type='checkbox' name='opdracht[$key]' value='1'". (newHouse($_REQUEST['id'], $key) ? '' : ' checked') ."> ". $OpdrachtData['naam'] .'<br>';
	}
	
	$deel_1[] = "<input type='submit' name='save' value='Opslaan'>";
	$deel_1[] = "</form>";	
}

echo $HTMLHeader;
echo "<tr>\n";
echo "<td width='50%' valign='top' align='center'>\n";
echo showBlock(implode("\n", $deel_1));
echo "</td>\n";
echo "<td width='50%' valign='top' align='center'>\n";
echo "&nbsp;";
echo "</td>\n";
echo "</tr>\n";
echo $HTMLFooter;