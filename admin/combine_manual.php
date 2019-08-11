<?php
include_once(__DIR__.'/../include/config.php');
include_once('../include/HTML_TopBottom.php');
$minUserLevel = 3;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");
$db = connect_db();

$sql		= "SELECT * FROM $TableHuizen ORDER BY $HuizenAdres, $HuizenStart";
$result	= mysqli_query($db, $sql);
$row = mysqli_fetch_array($result);

$i = 1;
$KeyArray = array();

do {
	$huisID = $row[$HuizenID];
	$adres = urldecode($row[$HuizenAdres])."; ". urldecode($row[$HuizenPlaats]) ." | ". date("d.m.y", $row[$HuizenStart]) .' - '. date("d.m.y", $row[$HuizenEind]);
		
	$option_1[] = "<option value='$huisID'". ($huisID == $_REQUEST['id_1'] ? ' selected' : '') .">$adres</option>";
	$option_2[] = "<option value='$huisID'". ($huisID == $_REQUEST['id_2'] ? ' selected' : '') .">$adres</option>";
	
	if($huisID == $_REQUEST['id_1']) $start_tijd = $row[$HuizenStart];
	if($huisID == $_REQUEST['id_2']) $eind_tijd = $row[$HuizenEind];	
} while($row = mysqli_fetch_array($result));

$HTML[] = "<form method='post' action='combine_batch.php'>\n";
$HTML[] = "<input type='hidden' name='thijs' value='ja'>\n";
$HTML[] = "<table>\n";
$HTML[] = "<tr>";
$HTML[] = "	<td>Verwijderen</td>";
$HTML[] = "	<td>&nbsp;</td>";
$HTML[] = "	<td>Master</td>";	
$HTML[] = "</tr>";	
$HTML[] = "<tr>";
$HTML[] = "	<td><select name='id_1'>";
$HTML[] = implode("\n", $option_1);
$HTML[] = "	</td>";
$HTML[] = "	<td> -> </td>";
$HTML[] = "	<td><select name='id_2'>";
$HTML[] = implode("\n", $option_2);
$HTML[] = "	</td>";	
$HTML[] = "</tr>";
$HTML[] = "</table>\n";
$HTML[] = "<input type='submit' name='uitvoeren' value='uitvoeren'>\n";
$HTML[] = "</form>\n";
$HTML[] = "Starttijd van huis 1 toevoegen aan huis 2";
$HTML[] = date("d M Y", $start_tijd) .' tot '. date("d M Y", $eind_tijd);

echo $HTMLHeader;
echo "<tr>\n";
echo "	<td>". showBlock(implode("\n", $HTML)) ."</td>\n";
echo "</tr>\n";
echo $HTMLFooter;
