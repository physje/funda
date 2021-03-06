<?php
include_once(__DIR__.'/../include/config.php');
include_once('../include/HTML_TopBottom.php');
$db = connect_db();

$minUserLevel = 3;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

if(isset($_REQUEST['id'])) {
	$id = $_REQUEST['id'];
	
	$data = getFundaData($id);
	$deel_1 = ''; 
	$deel_2 = $data['adres'] .', '. $data['plaats'];
	
	if(isset($_REQUEST['zeker'])) {		
		$sql_check_unique = "SELECT * FROM $TableResultaat WHERE $ResultaatID like '$id'";
		$result	= mysqli_query($db, $sql_check_unique);
		
		# Als er maar 1 resultaat is, of als men het zeker weet
		# mag het huis verwijderd worden
		if(mysqli_num_rows($result) == 1 OR isset($_REQUEST['heelzeker'])) {
			# Huis zelf verwijderen
			$sql = "DELETE FROM $TableHuizen WHERE $HuizenID like '$id'";
			if(mysqli_query($db, $sql)) {
				$deel_1 .= $data['adres'] ." is verwijderd<br>";
			}
			
			# Kenmerken opschonen			
			$sql = "DELETE FROM $TableKenmerken WHERE $KenmerkenID like '$id'";
			if(mysqli_query($db, $sql)) {
				$deel_1 .= "Kenmerken van ". $data['adres'] ." zijn verwijderd<br>";
			}
			
			# Prijzen opschonen
			$sql = "DELETE FROM $TablePrijzen WHERE $PrijzenID like '$id'";
			if(mysqli_query($db, $sql)) {
				$deel_1 .= "Prijzen-historie is verwijderd<br>";
			}
						
			# Resultaten opschonen
			$sql = "DELETE FROM $TableResultaat WHERE $ResultaatID like '$id'";
			if(mysqli_query($db, $sql)) {
				$deel_1 .= "Huis uit resultaten verwijderd<br>";
			}
			
			# Lijsten opschonen
			$sql = "DELETE FROM $TableListResult WHERE $ListResultHuis like '$id'";
			if(mysqli_query($db, $sql)) {
				$deel_1 .= "Huis van lijsten verwijderd<br>";
			}
			
			$deel_1 .= "<a href='HouseDetails.php'>terug</a><br>";
		} else {
			# Mocht het huis in verschillende opdrachten voorkomen,
			# dan is het de vraag of het wel verstandig is het huis te verwijderen.
			$deel_1 = "Weet je het echt heel zeker? Dit huis komt namelijk in meerdere opdrachten voor<br>". $sql_check_unique ."<br><a href='?id=$id&zeker=ja&heelzeker=ja'>JA</a> | <a href='HouseDetails.php?id=$id'>NEE</a>";
		}
	} else {
		$deel_1 = "Weet u zeker dat u ". $data['adres'] ." wilt verwijderen?<br><a href='?id=$id&zeker=ja'>JA</a> | <a href='HouseDetails?id=$id'>NEE</a>";
	}
} else {
	$deel_1 = "Geen id bekend<br>";
}

echo $HTMLHeader;
echo "<tr>\n";
echo "<td width='50%' valign='top' align='center'>\n";
echo showBlock($deel_1);
echo "</td><td width='50%' valign='top' align='center'>\n";
echo showBlock($deel_2);
echo "</td>\n";
echo "</tr>\n";
echo $HTMLFooter;