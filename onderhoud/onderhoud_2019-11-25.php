<?php
include_once('../include/config.php');

$db = connect_db();

$sql = "CREATE TABLE $TableWijken ($WijkenID int(3) AUTO_INCREMENT PRIMARY KEY, $WijkenActive set('0','1') NOT NULL DEFAULT '1', $WijkenLeesbaar text NOT NULL, $WijkenFunda text NOT NULL, $WijkenStad text NOT NULL, $WijkenLastCheck int(11) NOT NULL)";
mysqli_query($db, $sql);

$sql_fill = "SELECT * FROM $TableHuizen WHERE $HuizenOffline = '0' AND $HuizenWijk NOT like '' GROUP BY $HuizenWijk, $HuizenPlaats";
$result_fill = mysqli_query($db, $sql_fill);
$row_fill = mysqli_fetch_array($result_fill);

do {
	addUpdateWijkDb(urldecode($row_fill[$HuizenWijk]), urldecode($row_fill[$HuizenPlaats]));
} while($row_fill = mysqli_fetch_array($result_fill));


?>