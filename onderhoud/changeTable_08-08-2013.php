<?php
include_once('../../general_include/general_functions.php');
include_once('../../general_include/general_config.php');
include_once('../include/functions.php');
include_once('../include/config.php');

connect_db();

$sql_huizen			= "ALTER TABLE $TableHuizen ADD `$HuizenOpenHuis` SET( '0', '1' ) NOT NULL DEFAULT '0'";
mysql_query($sql_huizen);

$sql_resultaat	= "ALTER TABLE $TableResultaat ADD `$ResultaatOpenHuis` SET( '0', '1' ) NOT NULL DEFAULT '0'";
mysql_query($sql_resultaat);

$sql_kalendar		= "CREATE TABLE `$TableCalendar` (`$CalendarHuis` INT NOT NULL , `$CalendarStart` INT NOT NULL , `$CalendarEnd` INT NOT NULL )";
mysql_query($sql_kalendar);

# CREATE TABLE `draije1a_db_thijs`.`funda_kalender` (
# `huis` INT NOT NULL ,
# `start` INT NOT NULL ,
# `einde` INT NOT NULL
# ) ENGINE = MYISAM ;

?>
