<?php
include_once('../../general_include/general_functions.php');
include_once('../../general_include/general_config.php');
include_once('../include/functions.php');
include_once('../include/config.php');

connect_db();

$sql_huizen = "ALTER TABLE `$TableResultaat` CHANGE `$ResultaatVerkocht` `$ResultaatVerkocht` SET('0', '1', '2') NOT NULL DEFAULT '0'";
mysql_query($sql_huizen);

?>