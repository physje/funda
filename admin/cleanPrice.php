<?php
include_once('../../general_include/general_functions.php');
include_once('../../general_include/general_config.php');
include_once('../include/functions.php');
include_once('../include/config.php');
connect_db();

if(isset($_REQUEST['id'])) {
	$sql		= "SELECT * FROM $TablePrijzen WHERE $PrijzenID like ". $_REQUEST['id'] ." ORDER BY $PrijzenTijd ASC";
} else {
	// Vraag alle huis-prijs combinaties op
	$sql		= "SELECT * FROM $TablePrijzen GROUP BY $PrijzenID, $PrijzenPrijs";
}
$result	= mysql_query($sql);
$row		= mysql_fetch_array($result);

do {	
	// Vraag de huis-prijs combinatie op
	$huis		= $row[$PrijzenID];
	$prijs	= $row[$PrijzenPrijs];
	
	// Vraag de gegevens van dit huis op
	$data = getFundaData($huis);
	
	// Hou bij welke huis-prijs combinaties zijn geweest.
	// Als de array die dat bijhoudt niet bestaat : maak deze aan
	if(!isset($huis_array[$huis])) {
		$huis_array[$huis] = array();
	}
		
	if(is_array($data)) {
		$sql_detail = "SELECT * FROM $TablePrijzen WHERE $PrijzenID like '$huis' AND $PrijzenPrijs like '$prijs' ORDER BY $PrijzenTijd ASC LIMIT 0,1";
		
		if($result_detail = mysql_query($sql_detail) AND !in_array($prijs, $huis_array[$huis])) {
			$row_detail	= mysql_fetch_array($result_detail);
			$tijd				= $row_detail[$PrijzenTijd];
			
			// Voeg de huis-prijs combinatie toe aan de array
			$huis_array[$huis][] = $prijs;
			
			// Verwijder alle entries van deze betreffende huis-prijs combinatie
			$sql = "DELETE FROM $TablePrijzen WHERE $PrijzenID = $huis AND $PrijzenPrijs = $prijs";
	
			if(mysql_query($sql)) {
				$sql = "INSERT INTO $TablePrijzen ($PrijzenID, $PrijzenPrijs, $PrijzenTijd) VALUES ('$huis', $prijs, $tijd)";
				if(!mysql_query($sql)) {
					echo "Toevoegen van de prijs van <b>". $data['adres'] ."</b> ($huis) is mislukt<br>\n";
				} else {
					echo '<b>'. $data['adres'] .'</b> : '. date("d-m-Y", $tijd)  ." -> $prijs<br>\n";
				}
			}
		}
		
		/*
		if($data['start'] > $tijd) {
			echo "<a href='edit.php?id=$huis'>$huis</a> is 'ouder'<br>\n";
		} else {
			echo "<u>$huis</u> is ok<br>\n";
		}
		*/
		
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

//

?>