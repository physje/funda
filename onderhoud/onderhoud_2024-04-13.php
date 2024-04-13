<?php
include_once('../include/config.php');

$db = connect_db();

$sql = "DELETE FROM $TablePBK WHERE $PBKRegio like 'Totaal'";	
mysqli_query($db, $sql);

?>