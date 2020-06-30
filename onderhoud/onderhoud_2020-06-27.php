<?php
include_once('../include/config.php');

$db = connect_db();

$sql = "CREATE TABLE IF NOT EXISTS $TableWOZ ($WOZID int(5) AUTO_INCREMENT PRIMARY KEY, $WOZFundaID int(8) NOT NULL, $WOZJaar int(4) NOT NULL, $WOZPrijs int(7) NOT NULL, $WOZLastCheck int(11) NOT NULL)";
mysqli_query($db, $sql);

?>