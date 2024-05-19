<?php
include_once('../include/config.php');

$db = connect_db();

$sql[] = "DROP $TableVerdeling";
$sql[] = "DELETE FROM $TableAbo WHERE $AboZoekID = 0";
$sql[] = "ALTER TABLE $TableZoeken ADD $ZoekenActive SET('0', '1') NOT NULL DEFAULT '1' AFTER $ZoekenKey";
$sql[] = "CREATE TABLE $TablePage ($PageOpdracht tinyint(4) NOT NULL, $PagePage tinyint(4) NOT NULL, $PageSold tinyint(1) NOT NULL DEFAULT 0, $PageTime int(11) NOT NULL)";

foreach($sql as $query) {
	mysqli_query($db, $query);
}

?>