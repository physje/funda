<?php
include_once(__DIR__.'/include/config.php');
//include_once('include/HTML_TopBottom.php');
connect_db();

$fundaID = 40651832;

$data = getFundaData($fundaID);
$GoogleStraat = $data['adres'];
$postcode = '';
$plaats = $data['plaats'];

$coord = getCoordinates($GoogleStraat, $postcode, $plaats);

var_dump($coord);

?>