<?php
include_once(__DIR__.'/../include/config.php');
$minUserLevel = 1;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");
connect_db();
$deel_2 = $id = null;

if(isset($_REQUEST['id'])) {
	$id = $_REQUEST['id'];
} elseif(isset($_POST['search_house'])) {
	$elementen = getString('[', ']', $_POST['adres_input'], 0);
	$id = $elementen[0];
}

if($id != '') {	
	$data = getFundaData($id);
	$adres = convertToReadable($data['adres']);
	$deel_2	= $adres;
	
	$links['http://www.funda.nl/'.$id] 				= "Bekijk $adres op funda.nl";
	$links['renewData.php?id='. $id]					= "Haal de gegevens van $adres opnieuw van funda.nl";	
	$links['edit.php?id='. $id]								= "Wijzig de gegevens van $adres";	
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
	#$autocomplete = true;
	$autoCompleteNew = true;
	
	$deel_1 = "<form method='post' action='$_SERVER[PHP_SELF]'>\n";
	$deel_1 .= "Voer adres of funda_id in om te zoeken.<br>\n";	
	$deel_1 .= "<input type='text' id='adres_input' name='adres_input' placeholder='Zoek huis...'>";	
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