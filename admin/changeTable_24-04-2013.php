<?php
include_once('../../general_include/general_functions.php');
include_once('../../general_include/general_config.php');
include_once('../include/functions.php');
include_once('../include/config.php');

connect_db();

$sql_table = "ALTER TABLE $TableHuizen ADD `$HuizenLat` FLOAT(10,6) NOT NULL AFTER `$HuizenOdec`, ADD `$HuizenLon` FLOAT(10,6) NOT NULL AFTER `$HuizenLat`";
mysql_query($sql_table);

$sql = "SELECT * FROM `funda_huizen`";
$result = mysql_query($sql);
if($row = mysql_fetch_array($result)) {
	do {
		$funda_id = $row['funda_id'];
		$coord[0] = $row['N_deg'];
		$coord[1] = $row['N_dec'];
		$coord[2] = $row['O_deg'];
		$coord[3] = $row['O_dec'];
		
		if(!addKnowCoordinates($coord, $funda_id)) {
			echo 'Toevoegen van coordinaten van '. $funda_id .' ging niet goed<br>';
		}		
	} while($row = mysql_fetch_array($result));
}

$sql_table = "ALTER TABLE $TableHuizen DROP `$HuizenNdeg`, DROP `$HuizenNdec`, DROP `$HuizenOdeg`, DROP `$HuizenOdec`";
mysql_query($sql_table);

?>