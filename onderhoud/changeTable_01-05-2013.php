<?php
include_once('../../general_include/general_functions.php');
include_once('../../general_include/general_config.php');
include_once('../include/functions.php');
include_once('../include/config.php');

connect_db();

$sql_table = "CREATE TABLE IF NOT EXISTS `$TableUsers` (`$UsersID` int(3) NOT NULL AUTO_INCREMENT, `$UsersName` text NOT NULL, `$UsersUsername` text NOT NULL, `$UsersPassword` text NOT NULL, `$UsersLevel` int(1) NOT NULL, `$UsersAdres` text NOT NULL, `$UsersAccount` int(3) NOT NULL, `$UsersLastLogin` int(11) NOT NULL, UNIQUE KEY `$UsersID` (`$UsersID`)) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
mysql_query($sql_table);

$sql_zoeken = "ALTER TABLE $TableZoeken ADD `$ZoekenUser` int(3) NOT NULL AFTER `$ZoekenKey`";
mysql_query($sql_zoeken);

$sql_list = "ALTER TABLE $TableList ADD `$ListUser` int(3) NOT NULL AFTER `$ListID`";
mysql_query($sql_list);

$sql_huizen = "ALTER TABLE `$TableHuizen` CHANGE `$HuizenVerkocht` `$HuizenVerkocht` SET('0', '1', '2') NOT NULL DEFAULT '0'";
mysql_query($sql_huizen);

$sql_insert = "INSERT INTO $TableUsers ($UsersID, $UsersName, $UsersUsername, $UsersPassword, $UsersLevel) VALUES ('1', 'Admin', 'admin', '". md5('admin') ."', 3)";
mysql_query($sql_insert);

$sql_update = "UPDATE $TableZoeken SET $ZoekenUser = '1'";
mysql_query($sql_update);

$sql_update = "UPDATE $TableList SET $ListUser = '1'";
mysql_query($sql_update);

?>