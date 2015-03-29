<?php
include_once(__DIR__. '../include/config.php');
include_once('../include/HTML_TopBottom.php');
$minUserLevel = 1;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");
connect_db();

if(!isset($_REQUEST['bDag']) OR !isset($_REQUEST['bMaand']) OR !isset($_REQUEST['bJaar'])) {
	$bDag = date('d');
	$bMaand = date('m');
	$bJaar = date('Y');	
} else {	
	$bDag = $_REQUEST['bDag'];
	$bMaand = $_REQUEST['bMaand'];
	$bJaar = $_REQUEST['bJaar'];
}

if(!isset($_REQUEST['eDag']) OR !isset($_REQUEST['eMaand']) OR !isset($_REQUEST['eJaar'])) {
	$eDag = date('d');
	$eMaand = date('m');
	$eJaar = date('Y');	
} else {
	$eDag = $_REQUEST['eDag'];
	$eMaand = $_REQUEST['eMaand'];
	$eJaar = $_REQUEST['eJaar'];
}

$dateSelection = makeDateSelection('', '', $bDag, $bMaand, $bJaar, '', '', $eDag, $eMaand, $eJaar);

if(isset($_REQUEST['prijs'])) {
	$price_2 = formatPrice(corrigeerPrice(mktime(0,0,0,$bMaand, $bDag, $bJaar), $_REQUEST['prijs'], mktime(0,0,0,$eMaand, $eDag, $eJaar)));
} else {
	$price_2 = '&nbsp;';
}

$zoekScherm[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
$zoekScherm[] = "<table border=0 align='center'>";
$zoekScherm[] = "<tr>";
$zoekScherm[] = "	<td><b>Van</b></td>";
$zoekScherm[] = "	<td>&nbsp;</td>";
$zoekScherm[] = "	<td rowspan='3'><input type='submit' value='Corrigeer' name='submit'></td>";
$zoekScherm[] = "	<td>&nbsp;</td>";
$zoekScherm[] = "	<td><b>Naar</b></td>";
$zoekScherm[] = "</tr>";
$zoekScherm[] = "<tr>";
$zoekScherm[] = "	<td>". $dateSelection[0] ."</td>";
$zoekScherm[] = "	<td>&nbsp;</td>";
$zoekScherm[] = "	<td>&nbsp;</td>";
$zoekScherm[] = "	<td>". $dateSelection[1] ."</td>";
$zoekScherm[] = "</tr>";
$zoekScherm[] = "<tr>";
$zoekScherm[] = "	<td><input type='text' name='prijs' value='". $_REQUEST['prijs'] ."'></td>";
$zoekScherm[] = "	<td>&nbsp;</td>";
$zoekScherm[] = "	<td>&nbsp;</td>";
$zoekScherm[] = "	<td>$price_2</td>";
$zoekScherm[] = "</tr>";
$zoekScherm[] = "</table>";
$zoekScherm[] = "</form>";

echo $HTMLHeader;
echo "<tr>\n";
echo "	<td width='25%'>&nbsp;</td>\n";
echo "	<td valign='top' align='center' colspan=2>". showBlock(implode("\n", $zoekScherm)) ."</td>\n";
echo "	<td width='25%'>&nbsp;</td>\n";
echo "</tr>\n";
echo $HTMLFooter;