<?php
include_once('../../general_include/general_functions.php');
include_once('../../general_include/general_config.php');
include_once('../include/functions.php');
include_once('../include/config.php');

connect_db();

$sql = "ALTER TABLE $TableUsers ADD `$UsersPOKey` TEXT NOT NULL AFTER `$UsersAdres` , ADD `$UsersPOToken` TEXT NOT NULL AFTER `$UsersPOKey`";

mysql_query($sql);

?>