<?
include_once('../../general_include/general_functions.php');
include_once('../../general_include/general_config.php');
include_once('../include/functions.php');
include_once('../include/config.php');
connect_db();

$sql		= "SELECT * FROM $TableKenmerken GROUP BY $KenmerkenID";
$result	= mysql_query($sql);
$row		= mysql_fetch_array($result);

//echo $sql;

do {
	$huis			= $row[$KenmerkenID];
	$kenmerk	= $row[$KenmerkenKenmerk];
	$waarde		= $row[$KenmerkenValue];
	
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
		echo "<u>$huis</u> bestaat niet";
				
		$sql = "DELETE FROM $TablePrijzen WHERE $PrijzenID = $huis";
		if(mysql_query($sql)) {
			echo ", en is verwijderd<br>\n";
		} else {
			echo ", maar kon niet worden verwijderd<br>\n";
		}			
	}
	
} while($row = mysql_fetch_array($result));

?>