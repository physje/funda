<?php
include_once(__DIR__.'/../include/config.php');
connect_db();

$output = $ov2output = "";
$grens	= time() - (24*60*60);

if(isset($_REQUEST['opdracht'])) {
	$id						= $_REQUEST['opdracht'];
	$opdrachtData	= getOpdrachtData($id);
	$Name					= $opdrachtData['naam'];
	$dataset			= getHuizen($id, true);
	$tables				= "$TableZoeken, $TableUsers";
	$sql_postfix	= "$TableUsers.$UsersID = $TableZoeken.$ZoekenUser AND $TableZoeken.$ZoekenKey = $id";
} else {
	$id						= $_REQUEST['lijst'];
	$LijstData		= getLijstData($id);
	$Name					= $LijstData['naam'];
	$dataset			= getLijstHuizen($id, true);
	$tables				= "$TableList, $TableUsers";
	$sql_postfix	= "$TableUsers.$UsersID = $TableList.$ListUser AND $TableList.$ListID = $id";
}

# Kijken of de combinatie user/pass en lijst/opdracht een geldige combi is
$sql = "SELECT * FROM $tables WHERE $TableUsers.$UsersUsername like '". $_REQUEST['user'] ."' AND $TableUsers.$UsersPassword like '". md5($_REQUEST['pass']) ."' AND ". $sql_postfix;
$result = mysql_query($sql);
if(mysql_num_rows($result) == 0) {
	$dataset = array();
	exit;
}

foreach($dataset as $huisID) {
	$data 			= getFundaData($huisID);
	$name				= convertToReadable($data['adres']) ."; ". $data['plaats'].'; '. formatPrice(getHuidigePrijs($huisID), false);	
	$lat				= explode('.', $data['lat']);
	$long				= explode('.', $data['long']);	
	$latitude		= $lat[0].substr($lat[1].'00000', 0, 5);
	$longitude	= $long[0].substr($long[1].'00000', 0, 5);	
	
	$ov2part = pack("VV", $longitude, $latitude).$name.chr(0);
	$ov2output .= chr(2).pack("V", strlen($ov2part)+5).$ov2part;
}

header("Expires: Mon, 26 Jul 2001 05:00:00 GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false); 
header("Pragma: no-cache");
header("Cache-control: private");
header('Content-type: application/ov2');
header('Content-Disposition: attachment; filename="funda_'. str_replace(" ", "", ucwords($Name)) .'.ov2"');
print $ov2output;