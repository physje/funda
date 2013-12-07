<?php
include_once('../../general_include/general_functions.php');
include_once('../../general_include/general_config.php');
include_once('../include/functions.php');
include_once('../include/config.php');

connect_db();

$sql_table = "CREATE TABLE IF NOT EXISTS `$TableVerdeling` (`$VerdelingUur` int(2) NOT NULL, `$VerdelingOpdracht` int(3) NOT NULL);";
mysql_query($sql_table);

$sql = "SELECT * FROM $TableZoeken WHERE $ZoekenActive like '1'";
$result = mysql_query($sql);
$row = mysql_fetch_array($result);

do {
	$opdracht = $row[$ZoekenKey];
	mysql_query("INSERT INTO $TableVerdeling ($VerdelingUur, $VerdelingOpdracht) VALUES (9, $opdracht)");
	mysql_query("INSERT INTO $TableVerdeling ($VerdelingUur, $VerdelingOpdracht) VALUES (15, $opdracht)");
	mysql_query("INSERT INTO $TableVerdeling ($VerdelingUur, $VerdelingOpdracht) VALUES (21, $opdracht)");
} while($row = mysql_fetch_array($result));

$sql_drop = "ALTER TABLE $TableZoeken DROP `$ZoekenActive`";
mysql_query($sql_drop);

?>