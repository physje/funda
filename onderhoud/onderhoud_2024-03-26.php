<?php
include_once('../include/config.php');

$db = connect_db();

$sql = "ALTER TABLE `$TableHuizen` MODIFY `$HuizenVerkocht` SET ('0', '1', '2', '3')";
mysqli_query($db, $sql);

?>