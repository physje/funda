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
} elseif(isset($_REQUEST['submit_opdracht']) || isset($_REQUEST['submit_lijst'])) {
	if(isset($_REQUEST['submit_opdracht'])) {
		$opdracht			= $_REQUEST['opdracht'];
		$opdrachtData	= getOpdrachtData($opdracht);
		$Name	= $opdrachtData['naam'];
		$from					= "$TableResultaat, $TableHuizen";
		$where				= "$TableResultaat.$ResultaatID = $TableHuizen.$HuizenID AND $TableResultaat.$ResultaatZoekID = $opdracht";
	} else {
		$lijst				= $_REQUEST['lijst'];
		$LijstData		= getLijstData($lijst);
		$Name					= $LijstData['naam'];
		$from					= "$TableListResult, $TableHuizen";
		$where				= "$TableListResult.$ListResultHuis = $TableHuizen.$HuizenID AND $TableListResult.$ListResultList = $lijst";
		}
		
	if($_POST['addHouses'] == '1') {
		$showListAdd = true;
	}
	
	$sql		= "SELECT * FROM $from WHERE $where ORDER BY $TableHuizen.$HuizenAdres";
	$result	= mysql_query($sql);
	$row		= mysql_fetch_array($result);
	
	$max_percentage = 33;
		
	echo "<form method='post' action='$_SERVER[PHP_SELF]'>\n";
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
		
	do {
		$percentage = $breedte = array();
		$prijzen	= getPriceHistory($row[$HuizenID]);
		//$laatste	= array_shift($prijzen);
								
		if(max($prijzen) > 0) {
			$eerste		= array_pop($prijzen);
			$prijzenRev	= array_reverse($prijzen, true);
			$vorige		= $eerste;
			
			foreach($prijzenRev as $key => $prijs) {
				$percentage[$key]	= 100*($vorige - $prijs)/$vorige;				
				$breedte[$key]		= round(100*$percentage[$key]/$max_percentage);
				$vorige						= $prijs;
				
				$percentage_overall[$key]	= 100*($eerste - $prijs)/$eerste;
			}
				
			if(array_sum($percentage) > 0) {
				$percGemiddeld[]	= array_sum($percentage);
			}
		} else {
			$breedte[] = 0;
		}
		
		$restBreedte = 100 - array_sum($breedte);
		
		if($row[$HuizenOffline] == '1') {
			if($row[$HuizenVerkocht] != '1') {
				$class = 'offline';
			} else {
				$class = 'offlineVerkocht';
			}			
		} elseif($row[$HuizenVerkocht] == '1') {
			$class = 'onlineVerkocht';
		} else {
			$class = '';
		}
				
		echo "<tr>\n";
		echo "	<td width='25%'>";
		if($showListAdd)	echo "	<input type='checkbox' name='huis[]' value='". $row[$HuizenID] ."'>";
		echo "<a id='". $row[$HuizenID] ."'><a href='admin/HouseDetails.php?regio=". $regio ."&id=". $row[$HuizenID] ."'><img src='http://www.vvaltena.nl/styles/img/details/report.png'></a> <a id='". $row[$HuizenID] ."'><a href='http://www.funda.nl". urldecode($row[$HuizenURL]) ."' target='_blank' class='$class'>". urldecode($row[$HuizenAdres]) ."</a></td>\n";
		echo "	<td colspan=2>\n";
		echo "	<table width='100%' border=0><tr>\n";
		if(array_sum($breedte) > 0) {
			
			foreach($breedte as $tijd => $value) {
				echo "		<td width='". $value ."%' bgcolor='#FF6D6D' title='Gedaald naar &euro;&nbsp;". number_format($prijzen[$tijd], 0,'','.') ." (afname ".number_format($percentage[$tijd], 0) ."%)\nOorspronkelijk &euro;&nbsp;". number_format($eerste, 0,'','.') ." (afname ".number_format($percentage_overall[$tijd], 0) ."%)'>&nbsp;</td>\n";
			}
		}
		echo "		<td width='". $restBreedte ."%'>&nbsp;</td>\n";
		echo "	</tr></table>\n";
		echo "	</td>\n";
		echo "	<td width='7%'><a href='TimeLine.php?regio=$regio#". $row[$HuizenID] ."'>". getDoorloptijd($row[$HuizenID]) ."</a></td>\n";		
		echo "</tr>\n";
	} while($row = mysql_fetch_array($result));
	
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
	
	if($showListAdd) {
		echo "<tr>\n";
		echo "	<td colspan='4'>&nbsp;</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "	<td colspan='4'>";
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
