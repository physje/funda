<?php
include_once(__DIR__. 'include/config.php');
include_once(__DIR__  .'general_includes/class.MobileDetect.php');


include_once(__DIR__ . 'include/HTML_TopBottom.php');
$minUserLevel = 1;
$cfgProgDir = 'auth/';
include($cfgProgDir. "secure.php");

$detect = new Mobile_Detect;
if ($detect->isMobile() ) {
	$mobile = true;
}

connect_db();

echo $HTMLHeader;

if(isset($_POST['add'])) {
		foreach($_POST['huis'] as $huis) {
		echo addHouse2List($huis, $_POST['lijst']);
	}
	
	echo "Huizen verwerkt.";
	
} elseif(isset($_REQUEST['selectie'])) {
	$groep	= substr($_REQUEST['selectie'], 0, 1);
	$id			= substr($_REQUEST['selectie'], 1);
	
	if($groep == 'Z') {		
		$opdrachtData	= getOpdrachtData($id);
		$Name					= $opdrachtData['naam'];
		$dataset				= getHuizen($id);
	} else {
		$LijstData		= getLijstData($id);
		$Name					= $LijstData['naam'];
		$dataset			= getLijstHuizen($id);
	}

	$knownHuizen = array();
	if($_POST['addHouses'] == '1') {
		$showListAdd = true;
		$knownHuizen = getLijstHuizen($_POST['chosenList']);
	}
		
	$max_percentage = 33;
		
	echo "<form method='post' action='$_SERVER[PHP_SELF]'>\n";
	if($showListAdd) echo "<input type='hidden' name='lijst' value='". $_POST['chosenList'] ."'>\n";
	echo "<table width='100%' border=0>\n";
	echo "<tr>\n";
	echo "	<td align='center' colspan='4'><h1>Prijsdaling '$Name'</h1></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "	<td colspan='4'>&nbsp;</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "	<td width='25%'>&nbsp;</td>\n";
	echo "	<td width='34%' align='left'>0 %</td>\n";
	echo "	<td width='34%' align='right'>$max_percentage %</td>\n";
	echo "	<td width='7%'>&nbsp;</td>\n";
	echo "</tr>\n";
	
	# Doorloop alle huizen
	foreach($dataset as $huisID) {
		$data 			= getFundaData($huisID);
		$adres			= convertToReadable($data['adres']);
		$relPrijzen	= getFullPriceHistory($huisID);
		$prijzen						= $relPrijzen[0];
		$percentage					= $relPrijzen[2];
		$percentage_overall	= $relPrijzen[4];
		
		$breedte		= array();
				
		if(array_sum($percentage) > 0) {
			foreach($percentage as $key => $perc) {
				$breedte[$key]	= round(100*$perc/$max_percentage);
			}
			
			# De eerste breedte is altijd 0, die skippen we dus
			# Omdat we w�l de keys willen behouden (omdat dat de link is met andere arrays) moeten we de 4de variabele meegeven.
			# Omdat ik geen idee heb hoe lang de array is, neem ik 999 voor de 3de variabele
			$breedte = array_slice($breedte, 1, 999, true);
			
			$percGemiddeld[]	= 100-$relPrijzen[5];
		} else {
			$breedte[] = 0;
		}
		
		$restBreedte = 100 - array_sum($breedte);
		
		if($data['offline'] == '1') {
			if($data['verkocht'] != '1') {
				$TextClass = 'offline';
			} else {
				$TextClass = 'offlineVerkocht';
			}			
		} elseif($data['verkocht'] == '1') {
			$TextClass = 'onlineVerkocht';
		} else {
			$TextClass = 'online';
		}
		
		echo "<tr>\n";
		echo "	<td width='25%'>";
		if($showListAdd)	echo "	<input type='checkbox' name='huis[]' value='$huisID'". (in_array($huisID, $knownHuizen) ? ' checked' : '') .">";
		echo "<a href='admin/HouseDetails.php?selectie=". $_REQUEST['selectie'] ."&id=$huisID'><img src='http://www.nccfsokotoalumni.com/wp-content/themes/NCCF/images/tags.png' title='Toon opties voor $adres'></a>";
		echo "<a id='$huisID'> <a href='http://funda.nl/". $huisID ."' target='_blank' class='$TextClass' title='Bezoek $adres op funda.nl'>$adres</a></td>\n";
		echo "	<td colspan=2>\n";
		echo "	<table width='100%' border=0><tr>\n";
		$i=0;
		if(array_sum($breedte) > 0) {			
			foreach($breedte as $tijd => $value) {
				$i++;
				echo "		<td width='". $value ."%' bgcolor='#FF6D6D' title='Gedaald naar ". formatPrice($prijzen[$tijd]) ." (afname ". formatPercentage($percentage[$tijd]) .")\nOorspronkelijk ". formatPrice(getOrginelePrijs($huisID)) ." (afname ". formatPercentage($percentage_overall[$tijd]) .")'>&nbsp;</td>\n";
			}
		}
		echo "		<td width='". $restBreedte ."%'>&nbsp;</td>\n";
		echo "	</tr></table>\n";
		echo "	</td>\n";
		echo "	<td width='7%'><a href='TimeLine.php?selectie=". $_REQUEST['selectie'] ."#$huisID' title='Bekijk het tijdsverloop van $adres'>". getDoorloptijd($huisID) ."</a></td>\n";		
		echo "</tr>\n";
	}
	
	$percentage = array_sum($percGemiddeld)/count($percGemiddeld);
	$breedte_1	= round(100*$percentage/$max_percentage);
	$breedte_2	= round(100*($max_percentage-$percentage)/$max_percentage);
	
	echo "<tr>\n";
	echo "	<td width='25%'><b>Gemiddeld</b></td>\n";
	echo "	<td colspan=2>\n";
	echo "	<table width='100%' border=0><tr>\n";
	echo "		<td width='". $breedte_1 ."%' bgcolor='#FF6D6D' title='Gemiddeld ". number_format($percentage, 0) ."%'>&nbsp;</td>\n";
	echo "		<td width='". $breedte_2 ."%'>&nbsp;</td>\n";
	echo "	</tr></table>\n";
	echo "	</td>\n";
	echo "	<td width='7%'>&nbsp;</td>\n";		
	echo "</tr>\n";
	
	# Laat een submit-button zien als dat nodig is.
	if($showListAdd) {
		echo "<tr>\n";
		echo "	<td colspan='$aantalCols'>&nbsp;</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "	<td colspan='$aantalCols' align='center'><input type='submit' name='add' value='Voeg toe'></td>\n";
		echo "</tr>\n";
	}
	
	echo "</table>\n";
	echo "</form>\n";
} else {
	$HTML[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
	$HTML[] = "<input type='hidden' name='addHouses' value='". (isset($_REQUEST['addHouses']) ? '1' : '0') ."'>";
	$HTML[] = "<input type='hidden' name='chosenList' value='". $_REQUEST['chosenList'] ."'>";
	$HTML[] = "<table>";
	$HTML[] = "<tr>";
	$HTML[] = "	<td>Selectie</td>";	
	$HTML[] = "	<td>&nbsp;</td>";
	$HTML[] = "	<td>". makeSelectionSelection(isset($_REQUEST['addHouses']), false) ."</td>";
	$HTML[] = "</tr>";
	$HTML[] = "<tr>";
	$HTML[] = "	<td colspan='3' align='center'><input type='submit' name='submit' value='Weergeven'></td>";
	$HTML[] = "</tr>";
	$HTML[] = "</table>";
	$HTML[] = "</form>";
	
	echo "<tr>\n";
	echo "<td width='25%' valign='top' align='center'>&nbsp;</td>\n";
	echo "<td width='50%' valign='top' align='center'>\n";
	echo showBlock(implode("\n", $HTML), $mobile);
	echo "</td>\n";
	echo "<td width='25%' valign='top' align='center'>&nbsp;</td>\n";
	echo "</tr>\n";	
}

echo $HTMLFooter;