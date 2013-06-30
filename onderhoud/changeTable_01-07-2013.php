<?php
include_once('../../general_include/general_functions.php');
include_once('../../general_include/general_config.php');
include_once('../include/functions.php');
include_once('../include/config.php');

connect_db();

$sql_table = "ALTER TABLE `$TableHuizen` ADD `$HuizenMakelaar` TEXT NOT NULL AFTER `$HuizenThumb` ";
mysql_query($sql_table);


?>