<?php
include_once(__DIR__.'/../include/config.php');
# $minUserLevel = 1;
# $cfgProgDir = '../auth/';
# include($cfgProgDir. "secure.php");
connect_db();

$tijdGrens = mktime(0,0,0,date("n")-1, date("d"), date("Y"));
$langTeKoopGrens = (time() - mktime(0,0,0,date("n"), date("d"), date("Y")-4));

$IDs = array(999, 998, 997, 996);
$namen = array('Open huizen', 'Afgelopen maand online', 'Afgelopen maand afgemeld', 'Lang te koop');
$query = array(
	"SELECT * FROM $TableHuizen WHERE $HuizenOpenHuis like '1'",
	"SELECT * FROM $TableHuizen WHERE $HuizenStart > $tijdGrens",
	"SELECT * FROM $TableHuizen WHERE $HuizenAfmeld > $tijdGrens AND $HuizenOffline like '0'",
	"SELECT $HuizenID, ($HuizenEind - $HuizenStart) AS tijdsduur FROM $TableHuizen HAVING tijdsduur > $langTeKoopGrens"
);

for($i = 0 ; $i < count($IDs) ; $i++) {
	$LijstID = $IDs[$i];
	$data = getLijstData($LijstID);
	
	if($data['id'] != $LijstID) {
		$sql_insert = "INSERT INTO $TableList ($ListID) VALUES ($LijstID)";
		mysql_query($sql_insert);
		saveUpdateList($LijstID, $_SESSION['UserID'], 1, $namen[$i]);		
	} else {
		$sql_delete = "DELETE FROM $TableListResult WHERE $ListResultList like $LijstID";
		if(!mysql_query($sql_delete)) {
			echo $sql_delete;
		}
	}
	
	$sql_toevoegen = $query[$i];	
	$result = mysql_query($sql_toevoegen);
	$row = mysql_fetch_array($result);

	do {
		$Page_1 .= addHouse2List($row[$HuizenID], $LijstID);
	} while($row = mysql_fetch_array($result));
}

toLog('info', '', '', 'Standaard lijsten aangemaakt');