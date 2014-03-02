<?php
include_once('../../general_include/general_functions.php');
include_once('../../general_include/general_config.php');
include_once('../include/functions.php');
include_once('../include/config.php');
connect_db();

# Omdat deze via een cronjob door de server wordt gedraaid is deze niet beveiligd
# Iedereen kan deze pagina dus in principe openen.

$grens	= time() - (2*24*60*60);
//$sql		= "SELECT $TableHuizen.$HuizenID, $TableHuizen.$HuizenURL, $TableHuizen.$HuizenAdres, $TableHuizen.$HuizenPlaats, $TableHuizen.$HuizenEind FROM $TableResultaat, $TableHuizen, $TableZoeken WHERE $TableResultaat.$ResultaatID = $TableHuizen.$HuizenID AND $TableResultaat.$ResultaatZoekID = $TableZoeken.$ZoekenKey AND $TableZoeken.$ZoekenActive like '1' AND $TableHuizen.$HuizenOffline = '0' AND $TableHuizen.$HuizenVerkocht = '0' AND $TableHuizen.$HuizenEind < $grens GROUP BY $TableHuizen.$HuizenID";

$sql_array[] = "SELECT * ";
$sql_array[] = "FROM $TableVerdeling, $TableHuizen, $TableResultaat ";
$sql_array[] = "WHERE ";
$sql_array[] = "$TableResultaat.$ResultaatZoekID = $TableVerdeling.$VerdelingOpdracht AND ";
$sql_array[] = "$TableResultaat.$ResultaatID = $TableHuizen.$HuizenID AND ";
$sql_array[] = "$TableHuizen.$HuizenVerkocht like '0' AND";
$sql_array[] = "$TableHuizen.$HuizenOffline like '0' AND";
$sql_array[] = "$TableHuizen.$HuizenEind < $grens";
$sql_array[] = "GROUP BY $TableHuizen.$HuizenID";

$sql = implode(" ", $sql_array);
$result	= mysql_query($sql);

//echo implode("<br>\n", $sql_array);

toLog('info', '', '', "Start controle offline huizen");

if($row = mysql_fetch_array($result)) {
	do {
		$fundaID	= $row[$HuizenID];
		$url			= "http://www.funda.nl". urldecode($row[$HuizenURL]);
		$contents = file_get_contents_retry($url);
		
		echo date("d-m-Y H:i", $row[$HuizenEind]). ' <b>'. urldecode($row[$HuizenAdres]) ."</b>, ". $row[$HuizenPlaats] ." (<a href='HouseDetails.php?id=$fundaID' target='_blank'>edit</a>, <a href='$url' target='_blank'>funda</a>)<br>";
		
		if(!is_string($contents)) {
			$sql_update = "UPDATE $TableHuizen SET $HuizenOffline = '1' WHERE $HuizenID like $fundaID";
			if(mysql_query($sql_update)) {
				echo " -> offline gehaald<br>";
				toLog('info', '', $fundaID, "Pagina is offline gehaald");
			} else {
				toLog('error', '', $fundaID, "Pagina kon niet offline gehaald worden");
			}
		}		
		echo "\n<br>\n";		
	} while($row = mysql_fetch_array($result));
}
?>		