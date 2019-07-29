<?php
include_once(__DIR__.'/../include/config.php');
include_once('../include/HTML_TopBottom.php');
setlocale(LC_ALL, 'nl_NL');
$minUserLevel = 2;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");
connect_db();

$huisID = $_REQUEST['fundaID'];

$data = getFundaData($huisID);

if(!addCoordinates($data['adres'], '', $data['plaats'], $huisID)) {
	echo 'Mislukt';
} else {
	echo 'Gelukt';
}

?>