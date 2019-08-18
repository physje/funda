<?php
include_once(__DIR__.'/../include/config.php');
$db = connect_db();

$minUserLevel = 3;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

if(isset($_REQUEST['fundaID'])) {
	$query = "SELECT * FROM $TableHuizen WHERE $HuizenID = ". $_REQUEST['fundaID'];
} else {
	$query = "SELECT * FROM $TableHuizen WHERE $HuizenPC_c = '' ORDER BY $HuizenEind DESC LIMIT 0,1";
}
$result = mysqli_query($db, $query);
$row = mysqli_fetch_array($result);

$data = getFundaData($row[$HuizenID]);

$straat			= $data['straat'];
$nummer 		= $data['nummer'];
$letter			= $data['letter'];
$toevoeging	= $data['toevoeging'];

$Rechts[] = '<table>';
$Rechts[] = '<tr>';
$Rechts[] = '	<td colspan=2><b>Details</b></td>';
$Rechts[] = '</tr>';
$Rechts[] = '<tr>';
$Rechts[] = '	<td>ID@funda</td><td><a href=\'edit.php?id='. $row[$HuizenID] .'\'>'. $row[$HuizenID] .'</a></td>';
$Rechts[] = '</tr>';
$Rechts[] = '<tr>';
$Rechts[] = '	<td>Volledige adres</td><td>'.$data['adres'] .'</td>';
$Rechts[] = '</tr>';
$Rechts[] = '<tr>';
$Rechts[] = '	<td>Straatnaam</td><td>'. $straat .'</td>';
$Rechts[] = '</tr>';
$Rechts[] = '<tr>';
$Rechts[] = '	<td>Huisnummer</td><td>'. $nummer .'</td>';
$Rechts[] = '</tr>';

if($letter != '') {
	$Rechts[] = '<tr>';
	$Rechts[] = '	<td>Huisletter</td><td>'. $letter .'</td>';
	$Rechts[] = '</tr>';
}

if($toevoeging != '') {
	$Rechts[] = '<tr>';
	$Rechts[] = '	<td>Huisnummertoevoeging</td><td>'. $toevoeging .'</td>';
	$Rechts[] = '</tr>';
}
$Rechts[] = '<tr>';
$Rechts[] = '	<td>Woonplaats</td><td>'. $data['plaats'] .'</td>';
$Rechts[] = '</tr>';
$Rechts[] = '</table>';

if(is_numeric($nummer)) {
    $PC = findPCbyAdress($straat, $nummer, $letter, $toevoeging, $data['plaats']);
    
    if(strlen($PC) <> 6) {
    	$Links[] = 'Geen postcode kunnen vinden<br>';
    	$Links[] = '<b>Foutmelding</b> : '. $PC;
    } elseif(updatePC($row[$HuizenID], $PC)) {
    	$Links[] = 'Gelukt : '. $PC;
    	if(!isset($_REQUEST['fundaID']))	$userInteraction = false;
    } else {
    	$Links[] = 'Geen postcode kunnen updaten';
    }
} else {
    $Links[] = 'Controleer input parameters, het huisnummer is niet numeriek : '. $nummer;
}

include_once('../include/HTML_TopBottom.php');
echo $HTMLHeader;
echo "<tr>\n";
echo "<td width='50%' valign='top' align='center'>". showBlock(implode("\n", $Rechts)) ."</td>";
echo "<td width='50%' valign='top' align='center'>". showBlock(implode("\n", $Links)) ."</td>";
echo "</tr>\n";
echo $HTMLFooter;