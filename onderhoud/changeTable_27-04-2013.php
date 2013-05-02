<?php
include_once('../../general_include/general_functions.php');
include_once('../../general_include/general_config.php');
include_once('../include/functions.php');
include_once('../include/config.php');

connect_db();

$sql_table = "ALTER TABLE $TableResultaat ADD `$ResultaatVerkocht` SET( '0', '1' ) NOT NULL DEFAULT '0'";
mysql_query($sql_table);

$Opdrachten = getZoekOpdrachten('');

foreach($Opdrachten as $opdracht) {
	$sql_verkocht = "UPDATE $TableHuizen, $TableResultaat SET $TableResultaat.$ResultaatVerkocht = '1' WHERE $TableHuizen.$HuizenVerkocht like '1' AND $TableResultaat.$ResultaatVerkocht like '0' AND $TableResultaat.$ResultaatZoekID like '$opdracht' AND $TableResultaat.$ResultaatID = $TableHuizen.$HuizenID";
	mysql_query($sql_verkocht);
}

?>