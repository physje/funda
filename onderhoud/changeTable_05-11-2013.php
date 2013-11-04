<?php
include_once('../../general_include/general_functions.php');
include_once('../../general_include/general_config.php');
include_once('../include/functions.php');
include_once('../include/config.php');

connect_db();

$sql_table = "CREATE TABLE IF NOT EXISTS `$TablePBK` (`$PBKStart` int(11) NOT NULL, `$PBKEind` int(11) NOT NULL, `$PBKWaarde` decimal(4,1) NOT NULL, `$PBKComment` text NOT NULL);";
mysql_query($sql_table);

?>