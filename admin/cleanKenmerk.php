<?php
include_once(__DIR__.'/../include/config.php');
$minUserLevel = 3;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");
connect_db();

if(isset($_REQUEST['id'])) {
	$sql		= "SELECT * FROM $TableKenmerken WHERE $KenmerkenID like ". $_REQUEST['id'] ." ORDER BY $KenmerkenKenmerk";
} else {
	# Vraag alle kenmerken combinaties op
	$sql		= "SELECT *, COUNT(*) as aantal FROM $TableKenmerken GROUP BY $KenmerkenID, $KenmerkenValue HAVING aantal > 1";
}

$result	= mysql_query($sql);
$row		= mysql_fetch_array($result);

//echo $sql;

do {
	$huis			= $row[$KenmerkenID];

	$data			= getFundaData($huis);
	$moreData = getFundaKenmerken($huis);
	
	if(is_array($data)) {
		$sql = "DELETE FROM $TableKenmerken WHERE $KenmerkenID = $huis";
	
		if(mysql_query($sql)) {
			foreach($moreData as $key => $value) {
				if($key != 'wijk') {
					$sql = "INSERT INTO $TableKenmerken ($KenmerkenID, $KenmerkenKenmerk, $KenmerkenValue) VALUES ('$huis', '". urlencode(strip_tags($key)) ."', '". urlencode(strip_tags($value)) ."')";
					if(!mysql_query($sql)) {
						echo 'KUT';
					}
				}
			}			
		}
	} else {
		echo "<em>$huis</em> bestaat niet";
				
		$sql = "DELETE FROM $TableHuizen WHERE $HuizenID = $huis";
		if(mysql_query($sql)) {
			echo ", en is verwijderd<br>\n";
		} else {
			echo ", maar kon niet worden verwijderd<br>\n";
		}			
	}
	
} while($row = mysql_fetch_array($result));