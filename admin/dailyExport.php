<?php
include_once(__DIR__. '../include/config.php');
include_once('../include/HTML_TopBottom.php');
connect_db();

$HuizenArray = array();
$Opdrachten = getZoekOpdrachten(1, '');

foreach($Opdrachten as $opdracht) {
	$Huizen = getHuizen($opdracht);
	
	foreach($Huizen as $huis) {
		if(!in_array($huis, $HuizenArray)) {
			$HuizenArray[] = $huis;
		}
	}
}

$fp = fopen($cfgXLSFilename, "w+");
fwrite($fp, createXLS($cfgCSVExport, $cfgPrefixExport, $HuizenArray, "	"));
fclose($fp);
?>