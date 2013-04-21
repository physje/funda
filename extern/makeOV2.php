<?php
include_once('../../general_include/general_functions.php');
include_once('../../general_include/general_config.php');
include_once('../include/functions.php');
include_once('../include/config.php');
connect_db();

$output = $ov2output = "";
$grens	= time() - (24*60*60);

if(isset($_REQUEST['opdracht'])) {
	$opdracht			= $_REQUEST['opdracht'];
	$opdrachtData	= getOpdrachtData($opdracht);
	$Name					= $opdrachtData['naam'];
	$from					= "$TableResultaat, $TableHuizen";
	$where				= "$TableResultaat.$ResultaatID = $TableHuizen.$HuizenID AND $TableResultaat.$ResultaatZoekID = $opdracht AND $TableHuizen.$HuizenEind > $grens";		
} elseif(isset($_REQUEST['lijst'])) {
	$lijst				= $_REQUEST['lijst'];
	$LijstData		= getLijstData($lijst);
	$Name					= $LijstData['naam'];
	$from					= "$TableListResult, $TableHuizen";
	$where				= "$TableListResult.$ListResultHuis = $TableHuizen.$HuizenID AND $TableListResult.$ListResultList = $lijst AND $TableHuizen.$HuizenEind > $grens";
}

$sql = "SELECT $TableHuizen.$HuizenOffline, $TableHuizen.$HuizenThumb, $TableHuizen.$HuizenVerkocht, $TableHuizen.$HuizenAdres, $TableHuizen.$HuizenID, $TableHuizen.$HuizenURL FROM $from WHERE $where ORDER BY $TableHuizen.$HuizenAdres";

//$OpdrachtID = $_REQUEST['opdracht'];
//$OpdrachtData = getOpdrachtData($OpdrachtID);
//$grens	= time() - (24*60*60);
////$sql		= "SELECT * FROM $TableHuizen WHERE $HuizenEind > $grens AND $HuizenOpdracht = $OpdrachtID ORDER BY $HuizenAdres";
//
//$sql		= "SELECT * FROM $TableHuizen, $TableResultaat WHERE $TableResultaat.$ResultaatID = $TableHuizen.$HuizenID AND $TableResultaat.$ResultaatZoekID = $OpdrachtID AND $TableHuizen.$HuizenEind > $grens ORDER BY $TableHuizen.$HuizenAdres";
$result = mysql_query($sql);
$row		= mysql_fetch_array($result);

do {
	$Prijzen	= getPriceHistory($row[$HuizenID]);
	$temp			= each($Prijzen);
	$label		= $temp[0];	
	$lat			= $row[$HuizenNdeg].substr($row[$HuizenNdec].'00000', 0, 5);
	$long			= $row[$HuizenOdeg].substr($row[$HuizenOdec].'00000', 0, 5);
		
	$name = urldecode($row[$HuizenAdres]) ."; ". urldecode($row[$HuizenPlaats]).'; '. number_format($Prijzen[$label],0,',','.');
	
	$ov2part = pack("VV", $long, $lat).$name.chr(0);
	$ov2output .= chr(2).pack("V", strlen($ov2part)+5).$ov2part;
}
while($row = mysql_fetch_array($result));

header("Expires: Mon, 26 Jul 2001 05:00:00 GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false); 
header("Pragma: no-cache");
header("Cache-control: private");
header('Content-type: application/ov2');
header('Content-Disposition: attachment; filename="funda_'. str_replace(" ", "", ucwords($Name)) .'.ov2"');
print $ov2output;
?>