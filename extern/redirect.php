<?php
include_once('../../general_include/general_functions.php');
include_once('../../general_include/general_config.php');
include_once('../include/functions.php');
include_once('../include/config.php');
connect_db();

$data = getFundaData($_REQUEST['id']);

//$redirect = "http://maps.google.nl/maps?q=". $data['N_deg'] .".". $data['N_dec'] .",". $data['O_deg'] .".". $data['O_dec'] ."&z=15";
$redirect = "http://maps.google.nl/maps?q=". $data['adres'] ."@". $data['lat'] .",". $data['long'] ."&z=15";

$url="Location: ". $redirect;
header($url);

?>