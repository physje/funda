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

if(isset($_POST['urls'])) {
	if($_POST['lijstID'] == '') {
		$nieuwNaam = 'NieuweHuizen_'.time();
		$lijstID = saveUpdateList('', $_SESSION['UserID'], 1, $nieuwNaam);
	} else {
		$lijstID = $_POST['lijstID'];
	}
	
	$dataset = explode("\n", $_POST['urls']);
		
	if(count($dataset) > 0){		
		foreach($dataset as $huis) {
			//$deel_2 .= addHouse2List($huis, $lijstID);
			$huis = str_replace('http://www.funda.nl', '', $huis);
			$mappen = explode("/", $huis);						
			$delen = explode("-", $mappen[3]);
			
			$data['url'] = $huis;
			$data['id'] = $delen[1];
			$data['adres'] = implode(' ', array_slice($delen, 2));
			
			if(!saveHouse($data, array())) {
				$deel_1 .= $data['adres']. " aan dB toevoegen is mislukt<br>\n";
			} else {
				if(!addHouse2List($data['id'], $lijstID)) {
					$deel_1 .= $data['adres']. " aan lijst $lijstID toevoegen is mislukt<br>\n";
				} else {
					$deel_1 .= $data['adres']. " toegevoegd<br>\n";
				}
			}					
		}
	} else {
		$deel_1 = "<p>Selectie bevat geen huizen";
	}
} else {
	$Lijsten = getLijsten($_SESSION['UserID'], '');
	
	$deel_1 = "<form method='post' action='$_SERVER[PHP_SELF]'>\n";	
	$deel_1 .= "<table border=0>\n";
	$deel_1 .= "<tr>\n";
	$deel_1 .= "	<td>Voer de funda-url's (http://www.funda.nl/koop/...) in elk op een nieuwe regel</td>\n";
	$deel_1 .= "</tr>\n";
	$deel_1 .= "<tr>\n";
	$deel_1 .= "	<td><textarea name='urls' cols='50' rows='5'></textarea></td>\n";
	$deel_1 .= "</tr>\n";
	$deel_1 .= "<tr>\n";
	$deel_1 .= "	<td>Selecteer de lijst<br>";
	$deel_1 .= "	<select name='lijstID'>\n";
	$deel_1 .= "	<option value=''> * nieuwe lijst *</option>\n";
		
	foreach($Lijsten as $LijstID) {
		$LijstData = getLijstData($LijstID);
		$deel_1 .= "		<option value='$LijstID'>". $LijstData['naam'] ."</option>\n";
	}
		
	$deel_1 .= "</select>\n";	
	$deel_1 .= "	</td>\n";
	$deel_1 .= "</tr>\n";
	$deel_1 .= "<tr>\n";
	$deel_1 .= "	<td>&nbsp;</td>\n";
	$deel_1 .= "</tr>\n";	
	$deel_1 .= "<tr>\n";
	$deel_1 .= "	<td align='center'><input type='submit' name='toevoegen' value='Weergeven'></td>\n";
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
