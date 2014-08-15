<?php
include_once('../general_include/general_functions.php');
include_once('../general_include/general_config.php');
include_once('include/functions.php');
include_once('include/config.php');
include_once('include/HTML_TopBottom.php');
$minUserLevel = 1;
$cfgProgDir = 'auth/';
include($cfgProgDir. "secure.php");

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
		$from					= "$TableResultaat, $TableHuizen";
		$where				= "$TableResultaat.$ResultaatID = $TableHuizen.$HuizenID AND $TableResultaat.$ResultaatZoekID = $id";
	} else {
		$LijstData		= getLijstData($id);
		$Name					= $LijstData['naam'];
		$from					= "$TableListResult, $TableHuizen";
		$where				= "$TableListResult.$ListResultHuis = $TableHuizen.$HuizenID AND $TableListResult.$ListResultList = $id";
	}
		
	if($_POST['addHouses'] == '1') {
		$showListAdd = true;
		$Huizen = getLijstHuizen($_POST['chosenList']);
	}
	
	$sql		= "SELECT min($TableHuizen.$HuizenStart) FROM $from WHERE $where";
	$result	= mysql_query($sql);
	$row		= mysql_fetch_array($result);
	$start_tijd = $row[0];
	
	$sql		= "SELECT max($TableHuizen.$HuizenEind) FROM $from WHERE $where";
	$result	= mysql_query($sql);
	$row		= mysql_fetch_array($result);
	$eind_tijd = $row[0];
	
	$sql		= "SELECT $TableHuizen.$HuizenOffline, $TableHuizen.$HuizenVerkocht, $TableHuizen.$HuizenAfmeld, $TableHuizen.$HuizenStart, $TableHuizen.$HuizenEind, $TableHuizen.$HuizenAdres, $TableHuizen.$HuizenID, $TableHuizen.$HuizenURL, ($TableHuizen.$HuizenEind - $TableHuizen.$HuizenStart) as tijdsduur FROM $from WHERE $where ORDER BY $TableHuizen.$HuizenAdres";
	$result	= mysql_query($sql);
	$row		= mysql_fetch_array($result); 
	
	$fullWidth = $eind_tijd - $start_tijd;
	
	echo "<form method='post' action='$_SERVER[PHP_SELF]'>\n";
	if($showListAdd) echo "<input type='hidden' name='lijst' value='". $_POST['chosenList'] ."'>\n";
	echo "<table width='100%' border=0>\n";
	echo "<tr>\n";
	echo "	<td align='center'><h1>Tijdslijn '$Name'</h1></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "	<td>&nbsp;</td>\n";
	echo "</tr>\n";
	echo "<tr><td>\n";
	echo "	<table width='100%' border=0><tr>\n";
	echo "	<td width='25%'>&nbsp;</td>\n";
	echo "	<td width='35%' align='left'>". date("d M y", $start_tijd) ."</td>\n";
	echo "	<td width='35%' align='right'>". date("d M y", $eind_tijd) ."</td>\n";
	echo "	<td width='5%' align='right'>&nbsp;</td>\n";
	echo "	</tr></table>\n";
	echo "</td></tr>\n";
		
	do {
		$breedte_1	= round(70*($row[$HuizenStart] - $start_tijd)/$fullWidth);
		$breedte_2	= round(70*($row[$HuizenEind] - $row[$HuizenStart])/$fullWidth);
		if($row[$HuizenVerkocht] == '1') {
			$breedte_3	= round(70*($row[$HuizenAfmeld] - $row[$HuizenEind])/$fullWidth);
		} else {
			$breedte_3	= 0;
		} 
		$breedte_4	= 70 - $breedte_1 - $breedte_2 - $breedte_3;
		
		$adres			= convertToReadable(urldecode($row[$HuizenAdres]));
		
		$relPrijzen			= getFullPriceHistory($row[$HuizenID]);
		$prijzen				= $relPrijzen[0];
		$percentageAll	= 100-$relPrijzen[5];	
		
		if($row[$HuizenOffline] == '1') {
			if($row[$HuizenVerkocht] != '1') {
				$class = 'offline';
			} else {
				$class = 'offlineVerkocht';
			}			
		} elseif($row[$HuizenVerkocht] == '1') {
			$class = 'onlineVerkocht';
		} else {
			$class = 'online';
		}
		
		echo "<tr><td>\n";
		echo "	<table width='100%' border=0><tr>\n";
		echo "		<td width='25%'>";
		if($showListAdd)	echo "	<input type='checkbox' name='huis[]' value='". $row[$HuizenID] ."'". (in_array($row[$HuizenID], $Huizen) ? ' checked' : '') .">";
		echo "<a id='". $row[$HuizenID] ."'><a href='admin/HouseDetails.php?selectie=". $_REQUEST['selectie'] ."&id=". $row[$HuizenID] ."'><img src='http://www.nccfsokotoalumni.com/wp-content/themes/NCCF/images/tags.png' title='Toon opties voor $adres'></a> <a href='http://funda.nl/". $row[$HuizenID] ."' target='_blank' class='$class' title='Bezoek $adres op funda.nl'>$adres</a></td>\n";
		if($breedte_1 != 0) { echo "		<td width='". $breedte_1 ."%'>&nbsp;</td>\n"; }
		echo "		<td width='". $breedte_2 ."%' bgcolor='#FF6D6D' title='". getDoorloptijd($row[$HuizenID]) ." in de verkoop. Van ". date("d-m-y", $row[$HuizenStart]) .' t/m '. date("d-m-y", $row[$HuizenEind]) ."'>&nbsp;</td>\n";
		if($breedte_3 != 0) { echo "		<td width='". $breedte_3 ."%' bgcolor='#FFA0A0'>&nbsp;</td>\n"; }
		if($breedte_4 != 0) { echo "		<td width='". $breedte_4 ."%'>&nbsp;</td>\n"; }
		echo "		<td width='5%' align='right'><a href='PrijsDaling.php?selectie=". $_REQUEST['selectie'] ."#". $row[$HuizenID] ."' title='Bekijk de prijsdaling van $adres\nVraagprijs ". formatPrice(getOrginelePrijs($row[$HuizenID])) ." | Gecorrigeerde prijs ". formatPrice(corrigeerPrice($row[$HuizenStart], getOrginelePrijs($row[$HuizenID]))) ."'>". number_format($percentageAll, 0) ."%</a></td>\n";
		echo "	</tr></table>\n";
		echo "</td></tr>\n";
	} while($row = mysql_fetch_array($result));
	
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
	echo showBlock(implode("\n", $HTML));
	echo "</td>\n";
	echo "<td width='25%' valign='top' align='center'>&nbsp;</td>\n";
	echo "</tr>\n";	
}

echo $HTMLFooter;
?>
