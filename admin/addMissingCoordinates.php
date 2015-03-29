<?php
include_once(__DIR__. '../include/config.php');

$minUserLevel = 3;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");
connect_db();

$sql = "SELECT $HuizenID FROM $TableHuizen WHERE $HuizenLat = 0 OR $HuizenLon = 0";
$result = mysql_query($sql);
if($row = mysql_fetch_array($result)) {
	do {
		$data = getFundaData($row[$HuizenID]);
		if(addCoordinates($data['adres'], $data['PC_c'], $data['plaats'], $row[$HuizenID])) {
			toLog('debug', '', $row[$HuizenID], "Coordinaten toegevoegd");
			echo $row[$HuizenID] .'|';
		}
	} while($row = mysql_fetch_array($result));
}