<?php
include_once(__DIR__.'/../include/config.php');
$db = connect_db();

$minUserLevel = 1;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

$deel_1 = $deel_2 = $id = '';

if(isset($_REQUEST['id'])) {
	$id = $_REQUEST['id'];
} elseif(isset($_POST['extra_huis'])) {
	$elementen = getString('[', ']', $_POST['extra_huis'], 0);
	$id = $elementen[0];
}

if($id != '') {	
	$data = getFundaData($id);
	$adres = formatStreetAndNumber($id);//convertToReadable($data['adres']);
	$deel_2	= $adres;
	
	$links['http://www.funda.nl/'.$id] 				= "Bekijk $adres op funda.nl";
	//$links['renewData.php?id='. $id]					= "Haal de gegevens van $adres opnieuw van funda.nl";	
	$links['edit.php?id='. $id]								= "Wijzig de gegevens van $adres";
	$links['overviewOpdrachtenHuis.php?id='. $id]	= "Bekijk zoekopdrachten waar $adres gevonden is";
	$links['checkOudeHuizen.php?id='. $id] 		= "Haal verkoop-gegevens van $adres op";
	$links['bekijkHuizenZoeker.php?id='. $id]	= "Zoek $adres op HuizenZoeker.nl";
	$links['delete.php?id='. $id]							= "Verwijder $adres uit de database";
	$links['cleanPrice.php?id='. $id]					= "Prijzen van $adres opschonen";
	$links['cleanKenmerk.php?id='. $id]				= "Kenmerken van $adres opschonen";
	$links['cleanOpenhuis.php?id='. $id]			= "Open Huis vermeldingen van $adres opschonen";
	
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
	$deel_1 = "<form method='post' action='". $_SERVER['PHP_SELF']."'>\n";
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

if($deel_2 != '') {
	echo "<td width='50%' valign='top' align='center'>\n";
	echo showBlock($deel_2);
	echo "</td>\n";
}
echo "</tr>\n";
echo $HTMLFooter;