<?php
include_once(__DIR__.'/../include/config.php');
include_once('../include/HTML_TopBottom.php');
$db = connect_db();

$query = "SELECT * FROM $TableHuizen WHERE $HuizenStraat like '' ORDER BY $HuizenEind DESC";
$result = mysqli_query($db, $query);
$row = mysqli_fetch_array($result);

do {
	$id = $row[$HuizenID];
	$data = getFundaData($id);
	$adres = splitStreetAndNumberFromAdress($data['adres']);

	$straat			= $adres['straat'];
	$nummer 		= $adres['nummer'];
	$letter			= $adres['letter'];
	$toevoeging	= $adres['toevoeging'];

	$sql_update = "UPDATE $TableHuizen SET $HuizenStraat = '". urlencode($adres['straat']) ."', $HuizenNummer = '". $adres['nummer'] ."', $HuizenLetter = '". urlencode($adres['letter']) ."', $HuizenToevoeging = '". $adres['toevoeging'] ."' WHERE $HuizenID = $id";
	mysqli_query($db, $sql_update);	
	
	echo $sql_update .'<br>';
	
} while ($row = mysqli_fetch_array($result));
