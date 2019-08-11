<?php
include_once('../include/config.php');

$db = connect_db();

$sql = "ALTER TABLE `$TableHuizen` ADD `$HuizenStraat` TEXT NOT NULL AFTER `$HuizenAdres`, ADD `$HuizenNummer` INT(4) NOT NULL AFTER `$HuizenStraat`, ADD `$HuizenLetter` TEXT NOT NULL AFTER `$HuizenNummer`, ADD `$HuizenToevoeging` INT(4) NOT NULL AFTER `$HuizenLetter`";
mysqli_query($db, $sql);

echo $sql;

?>