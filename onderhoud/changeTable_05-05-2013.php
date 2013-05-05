<?php
include_once('../../general_include/general_functions.php');
include_once('../../general_include/general_config.php');
include_once('../include/functions.php');
include_once('../include/config.php');

connect_db();

$sql_abo = "CREATE TABLE `$TableAbo` (`$AboZoekID` INT( 3 ) NOT NULL, `$AboUserID` INT( 3 ) NOT NULL) ENGINE = MYISAM";
mysql_query($sql_abo);

$Opdrachten = getZoekOpdrachten('', '');

foreach($Opdrachten as $Opdracht) {
	$data = getOpdrachtData($Opdracht);
	
	if($data['mail'] == 1) {
		addMember2Opdracht($Opdracht, $data['user']);
	
		$sql 		= "SELECT * FROM $TableUsers WHERE $UsersAccount like '". $data['user'] ."'";
		$result = mysql_query($sql);
	
		while($row = mysql_fetch_array($result)) {
			addMember2Opdracht($Opdracht, $row[$UsersID]);
		}
	}		
}

$sql_zoeken = "ALTER TABLE `$TableZoeken` DROP `$ZoekenMail`, DROP `$ZoekenAdres`";
mysql_query($sql_zoeken);
?>