<?php
include_once('../../general_include/general_functions.php');
include_once('../../general_include/general_config.php');
include_once('../include/functions.php');
include_once('../include/config.php');
$minUserLevel = 1;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");
connect_db();

if(isset($_REQUEST['id'])) {
	$id = $_REQUEST['id'];
} elseif(isset($_POST['extra_huis'])) {
	$elementen = getString('[', ']', $_POST['extra_huis'], 0);
	$id = $elementen[0];
}

if($id != '') {	
	$data = getFundaData($id);
	$deel_2 = $data['adres'];
	
	$links['http://www.funda.nl'.$data['url']] 	= 'Bekijk '. $data['adres'] .' op funda.nl';
	$links['checkOudeHuizen.php?id='. $id] 			= 'Haal verkoop-gegevens van '. $data['adres'] .' op';
	$links['edit.php?id='. $id]									= 'Wijzig de gegevens van '. $data['adres'];	
	$links['bekijkHuizenZoeker.php?id='. $id]		= 'Zoek '. $data['adres'] .' op HuizenZoeker.nl';
	$links['delete.php?id='. $id]								= 'Verwijder '. $data['adres'] .' uit de database';
	$links['cleanPrice.php?id='. $id]						= 'Prijzen van '. $data['adres'] .' opschonen';
	
	if(isset($_REQUEST['selectie'])) {
		$selectie = $_REQUEST['selectie'];
		$links['../TimeLine.php?selectie='. $selectie .'#'. $id]		= 'Bekijk de tijdslijn';
		$links['../PrijsDaling.php?selectie='. $selectie .'#'. $id]	= 'Bekijk de prijsdalingen';
	} else {
		$links['../TimeLine.php']			= 'Bekijk de tijdslijn';
		$links['../PrijsDaling.php']	= 'Bekijk de prijsdalingen';
	}

	foreach($links as $url => $titel) {
		$deel_1 .= "<a href='$url'>$titel</a><br>\n";
	}
} else {
	$autocomplete = true; 
	$deel_1 = "<form method='post' action='$_SERVER[PHP_SELF]'>\n";
	$deel_1 .= "Voer adres of funda_id in om te zoeken.<br>\n";
	$deel_1 .= "<input type='text' name='extra_huis' id=\"huizen\" size='50'><br>";
	$deel_1 .= "<br>\n";
	$deel_1 .= "<input type='submit' name='search_house' value='Huis bekijken'>\n";
	$deel_1 .= "</form>\n";
}

include_once('../include/HTML_TopBottom.php');

echo $HTMLHeader;
echo "<tr>\n";
echo "<td width='50%' valign='top' align='center'>\n";
echo showBlock($deel_1);
echo "</td>\n";
if($deel2 != '') {
	echo "<td width='50%' valign='top' align='center'>\n";
	echo showBlock($deel_2);
	echo "</td>\n";
}
echo "</tr>\n";
echo $HTMLFooter;

?>