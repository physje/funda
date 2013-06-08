<?php
include_once('../../general_include/general_functions.php');
include_once('../../general_include/general_config.php');
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
$minUserLevel = 1;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");
connect_db();

if(isset($_POST['ids'])) {
	$dataset = explode(";", $_POST['ids']);
	$nieuwNaam = 'List_'.date('Y-m-d|H-i-s');
	
	if(count($dataset) > 0){		
		$lijstID = saveUpdateList('', $_SESSION['UserID'], 1, $nieuwNaam);
		
		foreach($dataset as $huis) {		
			$deel_2 .= addHouse2List($huis, $lijstID);
		}
		
		$deel_1 = "<p>Selectie opgeslagen als <a href='edit_lijsten.php?list=$lijstID'>$nieuwNaam</a>";
} else {
		$deel_1 = "<p>Selectie bevat geen huizen";
	}
} else {
	$deel_1 = "<form method='post' action='$_SERVER[PHP_SELF]'>\n";	
	$deel_1 .= "<table border=0>\n";
	$deel_1 .= "<tr>\n";
	$deel_1 .= "	<td>Voer de funda-ID's in gescheiden door een puntkomma (;)</td>\n";
	$deel_1 .= "</tr>\n";
	$deel_1 .= "<tr>\n";
	$deel_1 .= "	<td><input type='text'name='ids' size='75'></td>\n";
	$deel_1 .= "</tr>\n";
	$deel_1 .= "<tr>\n";
	$deel_1 .= "	<td>&nbsp;</td>\n";
	$deel_1 .= "</tr>\n";	
	$deel_1 .= "<tr>\n";
	$deel_1 .= "	<td align='center'><input type='submit' name='combine' value='Weergeven'></td>\n";
	$deel_1 .= "</tr>\n";
	$deel_1 .= "</table>\n";
	$deel_1 .= "</form>\n";
}

echo $HTMLHeader;
echo "<tr>\n";
echo "<td width='50%' valign='top' align='center'>\n";
echo showBlock($deel_1);
echo "</td>\n";
if($deel_2 != "") {
	echo "<td width='50%' valign='top' align='center'>\n";
	echo showBlock($deel_2);
	echo "</td>\n";
} else {
	echo "<td width='50%' valign='top' align='center'>\n";
	echo "&nbsp;";
	echo "</td>\n";
}
echo "</tr>\n";
echo $HTMLFooter;

?>