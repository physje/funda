<?php
include_once(__DIR__.'/../include/config.php');
connect_db();

# Omdat deze via een cronjob door de server wordt gedraaid is deze niet beveiligd
# Iedereen kan deze pagina dus in principe openen.

# Huizen die 2 dagen niet gezien zijn staan op de nominatie om mogelijk offline te zijn
$grens_offline	= time() - (2*24*60*60);

# Hetzelfde geldt voor verkochte huizen die langer dan 13 maanden geleden verkocht zijn
$grens_verkocht	= mktime(0,0,0,(date("n")-13),date("j"),date("Y"));

$sql_array[] = "SELECT * ";
$sql_array[] = "FROM $TableHuizen, $TableResultaat ";
$sql_array[] = "WHERE ";
$sql_array[] = "$TableResultaat.$ResultaatID = $TableHuizen.$HuizenID AND ";
$sql_array[] = "(($TableHuizen.$HuizenVerkocht NOT like '1' AND $TableHuizen.$HuizenOffline NOT like '1' AND $TableHuizen.$HuizenEind < $grens_offline) OR ";
$sql_array[] = "($TableHuizen.$HuizenVerkocht like '1' AND $TableHuizen.$HuizenOffline NOT like '1' AND $TableHuizen.$HuizenEind < $grens_verkocht))";
$sql_array[] = "$TableHuizen.$HuizenVerkocht NOT like '1' AND $TableHuizen.$HuizenOffline NOT like '1' AND $TableHuizen.$HuizenEind < $grens_offline ";
$sql_array[] = "GROUP BY $TableHuizen.$HuizenID";

$sql = implode(" ", $sql_array);
$result	= mysql_query($sql);

toLog('info', '', '', "Start controle offline huizen");

# Als hij een pagina opvraagt die niet bestaat krijg je veel errors/warnings.
# Dat is niet handig, dus even onderdrukken
error_reporting(0);

if($row = mysql_fetch_array($result)) {
	do {
		$fundaID	= $row[$HuizenID];
		$url			= "https://www.funda.nl". urldecode($row[$HuizenURL]);
		
		echo date("d-m-Y H:i", $row[$HuizenEind]). ' <b>'. urldecode($row[$HuizenAdres]) ."</b>, ". $row[$HuizenPlaats] ." (<a href='HouseDetails.php?id=$fundaID' target='_blank'>edit</a>, <a href='$url' target='_blank'>funda</a>)<br>";
				
		$contents = file_get_contents_retry($url);
									
		if(!is_string($contents) OR strpos($contents, '<title>Dit huis kunnen we niet vinden</title>')) {
			$sql_update = "UPDATE $TableHuizen SET $HuizenOffline = '1' WHERE $HuizenID like $fundaID";
			if(mysql_query($sql_update)) {
				echo " -> offline gehaald<br>";
				toLog('info', '', $fundaID, "Pagina is offline gehaald");
			} else {
				toLog('error', '', $fundaID, "Pagina kon niet offline gehaald worden");
			}
		}		
		echo "\n<br>\n";		
		sleep(3);
	} while($row = mysql_fetch_array($result));
}
