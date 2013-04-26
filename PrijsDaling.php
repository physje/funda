<?php
include_once('../general_include/general_functions.php');
include_once('../general_include/general_config.php');
include_once('include/functions.php');
include_once('include/config.php');
include_once('include/HTML_TopBottom.php');
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
		echo "<a href='admin/HouseDetails.php?selectie=". $_REQUEST['selectie'] ."&id=$huisID'><img src='http://www.vvaltena.nl/styles/img/details/report.png'></a>";
		echo "<a id='$huisID'> <a href='http://www.funda.nl". $data['url'] ."' target='_blank' class='$TextClass'>$adres</a></td>\n";
		echo "	<td colspan=2>\n";
		echo "	<table width='100%' border=0><tr>\n";
		if(array_sum($breedte) > 0) {			
			foreach($breedte as $tijd => $value) {
				echo "		<td width='". $value ."%' bgcolor='#FF6D6D' title='Gedaald naar ". formatPrice($prijzen[$tijd]) ." (afname ". formatPercentage($percentage[$tijd]) .")\nOorspronkelijk ". formatPrice(getOrginelePrijs($huisID)) ." (afname ". formatPercentage($percentage_overall[$tijd]) .")'>&nbsp;</td>\n";
			}
		}
		echo "		<td width='". $restBreedte ."%'>&nbsp;</td>\n";
		echo "	</tr></table>\n";
		echo "	</td>\n";
		echo "	<td width='7%'><a href='TimeLine.php?selectie=". $_REQUEST['selectie'] ."#$huisID'>". getDoorloptijd($huisID) ."</a></td>\n";		
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
	# Vraag alle actieve opdrachten en lijsten op en zet die in een pull-down menu
	# De value is Z... voor een zoekopdracht en L... voor een lijst
	$Opdrachten = getZoekOpdrachten(1);
	$Lijsten		= getLijsten(1);
	
	# Als er geen lijsten zijn of als er huizen aan een lijst worden toegevoegd
	# (het is zinloos om dan lijsten te laten zien) de lijsten disablen
	if(count($Lijsten) == 0 || isset($_REQUEST['addHouses'])) {
		$showList = false;
	} else {
		$showList = true;
	}
	
	echo "<form method='post' action='$_SERVER[PHP_SELF]'>\n";
	echo "<input type='hidden' name='addHouses' value='". (isset($_REQUEST['addHouses']) ? '1' : '0') ."'>\n";
	echo "<input type='hidden' name='chosenList' value='". $_REQUEST['chosenList'] ."'>\n";
	echo "<table>\n";
	echo "<tr>\n";
	echo "	<td>Selectie</td>\n";	
	echo "	<td>&nbsp;</td>\n";
	echo "	<td><select name='selectie'>\n";
	echo "	<optgroup label='Zoekopdrachten'>\n";
	
	foreach($Opdrachten as $OpdrachtID) {
		$OpdrachtData = getOpdrachtData($OpdrachtID);
		echo "	<option value='Z$OpdrachtID'>". $OpdrachtData['naam'] ."</option>\n";
	}
	
	echo "	</optgroup>\n";
	echo "	<optgroup label='Lijsten'". ($showList ? '' : ' disabled') .">\n";
	
	foreach($Lijsten as $LijstID) {
		$LijstData = getLijstData($LijstID);
		echo "	<option value='L$LijstID'>". $LijstData['naam'] ."</option>\n";
	}
	
	echo "	</optgroup>\n";
	echo "	</select>\n";
	echo "	</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "	<td colspan='3' align='center'><input type='submit' name='submit' value='Weergeven'></td>\n";
	echo "</tr>\n";
	echo "<table>\n";
	echo "</form>\n";
}

echo $HTMLFooter;
?>
