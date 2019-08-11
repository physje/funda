<?php
include_once(__DIR__.'/../include/config.php');
$db = connect_db();

$data = getFundaData($_REQUEST['id']);

$redirect = "http://maps.google.nl/maps?q=". $data['adres'] ."@". $data['lat'] .",". $data['long'] ."&z=15";

$url="Location: ". $redirect;
header($url);