<?php
include_once(__DIR__.'/../include/config.php');
include_once('../include/HTML_TopBottom.php');
$db = connect_db();

$minUserLevel = 3;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

$out = array();

if(isset($_REQUEST['id'])) {
	$sql		= "SELECT * FROM $TableKenmerken WHERE $KenmerkenID like ". $_REQUEST['id'] ." ORDER BY $KenmerkenKenmerk";
} else {
	# Vraag alle kenmerken combinaties op
	$sql		= "SELECT $KenmerkenID, COUNT(*) as aantal FROM $TableKenmerken GROUP BY $KenmerkenID, $KenmerkenKenmerk HAVING aantal > 1";	
}

$result	= mysqli_query($db, $sql);
$row		= mysqli_fetch_array($result);

do {
	$huis			= $row[$KenmerkenID];

	$data			= getFundaData($huis);
	$moreData = getFundaKenmerken($huis);
	
	if(is_array($data)) {
		$sql = "DELETE FROM $TableKenmerken WHERE $KenmerkenID = $huis";
	
		if(mysqli_query($db, $sql)) {
			foreach($moreData as $key => $value) {
				if($key != 'wijk') {
					$sql = "INSERT INTO $TableKenmerken ($KenmerkenID, $KenmerkenKenmerk, $KenmerkenValue) VALUES ('$huis', '". urlencode(strip_tags($key)) ."', '". urlencode(strip_tags($value)) ."')";
										
					if(!mysqli_query($db, $sql)) {
						$out[] = ' ERROR';
					}
				}
			}			
		}
	} else {
		$out[] = "<em>$huis</em> bestaat niet";
				
		$sql = "DELETE FROM $TableHuizen WHERE $HuizenID = $huis";
		if(mysqli_query($db, $sql)) {
			$out[] = ", en is verwijderd.\n";
		} else {
			$out[] = ", maar kon niet worden verwijderd.\n";
		}
		
		$sql = "DELETE FROM $TableKenmerken WHERE $KenmerkenID = $huis";
		if(mysqli_query($db, $sql)) {
			$out[] = " Data is verwijderd<br>\n";
		} else {
			$out[] = " Data kon niet worden verwijderd<br>\n";
		}
	}
	
} while($row = mysqli_fetch_array($result));

echo $HTMLHeader;
echo "<tr>\n";
echo "<td width='50%' valign='top' align='center'>\n";
echo showBlock(implode('', $out));
echo "</td>\n";
echo "<td width='50%' valign='top' align='center'>&nbsp;</td>\n";
echo "</tr>\n";
echo $HTMLFooter;