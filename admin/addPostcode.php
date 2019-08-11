<?php
include_once(__DIR__.'/../include/config.php');
include_once('../include/HTML_TopBottom.php');
$minUserLevel = 3;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");
$db = connect_db();

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

$Rechts[] = '<b>Details</b>';
$Rechts[] = 'ID@funda : '. $row[$HuizenID];
$Rechts[] = 'Volledige adres : '.$data['adres'];
$Rechts[] = 'Straatnaam :'. $straat;
$Rechts[] = 'Huisnummer : '. $nummer;
$Rechts[] = 'Huisletter : '. $letter;
$Rechts[] = 'Huisnummertoevoeging : '. $toevoeging;
$Rechts[] = 'Woonplaats : '. $data['plaats'];

if(is_numeric($nummer)) {
    $PC = findPCbyAdress($straat, $nummer, $letter, $toevoeging, $data['plaats']);

    if(updatePC($row[$HuizenID], $PC)) {
        $Links[] = 'Gelukt : '. $PC;
    } else {
        $Links[] = 'Geen postcode kunnen updaten';
    }
} else {
    $Links[] = 'Controleer input parameters, het huisnummer is niet numeriek : '. $nummer;
}

echo $HTMLHeader;
echo "<tr>\n";
echo "<td width='50%' valign='top' align='center'>". showBlock(implode("\n", $Links)) ."</td>";
echo "<td width='50%' valign='top' align='center'>". showBlock(implode("<br>\n", $Rechts)) ."</td>";
echo "</tr>\n";
echo $HTMLFooter;