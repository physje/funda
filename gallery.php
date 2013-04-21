<?php
include_once('../general_include/general_functions.php');
include_once('../general_include/general_config.php');
include_once('include/functions.php');
include_once('include/config.php');
include_once('include/HTML_TopBottom.php');
connect_db();

$aantalCols			= 4;

echo $HTMLHeader;

if(isset($_POST['add'])) {
	foreach($_POST['huis'] as $huis) {
		$sql_check = "SELECT * FROM $TableListResult WHERE $ListResultList like ". $_POST['lijst'] ." AND $ListResultHuis like '$huis'";
		$result	= mysql_query($sql_check);
		if(mysql_num_rows($result) == 0) {
			$sql_insert = "INSERT INTO $TableListResult ($ListResultList, $ListResultHuis) VALUES (". $_POST['lijst'] .", $huis)";
			if(!mysql_query($sql_insert)) {
				echo '<b>'. $huis .' niet toegevoegd</b><br>';
			} else {
				echo $huis .' toegevoegd<br>';
			}
		} else {
			echo $huis .' bestaat al<br>';
		}			
	}
} elseif(isset($_POST['submit_opdracht']) || isset($_POST['submit_lijst'])) {
	if(isset($_POST['submit_opdracht'])) {
		$opdracht			= $_POST['opdracht'];
		$opdrachtData	= getOpdrachtData($opdracht);
		$galleryName	= $opdrachtData['naam'];
		$from					= "$TableResultaat, $TableHuizen";
		$where				= "$TableResultaat.$ResultaatID = $TableHuizen.$HuizenID AND $TableResultaat.$ResultaatZoekID = $opdracht";		
	} else {
		$lijst				= $_POST['lijst'];
		$LijstData		= getLijstData($lijst);
		$galleryName	= $LijstData['naam'];
		$from					= "$TableListResult, $TableHuizen";
		$where				= "$TableListResult.$ListResultHuis = $TableHuizen.$HuizenID AND $TableListResult.$ListResultList = $lijst";
	}
	
	if($_POST['addHouses'] == '1') {
		$showListAdd = true;
	}
	
	$sql = "SELECT $TableHuizen.$HuizenOffline, $TableHuizen.$HuizenThumb, $TableHuizen.$HuizenVerkocht, $TableHuizen.$HuizenAdres, $TableHuizen.$HuizenID, $TableHuizen.$HuizenURL FROM $from WHERE $where ORDER BY $TableHuizen.$HuizenAdres";
	
	$result	= mysql_query($sql);
	$row		= mysql_fetch_array($result);
	
	$i = 1;	
	echo "<form method='post' action='$_SERVER[PHP_SELF]'>\n";
	echo "<table width='100%' border=0>\n";
	echo "<tr>\n";
	echo "	<td align='center' colspan='$aantalCols'><h1>Gallery '$galleryName'</h1></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "	<td colspan='$aantalCols'>&nbsp;</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	
	do {		
		$adres			= convertToReadable(urldecode($row[$HuizenAdres]));
		$image			= str_replace('_klein.jpg', '_middel.jpg', urldecode($row[$HuizenThumb]));
		$prijzen		= getPriceHistory($row[$HuizenID]);					
	
		if($row[$HuizenOffline] == '1') {
			if($row[$HuizenVerkocht] != '1') {
				$TextClass = 'offline';
			} else {
				$TextClass = 'offlineVerkocht';
			}			
		} elseif($row[$HuizenVerkocht] == '1') {
			$TextClass = 'onlineVerkocht';
		} else {
			$TextClass = 'online';
		}
		
		if($row[$HuizenOffline] == '1' || $row[$HuizenVerkocht] == '1') {
			$image = str_replace('http://images.funda.nl/valentinamedia/', 'http://cloud.funda.nl/valentina_media/', $image);
			$imageClass = 'imageUnavailable';
		} else {
			$imageClass = 'imageAvailable';
		}
		
		$Foto  = "	<img src='$image' class='$imageClass'><br>";
		if($showListAdd)	$Foto .= "	<input type='checkbox' name='huis[]' value='". $row[$HuizenID] ."'>";
		$Foto .= "	<a href='http://www.funda.nl". urldecode($row[$HuizenURL]) ."' target='_blank' class='$TextClass'>$adres</a><br>\n";
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

	} while($row = mysql_fetch_array($result));
	
	echo "</tr>\n";
	
	if($showListAdd) {
		echo "<tr>\n";
		echo "	<td colspan='$aantalCols'>&nbsp;</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "	<td colspan='$aantalCols'>";
		echo "	<select name='lijst'>";
		
		$Lijsten = getLijsten(1);					
		foreach($Lijsten as $LijstID) {
			$LijstData = getLijstData($LijstID);
			echo "	<option value='$LijstID' ". ($_POST['chosenList'] == $LijstID ? ' selected' : '') .">". $LijstData['naam'] ."</option>";		
		}
		
		echo "	</select>";
		echo "	<input type='submit' name='add' value='Voeg toe'>";
		echo "	</td>\n";
		echo "</tr>\n";
	}
	
	echo "</table>\n";
	echo "</form>\n";	
} else {
	$Opdrachten = getZoekOpdrachten(1);
	$Lijsten = getLijsten(1);
	
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
	echo "	<td>Zoekopdracht</td>\n";	
	if($showList) {
		echo "	<td>&nbsp;</td>\n";
		echo "	<td>Lijst</td>\n";
	}
	echo "</tr>\n";
	echo "<tr>\n";
	echo "	<td><select name='opdracht'>\n";
	echo "	<option value=''> [selecteer opdracht] </option>\n";
	
	foreach($Opdrachten as $OpdrachtID) {
		$OpdrachtData = getOpdrachtData($OpdrachtID);
		echo "	<option value='$OpdrachtID'>". $OpdrachtData['naam'] ."</option>\n";
	}
	echo "	</select>\n";	
	if($showList) {
		echo "	<td>&nbsp;</td>\n";
		echo "	<td><select name='lijst'>\n";
		echo "	<option value=''> [selecteer lijst] </option>\n";
	
		foreach($Lijsten as $LijstID) {
			$LijstData = getLijstData($LijstID);
			echo "	<option value='$LijstID'>". $LijstData['naam'] ."</option>\n";
		}
		echo "	</select>\n";	
	}
	echo "	</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "	<td><input type='submit' name='submit_opdracht' value='Opdracht weergeven'></td>\n";
	if($showList) {
		echo "	<td>&nbsp;</td>\n";
		echo "	<td><input type='submit' name='submit_lijst' value='Lijst weergeven'></td>\n";
	}
	echo "</tr>\n";
	echo "<table>\n";
	echo "</form>\n";
}

echo $HTMLFooter;
?>
	