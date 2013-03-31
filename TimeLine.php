<?php
include_once('../general_include/general_functions.php');
include_once('../general_include/general_config.php');
include_once('include/functions.php');
include_once('include/config.php');
include_once('include/HTML_TopBottom.php');
connect_db();

echo $HTMLHeader;

if(isset($_REQUEST['regio'])) {
	$regio = $_REQUEST['regio'];
	$opdrachtData = getOpdrachtData($regio);
	$gisteren = time() - (24*60*60);
	
	$sql		= "SELECT min($TableHuizen.$HuizenStart) FROM $TableResultaat, $TableHuizen WHERE $TableResultaat.$ResultaatID = $TableHuizen.$HuizenID AND $TableResultaat.$ResultaatZoekID = $regio";
	$result	= mysql_query($sql);
	$row		= mysql_fetch_array($result);
	$start_tijd = $row[0];
	
	$sql		= "SELECT max($TableHuizen.$HuizenEind) FROM $TableResultaat, $TableHuizen WHERE $TableResultaat.$ResultaatID = $TableHuizen.$HuizenID AND $TableResultaat.$ResultaatZoekID = $regio";
	$result	= mysql_query($sql);
	$row		= mysql_fetch_array($result);
	$eind_tijd = $row[0];
	
	//$sql		= "SELECT $TableHuizen.$HuizenOffline, $TableHuizen.$HuizenVerkocht, $TableHuizen.$HuizenStart, $TableHuizen.$HuizenEind, $TableHuizen.$HuizenAdres, $TableHuizen.$HuizenID, $TableHuizen.$HuizenURL, ($TableHuizen.$HuizenEind - $TableHuizen.$HuizenStart) as tijdsduur FROM $TableResultaat, $TableHuizen WHERE $TableResultaat.$ResultaatID = $TableHuizen.$HuizenID AND $TableResultaat.$ResultaatZoekID =  $regio ORDER BY tijdsduur DESC, $TableHuizen.$HuizenStart";
	$sql		= "SELECT $TableHuizen.$HuizenOffline, $TableHuizen.$HuizenVerkocht, $TableHuizen.$HuizenStart, $TableHuizen.$HuizenEind, $TableHuizen.$HuizenAdres, $TableHuizen.$HuizenID, $TableHuizen.$HuizenURL, ($TableHuizen.$HuizenEind - $TableHuizen.$HuizenStart) as tijdsduur FROM $TableResultaat, $TableHuizen WHERE $TableResultaat.$ResultaatID = $TableHuizen.$HuizenID AND $TableResultaat.$ResultaatZoekID =  $regio ORDER BY $TableHuizen.$HuizenAdres";
	$result	= mysql_query($sql);
	$row		= mysql_fetch_array($result); 
	
	$fullWidth = $eind_tijd - $start_tijd;
	
	echo "<table width='100%' border=0>\n";
	echo "<tr>\n";
	echo "	<td align='center'><h1>Tijdslijn '". $opdrachtData['naam'] ."'</h1></td>\n";
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
		//$breedte_3	= round(70*($eind_tijd - $row[$HuizenEind])/$fullWidth);
		$breedte_3	= 70 - $breedte_1 - $breedte_2;;
		$adres			= convertToReadable(urldecode($row[$HuizenAdres]));
		
		$prijzen	= getPriceHistory($row[$HuizenID]);
		$laatste	= current($prijzen);
		$eerste		= end($prijzen);
				
		if(max($prijzen) > 0) {
			$percentageAll	= 100*($eerste - $laatste)/$eerste;
		} else {
			$percentageAll = 0;
		}		
		
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
		echo "		<td width='25%'><a id='". $row[$HuizenID] ."'><a href='admin/HouseDetails.php?regio=". $regio ."&id=". $row[$HuizenID] ."'><img src='http://www.vvaltena.nl/styles/img/details/report.png'></a> <a href='http://www.funda.nl". urldecode($row[$HuizenURL]) ."' target='_blank' class='$class'>$adres</a></td>\n";		
		if($breedte_1 != 0) { echo "		<td width='". $breedte_1 ."%'>&nbsp;</td>\n"; }
		echo "		<td width='". $breedte_2 ."%' bgcolor='#FF6D6D' title='In de verkoop van ". date("d-m", $row[$HuizenStart]) .' t/m '. date("d-m", $row[$HuizenEind]) ."'>". getDoorloptijd($row[$HuizenID]) ."</td>\n";
		if($breedte_3 != 0) { echo "		<td width='". $breedte_3 ."%'>&nbsp;</td>\n"; }
		echo "		<td width='5%' align='right'><a href='PrijsDaling.php?regio=$regio#". $row[$HuizenID] ."'>". number_format($percentageAll, 0) ."%</a></td>\n";			
		echo "	</tr></table>\n";
		echo "</td></tr>\n";
	} while($row = mysql_fetch_array($result));
	
	echo "</table>\n";
} else {
	echo "<form method='post' action='$_SERVER[PHP_SELF]'>\n";
	echo "<table>\n";
	echo "<tr>\n";
	echo "	<td>Regio</td>\n";
	echo "	<td>&nbsp;</td>\n";
	echo "	<td><select name='regio'>\n";

	$Opdrachten = getZoekOpdrachten(1);
	foreach($Opdrachten as $OpdrachtID) {
		$OpdrachtData = getOpdrachtData($OpdrachtID);
		echo "	<option value='$OpdrachtID'". ($OpdrachtID == $regio ? ' selected' : '') .">". $OpdrachtData['naam'] ."</option>\n";
	}
	echo "	</select>\n";
	echo "	</td>\n";
	echo "</tr>\n";
	echo "	<td colspan=3><input type='submit' name='submit' value='Weergeven'></td>\n";
	echo "</tr>\n";
	echo "<table>\n";
	echo "</form>\n";
}

echo $HTMLFooter;
?>
	