<?php
include_once('../include/config.php');

$db = connect_db();

$sql = "ALTER TABLE `$TablePBK` ADD `$PBKCategorie` TEXT NOT NULL AFTER `$PBKRegio`";
mysqli_query($db, $sql);

?>