<?php
include_once('../include/config.php');

$db = connect_db();

$sql = "ALTER TABLE `$TableHuizen` ADD `$HuizenDetails` SET('0','1') NOT NULL DEFAULT '0' AFTER `$HuizenOpenHuis`";
mysql_query($sql);

?>