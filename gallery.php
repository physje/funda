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
	
	$i = 1;	
	echo "<form method='post' action='$_SERVER[PHP_SELF]'>\n";
	if($showListAdd) echo "<input type='hidden' name='lijst' value='". $_POST['chosenList'] ."'>\n";
	echo "<table width='100%' border=0>\n";
	echo "<tr>\n";
	echo "	<td align='center' colspan='$aantalCols'><h1>Gallery '$Name'</h1></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "	<td colspan='$aantalCols'>&nbsp;</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";

	foreach($dataset as $huisID) {
		$data 			= getFundaData($huisID);
		$adres			= convertToReadable($data['adres']);
		$image			= str_replace('_klein.jpg', '_middel.jpg', $data['thumb']);
		$prijzen		= getPriceHistory($huisID);					
	
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
		
		if($data['verkocht'] == '1' || $data['offline'] == '1') {
			$image = changeThumbLocation($image);
			$imageClass = 'imageUnavailable';
		} else {
			$imageClass = 'imageAvailable';
		}
		
		$Foto  = "	<img src='$image' class='$imageClass'><br>";
		if($showListAdd)	$Foto .= "	<input type='checkbox' name='huis[]' value='$huisID'". (in_array($huisID, $knownHuizen) ? ' checked' : '') .">";
		$Foto .= "	<a href='http://www.funda.nl". $data['url'] ."' target='_blank' class='$TextClass'>$adres</a><br>\n";
		$Foto .= "	<b>&euro;&nbsp;". number_format(array_shift($prijzen), 0,'','.') ."</b>\n";
				
		echo "	<td align='center'>";
		echo showBlock($Foto);
		echo "	</td>\n";
		
		if(($i % $aantalCols) == 0) {
			echo "</tr>\n";
			echo "<tr>\n";
			$i=1;
		} else {
			$i++;
		}
	}
	
	echo "</tr>\n";
	
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
	