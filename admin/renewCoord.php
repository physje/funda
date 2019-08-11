<?php
include_once(__DIR__.'/../include/config.php');
include_once('../include/HTML_TopBottom.php');
setlocale(LC_ALL, 'nl_NL');
$minUserLevel = 3;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

$db = connect_db();

if(isset($_REQUEST['fundaID'])) {
	$huisID = $_REQUEST['fundaID'];

	$data = getFundaData($huisID);

	if(!addCoordinates($data['adres'], '', $data['plaats'], $huisID)) {
		$HTML[] = 'Mislukt';
	} else {
		$HTML[] = 'Gelukt';
	}
} else {
	$HTML[] = 'Geen huis gedefinieerd';
}

echo $HTMLHeader;
echo "<tr>\n";
echo "<td width='25%' valign='top' align='center'>&nbsp;</td>\n";
echo "<td width='50%' valign='top' align='center'>". showBlock(implode("\n", $HTML)) ."</td>";
echo "<td width='25%' valign='top' align='center'>&nbsp;</td>\n";
echo "</tr>\n";
echo $HTMLFooter;

?>