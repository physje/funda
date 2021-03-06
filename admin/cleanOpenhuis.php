<?php
include_once(__DIR__.'/../include/config.php');
include_once('../include/HTML_TopBottom.php');
$db = connect_db();

$minUserLevel = 3;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

if(isset($_REQUEST['id'])) {
	$sql		= "SELECT * FROM $TableCalendar WHERE $CalendarHuis like ". $_REQUEST['id'] ." ORDER BY $CalendarStart ASC";
} else {
	# Vraag alle huis-tijd combinaties op
	$sql		= "SELECT *, COUNT(*) as aantal FROM $TableCalendar GROUP BY $CalendarHuis, $CalendarStart HAVING aantal > 1";
}
$result	= mysqli_query($db, $sql);
$row		= mysqli_fetch_array($result);

$error = array();

do {	
	# neem een huis-tijd combinatie
	$huis		= $row[$CalendarHuis];
	$start	= $row[$CalendarStart];
	$einde	= $row[$CalendarEnd];
	
	# Vraag de gegevens van dit huis op
	$data = getFundaData($huis);
		
	# Als het om een bekend huis gaat (lees correct id) ga verder
	if(is_array($data)) {
		# Verwijder alle entries van deze huis-tijd combinatie
		$sql = "DELETE FROM $TableCalendar WHERE $CalendarHuis = $huis AND $CalendarStart = $start";
		
		# Voeg een verschijning van deze huis-tijd combinatie weer toe
		if(mysqli_query($db, $sql)) {
			$sql = "INSERT INTO $TableCalendar ($CalendarHuis, $CalendarStart, $CalendarEnd) VALUES ($huis, $start, $einde)";
			if(!mysqli_query($db, $sql)) {
				$error[] = "Toevoegen van de openhuis van <b>". $data['adres'] ."</b> ($huis) is mislukt<br>\n";
			} else {
				$melding[] = '<b>'. $data['adres'] .'</b> : '. date("d-m H:i", $start)  ." -> ". date("H:i", $einde) ."<br>\n";
			}
		}
	} else {
		# Er is een tijd gevonden wordt voor een huis wat niet meer bestaat.
		# Als het goed is komt dit nooit voor, mocht het vaker voorkomen dan kan checkTables.php gedraaid worden.
		# Hiermee worden de huizen uit de verschillende tabellen naast elkaar gelegd op zoek naar discrepanties
		$error[] = "<em>$huis</em> bestaat niet";
		
		# Als het huis niet bestaat kunnen de prijzen verwijderd worden				
		$sql = "DELETE FROM $TableCalendar WHERE $CalendarHuis = $huis";
		if(mysqli_query($db, $sql)) {
			$error[] = ", en is verwijderd<br>\n";
		} else {
			$error[] = ", maar kon niet worden verwijderd<br>\n";
		}			
	}	
} while($row = mysqli_fetch_array($result));

# Iets dergelijks gebeurd ookal dagelijks bij het genereren van de iCal-bestanden
# met open huis
/*
$sql = "SELECT $HuizenID FROM $TableHuizen WHERE $HuizenOpenHuis like '1' AND $HuizenDetails like '0'";
$result	= mysqli_query($db, $sql);
$row		= mysqli_fetch_array($result);

do {	
	$fundaID = $row[$HuizenID];	
	$openhuis = getNextOpenhuis($fundaID);
	
	if($openhuis[0] == 0 AND $openhuis[1] == 0) {
		removeOpenHuis($fundaID);
	}
	
} while($row = mysqli_fetch_array($result));
*/

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

?>