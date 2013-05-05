<?php
include_once('../../general_include/general_functions.php');
include_once('../../general_include/general_config.php');
include_once('../include/functions.php');
include_once('../include/config.php');
connect_db();

$XML[] = "<?xml version=\"1.0\" encoding=\"iso-8859-1\"?>";
$XML[] = "<pois>";
$XML[] = "  <!-- Specific info for PoiEdit -->";
$XML[] = "  <poiedit>";
$XML[] = "    <!-- Minimal version that is required -->";
$XML[] = "    <version>2.7.3</version>";
$XML[] = "  </poiedit>";

$Opdrachten = getZoekOpdrachten(1);
foreach($Opdrachten as $OpdrachtID) {
	$OpdrachtData = getOpdrachtData($OpdrachtID);

	$XML[] = "  <poi>";
	$XML[] = "    <description>". str_replace("&", '-', $OpdrachtData['naam']) ."</description>";
	$XML[] = "    <url>". $ScriptURL ."extern/makeOV2.php?opdracht=". $OpdrachtID ."</url>";
	$XML[] = "    <map>Nederland</map>";
	$XML[] = "    <image>". $ScriptURL ."extern/funda_logo.bmp</image>";
	$XML[] = "  </poi>";
}

$Lijsten = getLijsten(1);
foreach($Lijsten as $lijst) {
	$LijstData		= getLijstData($lijst);

	$XML[] = "  <poi>";
	$XML[] = "    <description>". str_replace("&", '-', $LijstData['naam']) ."</description>";
	$XML[] = "    <url>". $ScriptURL ."extern/makeOV2.php?lijst=". $lijst ."</url>";
	$XML[] = "    <map>Nederland</map>";
	$XML[] = "    <image>". $ScriptURL ."extern/funda_logo.bmp</image>";
	$XML[] = "  </poi>";
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