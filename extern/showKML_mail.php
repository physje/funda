<?php
include_once(__DIR__. '../include/config.php');
connect_db();

$dag		= getParam('dag', date("d"));
$maand	= getParam('maand', date("m"));
$jaar		= getParam('jaar', date("Y"));
$regio	= getParam('regio', 1);
	
$BeginTijd	= mktime(0, 0, 0, $maand, $dag, $jaar);
$EindTijd		= mktime(23, 59, 59, $maand, $dag, $jaar);

$data = getOpdrachtData($regio);

$KMLTitle = 'Nieuwe huizen in '. $data['naam'] .' voor '. date("d-m-Y", $BeginTijd);
include('../include/KML_TopBottom.php');

$sql		= "SELECT * FROM $TableResultaat, $TableHuizen WHERE $TableResultaat.$ResultaatID = $TableHuizen.$HuizenID AND $TableResultaat.$ResultaatZoekID = $regio AND $TableHuizen.$HuizenStart BETWEEN $BeginTijd AND $EindTijd";
$result	= mysql_query($sql);
$row		= mysql_fetch_array($result);
do {
	$KML_file[] = makeKMLEntry($row[$HuizenID]);	
} while($row = mysql_fetch_array($result));
	
header("Expires: Mon, 26 Jul 2001 05:00:00 GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false); 
header("Pragma: no-cache");
header("Cache-control: private");
header('Content-type: application/kml');
header('Content-Disposition: attachment; filename="'.  str_replace(' ', '_', $data['naam'] .'-'. date("d_m-H\hi\m")) .'.kml"');
	
echo $KML_header.implode("\n", $KML_file).$KML_footer;