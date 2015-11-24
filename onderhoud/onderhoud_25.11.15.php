<?php
include_once('../../general_include/general_functions.php');
include_once('../../general_include/general_config.php');
include_once('../include/functions.php');
include_once('../include/config.php');

connect_db();

$sql = "ALTER TABLE `$TableAbo` ADD `$AboType` SET('mail', 'push') NOT NULL DEFAULT 'mail' AFTER `$AboUserID`";
mysql_query($sql);

$sql = "UPDATE `$TableAbo` SET `$AboType` = 'mail'";
mysql_query($sql);

?>