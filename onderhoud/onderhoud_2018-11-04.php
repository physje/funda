<?php
include_once('../include/config.php');

$db = connect_db();

$sql = "ALTER TABLE `$TablePBK` ADD `$PBKRegio` TEXT NOT NULL AFTER `$PBKEind`";
mysql_query($sql);

$sql = "TRUNCATE TABLE `$TablePBK`";
mysql_query($sql);

echo "Laad PBK-data opnieuw in <a href='../admin/readKadasterPBK.php?type=regio' target='_blank'>run 1</a>, <a href='../admin/readKadasterPBK.php?type=alles' target='_blank'>run 2</a>";
echo "<p>";
echo "Laad vervolgens ook <a href='funda_gemeentes.sql.gz'>deze data</a> in";

?>