<?php
include_once(__DIR__.'/../include/config.php');
include_once('../include/HTML_TopBottom.php');
$db = connect_db();

$minUserLevel = 3;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

if(isset($_REQUEST['id'])) {
	$sql		= "SELECT * FROM $TablePrijzen WHERE $PrijzenID like ". $_REQUEST['id'] ." ORDER BY $PrijzenTijd ASC";
} else {
	# Vraag alle huis-prijs combinaties op
	$sql		= "SELECT *, COUNT(*) as aantal FROM $TablePrijzen GROUP BY $PrijzenID, $PrijzenPrijs HAVING aantal > 1";
}
$result	= mysqli_query($db, $sql);
$row		= mysqli_fetch_array($result);

$error = array();

do {	
	# neem een huis-prijs combinatie
	$huis		= $row[$PrijzenID];
	$prijs	= $row[$PrijzenPrijs];
	
	# Vraag de gegevens van dit huis op
	$data = getFundaData($huis);
	
	# Hou bij welke huis-prijs combinaties er zijn geweest.
	# Als de array die dat bijhoudt nog niet bestaat : maak deze dan aan
	if(!isset($huis_array[$huis])) {
		$huis_array[$huis] = array();
	}
	
	# Als het om een bekend huis gaat (lees correct id) ga verder
	if(is_array($data)) {
		# Vraag de eerst verschijning van deze prijs voor dit huis op
		$sql_detail = "SELECT * FROM $TablePrijzen WHERE $PrijzenID like '$huis' AND $PrijzenPrijs like '$prijs' ORDER BY $PrijzenTijd ASC LIMIT 0,1";
		
		if($result_detail = mysqli_query($db, $sql_detail) AND !in_array($prijs, $huis_array[$huis])) {
			$row_detail	= mysqli_fetch_array($result_detail);
			$tijd				= $row_detail[$PrijzenTijd];
			
			# Voeg de gevonden huis-prijs combinatie toe aan de array
			$huis_array[$huis][] = $prijs;
			
			if($tijd < $data['start']) {
				$sql_update = "UPDATE $TableHuizen SET $HuizenStart = $tijd WHERE $HuizenID like $huis";
				if(mysqli_query($db, $sql_update)) {
					$melding[] = '<b>'. formatStreetAndNumber($huis) .'</b> : begintijd aangepast naar '. date("d-m-Y", $tijd)  ." (was ". date("d-m-Y", $data['start']) .")<br>\n";	
				}
			}	
			
			# Verwijder alle entries van deze huis-prijs combinatie
			$sql = "DELETE FROM $TablePrijzen WHERE $PrijzenID = $huis AND $PrijzenPrijs = $prijs";
	
			# Voeg alleen de eerst verschijning van deze huis-prijs combinatie weer toe
			if(mysqli_query($db, $sql)) {
				$sql = "INSERT INTO $TablePrijzen ($PrijzenID, $PrijzenPrijs, $PrijzenTijd) VALUES ('$huis', $prijs, $tijd)";
				if(!mysqli_query($db, $sql)) {
					$error[] = "Toevoegen van de prijs van <b>". formatStreetAndNumber($huis) ."</b> ($huis) is mislukt<br>\n";
				} else {
					$melding[] = '<b>'. formatStreetAndNumber($huis) .'</b> : '. date("d-m-Y", $tijd)  ." -> ". formatPrice($prijs) ."<br>\n";
				}
			}
		}	
} else {
		# Er is een prijs gevonden voor een huis wat niet meer bestaat.
		# Als het goed is komt dit nooit voor, mocht het vaker voorkomen dan kan checkTables.php gedraaid worden.
		# Hiermee worden de huizen uit de verschillende tabellen naast elkaar gelegd op zoek naar discrepanties
		$error[] = "<em>$huis</em> bestaat niet";
		
		# Als het huis niet bestaat kunnen de prijzen verwijderd worden				
		$sql = "DELETE FROM $TablePrijzen WHERE $PrijzenID = $huis";
		if(mysqli_query($db, $sql)) {
			$error[] = ", en is verwijderd<br>\n";
		} else {
			$error[] = ", maar kon niet worden verwijderd<br>\n";
		}			
	}	
} while($row = mysqli_fetch_array($result));

if(count($error) > 0) {
	$errorVenster = implode("\n", $error);
} else {
	$errorVenster	= "Er zijn geen foutmeldingen\n";
}

if(count($melding) > 0) {
	$meldingenVenster = implode("\n", $melding);
} else {
	$meldingenVenster	= "Er zijn geen meldingen\n";
}

# Uitkomst netjes op het scherm tonen
echo $HTMLHeader;
echo "<tr>\n";
echo "<td width='50%' valign='top' align='center'>\n";
echo showBlock($errorVenster);
echo "</td><td width='50%' valign='top' align='center'>\n";
echo showBlock($meldingenVenster);
echo "</td>\n";
echo "</tr>\n";
echo $HTMLFooter;