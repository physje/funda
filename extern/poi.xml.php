<?php
include_once('../../general_include/general_functions.php');
include_once('../../general_include/general_config.php');
include_once('../include/functions.php');
include_once('../include/config.php');
connect_db();

$XML[] = "<?xml version=\"1.0\" encoding=\"iso-8859-1\"?>";
$XML[] = "<pois>";
$XML[] = "  <infourl>$ScriptURL</infourl>";

$sql		= "SELECT * FROM $TableZoeken GROUP BY $ZoekenUser";
$result	= mysql_query($sql);

while($row = mysql_fetch_array($result)) {
	$userID = $row[$ZoekenUser];          
	$data = getMemberDetails($userID);
	
	$Opdrachten = getZoekOpdrachten($userID,'');
	
	foreach($Opdrachten as $OpdrachtID) {
		$OpdrachtData = getOpdrachtData($OpdrachtID);
	
		$XML[] = "  <poi>";
		$XML[] = "    <description><![CDATA[". $OpdrachtData['naam'] ."]]></description>";
		$XML[] = "    <url>". $ScriptURL ."extern/makeOV2.php?opdracht=". $OpdrachtID ."&amp;user=%Username%&amp;pass=%Password%</url>";
		$XML[] = "    <map>Nederland</map>";
		$XML[] = "    <group>". $data['naam'] ."</group>";
		$XML[] = "    <image>". $ScriptURL ."extern/funda_logo.bmp</image>";
		$XML[] = "    <authorization>Registered</authorization>";
		$XML[] = "  </poi>";
	}
}

$sql		= "SELECT * FROM $TableList GROUP BY $ListUser";
$result	= mysql_query($sql);

while($row = mysql_fetch_array($result)) {
	$userID = $row[$ListUser];          
	$data = getMemberDetails($userID);

	$Lijsten = getLijsten($userID,1);
	foreach($Lijsten as $lijst) {
		$LijstData		= getLijstData($lijst);
	
		$XML[] = "  <poi>";
		$XML[] = "    <description><![CDATA[". $LijstData['naam'] ."]]></description>";
		$XML[] = "    <url>". $ScriptURL ."extern/makeOV2.php?lijst=". $lijst ."&amp;user=%Username%&amp;pass=%Password%</url>";
		$XML[] = "    <map>Nederland</map>";
		$XML[] = "    <group>". $data['naam'] ."</group>";
		$XML[] = "    <image>". $ScriptURL ."extern/funda_logo.bmp</image>";
		$XML[] = "    <authorization>Registered</authorization>";
		$XML[] = "  </poi>";
	}
}

$XML[] = "</pois>";

header("Expires: Mon, 26 Jul 2001 05:00:00 GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false); 
header("Pragma: no-cache");
header("Cache-control: private");
header('Content-type: application/xml');
header('Content-Disposition: attachment; filename="poi.xml"');
echo implode("\n", $XML);

?>