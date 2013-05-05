<?php
include_once('../../general_include/general_functions.php');
include_once('../../general_include/general_config.php');
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
$minUserLevel = 3;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");
connect_db();

$sql		= "SELECT * FROM $TableHuizen ORDER BY $HuizenAdres, $HuizenStart";
$result	= mysql_query($sql);
$row = mysql_fetch_array($result);

$i = 1;
$KeyArray = array();

do {
	$option[] = "<option value='". $row[$HuizenID] ."'>". urldecode($row[$HuizenAdres]) ."; ". urldecode($row[$HuizenPlaats]) ." (". $row[$HuizenID] .")</option>";
} while($row = mysql_fetch_array($result));

$HTML[] = "<form method='post' action='combine_batch.php'>\n";
$HTML[] = "<table>\n";
$HTML[] = "<tr>";
$HTML[] = "	<td>Verwijderen</td>";
$HTML[] = "	<td>&nbsp;</td>";
$HTML[] = "	<td>Master</td>";	
$HTML[] = "</tr>";	
$HTML[] = "<tr>";
$HTML[] = "	<td><select name='id_1'>";
$HTML[] = implode("\n", $option);
$HTML[] = "	</td>";
$HTML[] = "	<td> -> </td>";
$HTML[] = "	<td><select name='id_2'>";
$HTML[] = implode("\n", $option);
$HTML[] = "	</td>";	
$HTML[] = "</tr>";
$HTML[] = "</table>\n";
$HTML[] = "<input type='submit' name='uitvoeren' value='uitvoeren'>\n";
$HTML[] = "</form>\n";
$HTML[] = 
$HTML[] = "Starttijd van huis 1 toevoegen aan huis 2";

echo $HTMLHeader;
echo "<tr>\n";
echo "	<td>". showBlock(implode("\n", $HTML)) ."</td>\n";
echo "</tr>\n";
echo $HTMLFooter;
?>