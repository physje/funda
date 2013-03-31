<?php
include_once('../../general_include/general_functions.php');
include_once('../../general_include/general_config.php');
include_once('../include/functions.php');
include_once('../include/config.php');
connect_db();

$grens	= time() - (2*24*60*60);
$sql		= "SELECT $TableHuizen.$HuizenID, $TableHuizen.$HuizenURL, $TableHuizen.$HuizenAdres FROM $TableResultaat, $TableHuizen, $TableZoeken WHERE $TableResultaat.$ResultaatID = $TableHuizen.$HuizenID AND $TableResultaat.$ResultaatZoekID = $TableZoeken.$ZoekenKey AND $TableZoeken.$ZoekenActive like '1' AND $TableHuizen.$HuizenOffline = '0' AND $TableHuizen.$HuizenEind < $grens GROUP BY $TableHuizen.$HuizenID";
$result	= mysql_query($sql);

//echo $sql;

if($row = mysql_fetch_array($result)) {
	do {
		$fundaID	= $row[$HuizenID];
		$url			= "http://www.funda.nl". urldecode($row[$HuizenURL]);
		$contents = file_get_contents_retry($url);
		
		echo '<b>'. urldecode($row[$HuizenAdres]) ."</b> (<a href='$url' target='_blank'>url</a>)<br>";
		
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
		