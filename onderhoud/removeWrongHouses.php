<?php
include_once('../../general_include/general_functions.php');
include_once('../../general_include/general_config.php');
include_once('../include/functions.php');
include_once('../include/config.php');

connect_db();

$sql = "SELECT $HuizenID FROM $TableHuizen WHERE $HuizenAdres like  '%this.title%' OR $HuizenPlaats like '%3C%2Ful%' AND $HuizenOffline = '0' LIMIT 0,10";
$result = mysql_query($sql);
$row = mysql_fetch_array($result);

//echo $sql;

do {
	$fundaData	= getFundaData($row[$HuizenID]);
	//$data				= extractDetailedFundaData('http://www.funda.nl'.$fundaData['url']);
	$URL				= 'http://www.funda.nl'.$fundaData['url'];	
	$contents		= file_get_contents_retry($URL);
	$adres			= getString('<h1>', '</h1>', $contents, 0);
	$PC_1				= getString('<p>', ' ', $contents, 0);
	$PC_2				= getString(' ', ' ', $PC_1[1], 0);
	$Plaats			= getString(' ', '</p>', $PC_2[1], 0);	
	
	if(strpos($adres[0], '</span>')) {
		$adres_new = getString('</span>', '', $adres[0], 0);
	} else {
		$adres_new = $adres;
	}		
	
	if(urlencode(trim($adres_new[0])) != '') {
		$sql_2 = "UPDATE $TableHuizen SET $HuizenAdres = '". urlencode(trim($adres_new[0])) ."', $HuizenPC_c = '". urlencode($PC_1[0]) ."', $HuizenPC_l = '". urlencode($PC_2[0]) ."', $HuizenPlaats = '". urlencode($Plaats[0]) ."' WHERE $HuizenID=". $row[$HuizenID];
		$result_2 = mysql_query($sql_2);
		
		//echo count($data).'<br>';	
		echo 'http://www.funda.nl'.$fundaData['url'].' -> '. $adres_new[0] .'|'. $PC_1[0] .'|'. $PC_2[0] .'|'. $Plaats[0] ."<br>\n";
	}
		
	sleep(3);
} while($row = mysql_fetch_array($result));
	

?>